<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AuditLogService;
use App\Mail\TaskNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentTaskController extends Controller
{
    /**
     * List student's submitted tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $query = $student->tasks()->with(['attachments', 'classroom', 'attendance']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->orderBy('submitted_at', 'desc')->paginate(10);

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
            'attachments.*' => 'nullable|file|mimes:pdf,docx,png,jpg,jpeg|max:10240',
        ]);

        $task = Task::create([
            'student_id' => $student->id,
            'classroom_id' => $validated['classroom_id'] ?? $student->classrooms()->first()?->id,
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

        $task->load(['attachments', 'classroom', 'attendance']);

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
                Mail::to($student->user->email)->send(new TaskNotificationMail($task, 'submitted'));
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
