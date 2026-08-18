<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AuditLogService;
use App\Services\PhpMailerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskApprovalController extends Controller
{
    protected PhpMailerService $mailerService;

    public function __construct(PhpMailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $classroomIds = $teacher->classrooms()->pluck('id')->toArray();

        $query = Task::whereIn('classroom_id', $classroomIds)->with(['student.user', 'attachments', 'classroom', 'attendance']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        $tasks = $query->orderByDesc('submitted_at')->paginate(15);

        return response()->json($tasks);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $task = $this->taskQueryForTeacher($teacher)
            ->with(['student.user', 'student.course', 'attachments', 'classroom.course', 'attendance'])
            ->findOrFail($id);

        return response()->json($task);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $task = $this->taskQueryForTeacher($teacher)->with('student.user')->findOrFail($id);

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
                $this->mailerService->sendTaskNotification($task, 'approve');
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Task approved successfully.',
            'task' => $task,
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:2000',
        ]);

        $task = $this->taskQueryForTeacher($teacher)->with('student.user')->findOrFail($id);

        $task->update([
            'status' => 'rejected',
            'teacher_feedback' => $validated['reason'],
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'reject_task',
            module: 'tasks',
            userId: $teacher->user_id,
            recordId: $task->id,
            payload: ['reason' => $validated['reason']]
        );

        if ($task->student && $task->student->user && $task->student->user->email) {
            try {
                $this->mailerService->sendTaskNotification($task, 'reject');
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Task rejected successfully.',
            'task' => $task,
        ]);
    }

    public function requestRevision(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $validated = $request->validate([
            'feedback' => 'required|string|min:3',
        ]);

        $task = $this->taskQueryForTeacher($teacher)->with('student.user')->findOrFail($id);

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
                $this->mailerService->sendTaskNotification($task, 'revision');
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Revision requested for task.',
            'task' => $task,
        ]);
    }

    public function approveAll(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $validated = $request->validate([
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
        ]);

        $query = $this->pendingTaskQuery($teacher, $validated['classroom_id'] ?? null);
        $tasks = $query->with('student.user')->get();

        $approvedCount = $this->approveTaskCollection($tasks, $teacher->user_id, 'Approved by instructor.');

        return response()->json([
            'message' => $approvedCount === 1
                ? '1 pending task approved successfully.'
                : "{$approvedCount} pending tasks approved successfully.",
            'approved_count' => $approvedCount,
        ]);
    }

    public function approveAllForStudent(Request $request, int $studentId): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $validated = $request->validate([
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
        ]);

        $query = $this->pendingTaskQuery($teacher, $validated['classroom_id'] ?? null)
            ->where('student_id', $studentId);

        $tasks = $query->with('student.user')->get();
        $approvedCount = $this->approveTaskCollection($tasks, $teacher->user_id, 'Approved by instructor.');

        return response()->json([
            'message' => $approvedCount === 1
                ? '1 pending task approved for this student.'
                : "{$approvedCount} pending tasks approved for this student.",
            'approved_count' => $approvedCount,
        ]);
    }

    private function taskQueryForTeacher($teacher)
    {
        return Task::whereHas('classroom', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        });
    }

    private function pendingTaskQuery($teacher, ?int $classroomId = null)
    {
        if ($classroomId && !$teacher->classrooms()->whereKey($classroomId)->exists()) {
            abort(403, 'You do not manage this classroom.');
        }

        $query = $this->taskQueryForTeacher($teacher)->where('status', 'pending');

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        return $query;
    }

    private function approveTaskCollection($tasks, int $teacherUserId, string $feedback): int
    {
        $approvedCount = 0;

        DB::transaction(function () use ($tasks, $teacherUserId, $feedback, &$approvedCount) {
            foreach ($tasks as $task) {
                $task->update([
                    'status' => 'approved',
                    'teacher_feedback' => $feedback,
                    'reviewed_at' => now(),
                ]);

                AuditLogService::log(
                    action: 'approve_task',
                    module: 'tasks',
                    userId: $teacherUserId,
                    recordId: $task->id,
                    payload: ['bulk_approval' => true]
                );

                $approvedCount++;
            }
        });

        foreach ($tasks as $task) {
            if ($task->student && $task->student->user && $task->student->user->email) {
                try {
                    $this->mailerService->sendTaskNotification($task->fresh(['student.user']), 'approve');
                } catch (\Throwable $e) {}
            }
        }

        return $approvedCount;
    }
}
