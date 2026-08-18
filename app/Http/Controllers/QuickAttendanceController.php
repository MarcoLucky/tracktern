<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class QuickAttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Rapid Kiosk auto action via 5-digit Student Code.
     */
    public function record(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'intern_id' => 'required|string|size:5',
        ]);

        try {
            $result = $this->attendanceService->quickKioskAutoAttendance(
                internId: $validated['intern_id'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rapid Kiosk Time In via 5-digit Student Code.
     */
    public function timeIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'intern_id' => 'required|string|size:5',
        ]);

        try {
            $result = $this->attendanceService->quickKioskAttendance(
                internId: $validated['intern_id'],
                actionType: 'time-in',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rapid Kiosk Time Out via 5-digit Student Code.
     */
    public function timeOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'intern_id' => 'required|string|size:5',
        ]);

        try {
            $result = $this->attendanceService->quickKioskAttendance(
                internId: $validated['intern_id'],
                actionType: 'time-out',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
