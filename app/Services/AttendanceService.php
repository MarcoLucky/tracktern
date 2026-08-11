<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Classroom;
use App\Mail\AttendanceNotificationMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

class AttendanceService
{
    /**
     * Authenticated student Time In.
     */
    public function timeIn(Student $student, ?int $classroomId = null, ?string $ipAddress = null, ?string $userAgent = null): Attendance
    {
        $existingOpen = Attendance::where('student_id', $student->id)->open()->first();

        if ($existingOpen) {
            throw new Exception('You already have an active Time In session recorded at ' . $existingOpen->time_in->format('Y-m-d H:i:s') . '. Please Time Out first.');
        }

        if (!$classroomId) {
            $activeClassroom = $student->classrooms()->wherePivot('status', 'active')->first();
            $classroomId = $activeClassroom ? $activeClassroom->id : null;
        }

        $now = Carbon::now();

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'classroom_id' => $classroomId,
            'date' => $now->toDateString(),
            'time_in' => $now,
            'time_out' => null,
            'rendered_minutes' => 0,
            'status' => 'open',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        AuditLogService::log(
            action: 'time_in',
            module: 'attendance',
            userId: $student->user_id,
            recordId: $attendance->id,
            payload: ['time_in' => $now->toIso8601String(), 'classroom_id' => $classroomId],
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );

        if ($student->user && $student->user->email) {
            try {
                Mail::to($student->user->email)->send(new AttendanceNotificationMail($attendance, 'time-in'));
            } catch (\Throwable $e) {}
        }

        return $attendance;
    }

    /**
     * Authenticated student Time Out.
     */
    public function timeOut(Student $student, ?string $ipAddress = null, ?string $userAgent = null): Attendance
    {
        $openAttendance = Attendance::where('student_id', $student->id)->open()->latest('time_in')->first();

        if (!$openAttendance) {
            throw new Exception('No active Time In session found for your account.');
        }

        $now = Carbon::now();
        $renderedMinutes = max(0, (int) $openAttendance->time_in->diffInMinutes($now));

        $openAttendance->update([
            'time_out' => $now,
            'rendered_minutes' => $renderedMinutes,
            'status' => 'completed',
            'ip_address' => $ipAddress ?? $openAttendance->ip_address,
            'user_agent' => $userAgent ?? $openAttendance->user_agent,
        ]);

        AuditLogService::log(
            action: 'time_out',
            module: 'attendance',
            userId: $student->user_id,
            recordId: $openAttendance->id,
            payload: [
                'time_out' => $now->toIso8601String(),
                'rendered_minutes' => $renderedMinutes,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );

        if ($student->user && $student->user->email) {
            try {
                Mail::to($student->user->email)->send(new AttendanceNotificationMail($openAttendance, 'time-out'));
            } catch (\Throwable $e) {}
        }

        return $openAttendance;
    }

    /**
     * Public Kiosk Time In or Time Out via 5-digit Intern ID.
     */
    public function quickKioskAttendance(string $internId, string $actionType, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $code = trim($internId);
        $student = Student::with(['user', 'classrooms'])->where('intern_id', $code)->first();

        if (!$student) {
            throw new Exception('Invalid Intern ID. Account not found.');
        }

        if ($actionType === 'time-in') {
            $attendance = $this->timeIn($student, null, $ipAddress, $userAgent);
            $message = 'Time In successful for ' . $this->maskName($student->user->name);
        } elseif ($actionType === 'time-out') {
            $attendance = $this->timeOut($student, $ipAddress, $userAgent);
            $message = 'Time Out successful for ' . $this->maskName($student->user->name);
        } else {
            throw new Exception('Invalid kiosk action type specified.');
        }

        $renderedHours = round($attendance->rendered_minutes / 60, 2);

        return [
            'success' => true,
            'message' => $message,
            'student_name_masked' => $this->maskName($student->user->name),
            'intern_id' => $student->intern_id,
            'action' => $actionType,
            'timestamp' => Carbon::now()->format('Y-m-d h:i:s A'),
            'rendered_minutes' => $attendance->rendered_minutes,
            'rendered_hours' => $renderedHours,
        ];
    }

    private function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $maskedParts = array_map(function ($part) {
            $len = mb_strlen($part);
            if ($len <= 1) return $part;
            return mb_substr($part, 0, 1) . str_repeat('*', max(1, $len - 1));
        }, $parts);

        return implode(' ', $maskedParts);
    }
}
