<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($user->isStudent()) {
            return $user->student && $task->student_id === $user->student->id;
        }

        if ($user->isTeacher()) {
            return $user->teacher && $task->classroom && $task->classroom->teacher_id === $user->teacher->id;
        }

        return false;
    }

    public function review(User $user, Task $task): bool
    {
        return $user->isTeacher() && $user->teacher && $task->classroom && $task->classroom->teacher_id === $user->teacher->id;
    }
}
