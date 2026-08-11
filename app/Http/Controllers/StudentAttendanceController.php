<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class StudentAttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * View Student DTR logs.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $query = $student->attendance()->with('classroom');

        if ($request->has('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $records = $query->orderBy('date', 'desc')->paginate(15);

        $activeOpenSession = $student->attendance()->open()->first();

        return response()->json([
            'active_open_session' => $activeOpenSession,
            'attendance_records' => $records,
        ]);
    }

    /**
     * Authenticated Student Time In.
     */
    public function timeIn(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        try {
            $attendance = $this->attendanceService->timeIn(
                student: $student,
                classroomId: $request->input('classroom_id'),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json([
                'message' => 'Time In recorded successfully.',
                'attendance' => $attendance,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Authenticated Student Time Out.
     */
    public function timeOut(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        try {
            $attendance = $this->attendanceService->timeOut(
                student: $student,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json([
                'message' => 'Time Out recorded successfully.',
                'attendance' => $attendance,
                'rendered_minutes' => $attendance->rendered_minutes,
                'rendered_hours' => round($attendance->rendered_minutes / 60, 2),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
