<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
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
            $classroomId = (int) $request->input('classroom_id');
            if (!$student->classrooms()->where('classroom_id', $classroomId)->exists()) {
                return response()->json(['message' => 'Selected classroom does not belong to your account.'], 422);
            }

            $query->where('classroom_id', $classroomId);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $records = $query->orderByDesc('time_in')->paginate($perPage);

        // Normalize paginator items for frontend (simple date/time strings)
        $records->getCollection()->transform(function ($r) {
            return [
                'id' => $r->id,
                'date' => $r->date ? $r->date->toDateString() : null,
                'time_in' => $r->time_in ? $r->time_in->toDateTimeString() : null,
                'time_out' => $r->time_out ? $r->time_out->toDateTimeString() : null,
                'rendered_hours' => $r->rendered_hours,
                'status' => $r->status,
                'classroom' => $r->classroom ? [
                    'id' => $r->classroom->id,
                    'classroom_name' => $r->classroom->classroom_name,
                ] : null,
            ];
        });

        $activeOpenSession = $student->attendance()->open()->first();
        if ($activeOpenSession) {
            $activeOpenSession = [
                'id' => $activeOpenSession->id,
                'date' => $activeOpenSession->date ? $activeOpenSession->date->toDateString() : null,
                'time_in' => $activeOpenSession->time_in ? $activeOpenSession->time_in->toDateTimeString() : null,
                'time_out' => $activeOpenSession->time_out ? $activeOpenSession->time_out->toDateTimeString() : null,
                'rendered_hours' => $activeOpenSession->rendered_hours,
                'status' => $activeOpenSession->status,
            ];
        }

        return response()->json([
            'active_open_session' => $activeOpenSession,
            'attendance_records' => $records,
        ]);
    }

    /**
     * Student DTR calendar data for selected month.
     */
    public function calendar(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $monthQuery = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $monthQuery)->startOfMonth();
        } catch (\Exception $e) {
            $monthStart = Carbon::now()->startOfMonth();
        }

        $monthEnd = $monthStart->copy()->endOfMonth();
        $classroomId = null;

        if ($request->filled('classroom_id')) {
            $classroomId = (int) $request->input('classroom_id');
            if (!$student->classrooms()->where('classroom_id', $classroomId)->exists()) {
                return response()->json(['message' => 'Selected classroom does not belong to your account.'], 422);
            }
        }

        $records = $student->attendance()
            ->with('classroom')
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->when($classroomId, function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->orderBy('date', 'asc')
            ->get();

        // Normalize fields for frontend: simple date (Y-m-d) and readable time strings without microseconds/Z
        $normalized = $records->map(function ($r) {
            return [
                'id' => $r->id,
                'date' => $r->date ? $r->date->toDateString() : null,
                'time_in' => $r->time_in ? $r->time_in->toDateTimeString() : null,
                'time_out' => $r->time_out ? $r->time_out->toDateTimeString() : null,
                'rendered_hours' => $r->rendered_hours,
                'status' => $r->status,
                'classroom' => $r->classroom ? [
                    'id' => $r->classroom->id,
                    'classroom_name' => $r->classroom->classroom_name,
                ] : null,
            ];
        });

        return response()->json([
            'month' => $monthStart->format('Y-m'),
            'month_label' => $monthStart->format('F Y'),
            'attendance_records' => $normalized,
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
                'rendered_hours' => $attendance->rendered_hours,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
