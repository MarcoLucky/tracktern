<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Models\ReportAttachment;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentReportController extends Controller
{
    /**
     * List weekly reports for student.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $reports = $student->weeklyReports()
            ->with(['attachments', 'classroom'])
            ->orderBy('week_number', 'desc')
            ->paginate(10);

        return response()->json($reports);
    }

    /**
     * Submit weekly report form.
     */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $validated = $request->validate([
            'classroom_id' => 'nullable|exists:classrooms,id',
            'week_number' => 'required|integer|min:1',
            'coverage_start_date' => 'required|date',
            'coverage_end_date' => 'required|date|after_or_equal:coverage_start_date',
            'activities' => 'required|string',
            'problems_encountered' => 'nullable|string',
            'skills_learned' => 'nullable|string',
            'reflections' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:pdf,docx,png,jpg,jpeg|max:10240',
        ]);

        $report = WeeklyReport::create([
            'student_id' => $student->id,
            'classroom_id' => $validated['classroom_id'] ?? $student->classrooms()->first()?->id,
            'week_number' => $validated['week_number'],
            'coverage_start_date' => $validated['coverage_start_date'],
            'coverage_end_date' => $validated['coverage_end_date'],
            'activities' => $validated['activities'],
            'problems_encountered' => $validated['problems_encountered'] ?? null,
            'skills_learned' => $validated['skills_learned'] ?? null,
            'reflections' => $validated['reflections'] ?? null,
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('report_attachments', 'public');
                ReportAttachment::create([
                    'weekly_report_id' => $report->id,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $report->load(['attachments', 'classroom']);

        AuditLogService::log(
            action: 'submit_weekly_report',
            module: 'weekly_reports',
            userId: $student->user_id,
            recordId: $report->id,
            payload: ['week_number' => $report->week_number]
        );

        return response()->json([
            'message' => 'Weekly report submitted successfully.',
            'report' => $report,
        ], 201);
    }

    /**
     * Show single weekly report details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->user()->student;

        $report = WeeklyReport::with(['attachments', 'classroom.teacher.user'])
            ->where('student_id', $student->id)
            ->findOrFail($id);

        return response()->json($report);
    }
}
