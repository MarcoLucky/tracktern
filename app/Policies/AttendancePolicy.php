<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isStudent()) {
            return $user->student && $attendance->student_id === $user->student->id;
        }

        if ($user->isTeacher()) {
            return $user->teacher && $attendance->classroom && $attendance->classroom->teacher_id === $user->teacher->id;
        }

        return false;
    }
}
