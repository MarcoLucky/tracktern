<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Classroom;
use Carbon\Carbon;

class RenderedHoursService
{
    /**
     * Calculate comprehensive progress details for a student.
     */
    public function calculateStudentProgress(Student $student, ?int $classroomId = null): array
    {
        $attendanceQuery = $student->attendance()->whereIn('status', ['completed', 'open']);
        if ($classroomId) {
            $attendanceQuery->where('classroom_id', $classroomId);
        }

        $totalRenderedMinutes = (int) $attendanceQuery->sum('rendered_minutes');
        $totalRenderedHours = round($totalRenderedMinutes / 60, 2);

        // Required hours: set by teacher on joined classroom
        $activeClassroom = null;
        if ($classroomId) {
            $activeClassroom = Classroom::find($classroomId);
        } else {
            $activeClassroom = $student->classrooms()->wherePivot('status', 'active')->first();
        }

        $targetHours = 0;
        $classroomJoined = false;

        if ($activeClassroom && $activeClassroom->required_hours > 0) {
            $targetHours = $activeClassroom->required_hours;
            $classroomJoined = true;
        } elseif ($student->target_hours > 0) {
            $targetHours = $student->target_hours;
        }

        $remainingHours = $targetHours > 0 ? max(0, round($targetHours - $totalRenderedHours, 2)) : 0;
        $progressPercentage = $targetHours > 0 ? min(100, round(($totalRenderedHours / $targetHours) * 100, 1)) : 0;

        $totalDaysRendered = $student->attendance()
            ->whereNotNull('time_in')
            ->selectRaw('DATE(time_in) as date_rendered')
            ->distinct()
            ->count('time_in');

        // Status Badge Logic
        $statusBadge = 'On Track';
        if ($targetHours > 0 && $totalRenderedHours >= $targetHours) {
            $statusBadge = 'Completed';
        } else {
            if ($student->internship_start_date && $student->internship_end_date) {
                $startDate = Carbon::parse($student->internship_start_date);
                $endDate = Carbon::parse($student->internship_end_date);
                $now = Carbon::now();

                if ($now->greaterThan($startDate)) {
                    $totalSpanDays = max(1, $startDate->diffInDays($endDate));
                    $elapsedDays = min($totalSpanDays, $startDate->diffInDays($now));
                    $expectedPercentage = ($elapsedDays / $totalSpanDays) * 100;

                    if ($progressPercentage < ($expectedPercentage - 15)) {
                        $statusBadge = 'Behind';
                    } elseif ($progressPercentage < ($expectedPercentage - 5)) {
                        $statusBadge = 'Needs Attention';
                    }
                }
            }
        }

        $taskQuery = $student->tasks();
        if ($classroomId) {
            $taskQuery->where('classroom_id', $classroomId);
        }

        $completedTasksCount = (clone $taskQuery)->where('status', 'approved')->count();
        $pendingTasksCount = (clone $taskQuery)->where('status', 'pending')->count();
        $needsRevisionTasksCount = (clone $taskQuery)->where('status', 'needs_revision')->count();

        return [
            'student_id' => $student->id,
            'student_name' => $student->user ? $student->user->name : 'N/A',
            'intern_id' => $student->intern_id,
            'company_name' => $student->company_name ?? 'N/A',
            'classroom_joined' => $classroomJoined,
            'classroom_name' => $activeClassroom ? $activeClassroom->classroom_name : null,
            'total_rendered_minutes' => $totalRenderedMinutes,
            'total_rendered_hours' => $totalRenderedHours,
            'required_target_hours' => $targetHours,
            'remaining_hours' => $remainingHours,
            'progress_percentage' => $progressPercentage,
            'total_days_rendered' => $totalDaysRendered,
            'status_badge' => $statusBadge,
            'tasks_stat' => [
                'approved' => $completedTasksCount,
                'pending' => $pendingTasksCount,
                'needs_revision' => $needsRevisionTasksCount,
                'total' => $taskQuery->count(),
            ],
        ];
    }
}
