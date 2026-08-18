<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AuditLogService;
use App\Services\PhpMailerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentTaskController extends Controller
{
    protected PhpMailerService $mailerService;

    public function __construct(PhpMailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    /**
     * List student's submitted tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $validated = $request->validate([
            'status' => 'nullable|string',
            'month' => 'nullable|date_format:Y-m',
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $student->tasks()->with(['attachments', 'classroom', 'attendance']);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['classroom_id'])) {
            if (!$student->classrooms()->where('classroom_id', $validated['classroom_id'])->exists()) {
                return response()->json(['message' => 'Selected classroom does not belong to your account.'], 422);
            }

            $query->where('classroom_id', $validated['classroom_id']);
        }

        if (!empty($validated['month'])) {
            $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
            $query->whereHas('attendance', function ($attendanceQuery) use ($month) {
                $attendanceQuery->whereBetween('date', [
                    $month->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ]);
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 10);
        $tasks = $query->orderBy('submitted_at', 'desc')->paginate($perPage);

        return response()->json($tasks);
    }

    /**
     * Submit a new task log linked to a specific DTR entry/accomplishment date.
     */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $validated = $request->validate([
            'classroom_id' => 'nullable|exists:classrooms,id',
            'attendance_id' => 'required|exists:attendance,id', // Required selected DTR entry
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'nullable|string|max:100',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,png,jpg,jpeg,gif,webp,mp4,mov,avi,webm,mkv|max:51200',
        ]);

        $attendance = $student->attendance()->find($validated['attendance_id']);

        if (!$attendance) {
            return response()->json(['message' => 'Selected DTR entry does not belong to your account.'], 422);
        }

        if (!$attendance->time_out || $attendance->status !== 'completed') {
            return response()->json(['message' => 'Please time out before submitting an accomplishment report for this DTR entry.'], 422);
        }

        if (!empty($validated['classroom_id']) && !$student->classrooms()->where('classroom_id', $validated['classroom_id'])->exists()) {
            return response()->json(['message' => 'Selected classroom does not belong to your account.'], 422);
        }

        $classroomId = $validated['classroom_id']
            ?? $attendance->classroom_id
            ?? $student->classrooms()->wherePivot('status', 'active')->first()?->id;

        if (!$classroomId) {
            return response()->json(['message' => 'Please join a classroom before submitting an accomplishment report.'], 422);
        }

        $task = Task::create([
            'student_id' => $student->id,
            'classroom_id' => $classroomId,
            'attendance_id' => $validated['attendance_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'General',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task_attachments', 'public');
                TaskAttachment::create([
                    'task_id' => $task->id,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $task->load(['attachments', 'classroom.teacher.user', 'attendance', 'student.user']);

        AuditLogService::log(
            action: 'submit_task',
            module: 'tasks',
            userId: $student->user_id,
            recordId: $task->id,
            payload: ['title' => $task->title, 'attendance_id' => $task->attendance_id]
        );

        // Send Email Notification on Task Submission
        if ($student->user && $student->user->email) {
            try {
                $this->mailerService->sendTaskNotification($task, 'submitted');
            } catch (\Throwable $e) {}
        }

        if ($task->classroom?->teacher?->user?->email) {
            try {
                $this->mailerService->sendTaskSubmissionToTeacher($task);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Task log submitted successfully.',
            'task' => $task,
        ], 201);
    }

    /**
     * View single task log detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->user()->student;

        $task = Task::with(['attachments', 'classroom.teacher.user', 'attendance'])->where('student_id', $student->id)->findOrFail($id);

        return response()->json($task);
    }
}
