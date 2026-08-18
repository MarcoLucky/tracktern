<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Task;
use App\Models\Attendance;
use App\Services\RenderedHoursService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherDashboardController extends Controller
{
    protected RenderedHoursService $renderedHoursService;

    public function __construct(RenderedHoursService $renderedHoursService)
    {
        $this->renderedHoursService = $renderedHoursService;
    }

    /**
     * Teacher Dashboard Summary metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile not found.'], 404);
        }

        $classrooms = $teacher->classrooms()->with(['students', 'course'])->get();
        $classroomIds = $classrooms->pluck('id')->toArray();

        $totalEnrolledStudents = $classrooms->flatMap(fn ($c) => $c->students)->unique('id')->count();

        // Students Currently Rendering (active open attendance record in teacher's classrooms)
        $currentlyRenderingCount = Attendance::whereIn('classroom_id', $classroomIds)
            ->open()
            ->distinct('student_id')
            ->count('student_id');

        // Pending Approvals
        $pendingTasksCount = Task::whereIn('classroom_id', $classroomIds)->where('status', 'pending')->count();

        // Student status calculation (Completed)
        $completedCount = 0;

        foreach ($classrooms as $classroom) {
            foreach ($classroom->students as $student) {
                $progress = $this->renderedHoursService->calculateStudentProgress($student, $classroom->id);
                if ($progress['status_badge'] === 'Completed') {
                    $completedCount++;
                }
            }
        }

        return response()->json([
            'teacher' => $teacher,
            'summary' => [
                'total_active_classrooms' => $classrooms->count(),
                'total_enrolled_students' => $totalEnrolledStudents,
                'students_currently_rendering' => $currentlyRenderingCount,
                'completed_internships' => $completedCount,
                'pending_task_approvals' => $pendingTasksCount,
            ],
            'classrooms' => $classrooms,
        ]);
    }
}
