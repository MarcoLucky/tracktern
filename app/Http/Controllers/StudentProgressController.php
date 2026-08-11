<?php

namespace App\Http\Controllers;

use App\Services\RenderedHoursService;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentProgressController extends Controller
{
    protected RenderedHoursService $renderedHoursService;
    protected PdfReportService $pdfReportService;

    public function __construct(RenderedHoursService $renderedHoursService, PdfReportService $pdfReportService)
    {
        $this->renderedHoursService = $renderedHoursService;
        $this->pdfReportService = $pdfReportService;
    }

    /**
     * Get detailed student progress analytics & visual chart data.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $progress = $this->renderedHoursService->calculateStudentProgress($student);

        // Weekly rendered breakdown for charts
        $weeklyBreakdown = $student->attendance()
            ->whereNotNull('time_out')
            ->selectRaw('YEARWEEK(date, 1) as year_week, MIN(date) as start_date, SUM(rendered_minutes) as minutes')
            ->groupBy('year_week')
            ->orderBy('year_week', 'asc')
            ->get()
            ->map(fn ($row) => [
                'week' => 'Week of ' . $row->start_date,
                'rendered_hours' => round($row->minutes / 60, 2),
            ]);

        return response()->json([
            'progress' => $progress,
            'weekly_chart_data' => $weeklyBreakdown,
        ]);
    }

    /**
     * Export DTR Report PDF data.
     */
    public function exportDtr(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $reportData = $this->pdfReportService->generateStudentDtrReport($student);

        return response()->json([
            'message' => 'DTR Report compiled successfully.',
            'report' => $reportData,
        ]);
    }

    /**
     * Export Progress Report PDF data.
     */
    public function exportProgress(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $reportData = $this->pdfReportService->generateStudentProgressReport($student);

        return response()->json([
            'message' => 'Progress Report compiled successfully.',
            'report' => $reportData,
        ]);
    }
}
