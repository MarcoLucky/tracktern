<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->isTeacher()) {
            return $user->teacher && $classroom->teacher_id === $user->teacher->id;
        }

        if ($user->isStudent()) {
            return $user->student && $user->student->classrooms()->where('classroom_id', $classroom->id)->exists();
        }

        return false;
    }

    public function manage(User $user, Classroom $classroom): bool
    {
        return $user->isTeacher() && $user->teacher && $classroom->teacher_id === $user->teacher->id;
    }
}
