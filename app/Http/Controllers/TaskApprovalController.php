<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AuditLogService;
use App\Mail\TaskNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $classroomIds = $teacher->classrooms()->pluck('id')->toArray();

        $query = Task::whereIn('classroom_id', $classroomIds)->with(['student.user', 'attachments', 'classroom']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        $tasks = $query->orderBy('submitted_at', 'asc')->paginate(15);

        return response()->json($tasks);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $task = Task::whereHas('classroom', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->with('student.user')->findOrFail($id);

        $task->update([
            'status' => 'approved',
            'teacher_feedback' => $request->input('feedback', 'Approved by instructor.'),
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'approve_task',
            module: 'tasks',
            userId: $teacher->user_id,
            recordId: $task->id
        );

        if ($task->student && $task->student->user && $task->student->user->email) {
            try {
                Mail::to($task->student->user->email)->send(new TaskNotificationMail($task, 'approve'));
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Task approved successfully.',
            'task' => $task,
        ]);
    }

    public function requestRevision(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $validated = $request->validate([
            'feedback' => 'required|string|min:3',
        ]);

        $task = Task::whereHas('classroom', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->with('student.user')->findOrFail($id);

        $task->update([
            'status' => 'needs_revision',
            'teacher_feedback' => $validated['feedback'],
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'request_task_revision',
            module: 'tasks',
            userId: $teacher->user_id,
            recordId: $task->id,
            payload: ['feedback' => $validated['feedback']]
        );

        if ($task->student && $task->student->user && $task->student->user->email) {
            try {
                Mail::to($task->student->user->email)->send(new TaskNotificationMail($task, 'revision'));
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Revision requested for task.',
            'task' => $task,
        ]);
    }
}
