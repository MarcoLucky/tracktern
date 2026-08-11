<?php

namespace App\Http\Controllers;

use App\Services\RenderedHoursService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentDashboardController extends Controller
{
    protected RenderedHoursService $renderedHoursService;

    public function __construct(RenderedHoursService $renderedHoursService)
    {
        $this->renderedHoursService = $renderedHoursService;
    }

    /**
     * Get Student Dashboard Summary Data.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStudent() || !$user->student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $student = $user->student;
        $student->load(['course', 'classrooms.teacher.user']);

        $progressData = $this->renderedHoursService->calculateStudentProgress($student);

        // Recent 5 attendance records & recent 5 tasks
        $recentAttendance = $student->attendance()->latest('date')->take(5)->get();
        $recentTasks = $student->tasks()->latest('submitted_at')->take(5)->get();

        return response()->json([
            'student_profile' => $student,
            'summary' => $progressData,
            'recent_attendance' => $recentAttendance,
            'recent_tasks' => $recentTasks,
        ]);
    }
}
