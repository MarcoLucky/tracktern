<?php

namespace App\Policies;

use App\Models\WeeklyReport;
use App\Models\User;

class WeeklyReportPolicy
{
    public function view(User $user, WeeklyReport $weeklyReport): bool
    {
        if ($user->isStudent()) {
            return $user->student && $weeklyReport->student_id === $user->student->id;
        }

        if ($user->isTeacher()) {
            return $user->teacher && $weeklyReport->classroom && $weeklyReport->classroom->teacher_id === $user->teacher->id;
        }

        return false;
    }

    public function review(User $user, WeeklyReport $weeklyReport): bool
    {
        return $user->isTeacher() && $user->teacher && $weeklyReport->classroom && $weeklyReport->classroom->teacher_id === $user->teacher->id;
    }
}
