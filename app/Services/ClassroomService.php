<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Str;
use Exception;

class ClassroomService
{
    /**
     * Generate unique 5-digit uppercase alphanumeric classroom code (e.g. CR902).
     */
    public function generateUniqueClassroomCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while (Classroom::where('classroom_code', $code)->exists());

        return $code;
    }

    /**
     * Generate unique 5-digit numeric Intern ID (e.g. 58392).
     */
    public function generateUniqueInternId(): string
    {
        do {
            $internId = (string) rand(10000, 99999);
        } while (Student::where('intern_id', $internId)->exists());

        return $internId;
    }

    /**
     * Join student to classroom via 5-digit code.
     */
    public function joinClassroom(Student $student, string $classroomCode): Classroom
    {
        $classroom = Classroom::where('classroom_code', strtoupper(trim($classroomCode)))->first();

        if (!$classroom) {
            throw new Exception('Invalid classroom code. Please check and try again.');
        }

        if ($student->classrooms()->where('classroom_id', $classroom->id)->exists()) {
            throw new Exception('Student is already enrolled in this classroom.');
        }

        $student->classrooms()->attach($classroom->id, [
            'joined_at' => now(),
            'status' => 'active',
        ]);

        AuditLogService::log(
            action: 'join_classroom',
            module: 'classroom',
            userId: $student->user_id,
            recordId: $classroom->id,
            payload: ['classroom_code' => $classroomCode]
        );

        return $classroom;
    }
}
