<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Services\AuditLogService;
use App\Mail\WeeklyReportNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WeeklyReportApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $classroomIds = $teacher->classrooms()->pluck('id')->toArray();

        $query = WeeklyReport::whereIn('classroom_id', $classroomIds)->with(['student.user', 'attachments', 'classroom']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        $reports = $query->orderBy('submitted_at', 'asc')->paginate(15);

        return response()->json($reports);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $report = WeeklyReport::whereHas('classroom', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->with('student.user')->findOrFail($id);

        $report->update([
            'status' => 'approved',
            'teacher_feedback' => $request->input('feedback', 'Weekly report approved by instructor.'),
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'approve_weekly_report',
            module: 'weekly_reports',
            userId: $teacher->user_id,
            recordId: $report->id
        );

        if ($report->student && $report->student->user && $report->student->user->email) {
            try {
                Mail::to($report->student->user->email)->send(new WeeklyReportNotificationMail($report, 'approve'));
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Weekly report approved successfully.',
            'report' => $report,
        ]);
    }

    public function requestRevision(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $validated = $request->validate([
            'feedback' => 'required|string|min:3',
        ]);

        $report = WeeklyReport::whereHas('classroom', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->with('student.user')->findOrFail($id);

        $report->update([
            'status' => 'needs_revision',
            'teacher_feedback' => $validated['feedback'],
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'request_report_revision',
            module: 'weekly_reports',
            userId: $teacher->user_id,
            recordId: $report->id,
            payload: ['feedback' => $validated['feedback']]
        );

        if ($report->student && $report->student->user && $report->student->user->email) {
            try {
                Mail::to($report->student->user->email)->send(new WeeklyReportNotificationMail($report, 'revision'));
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'message' => 'Revision requested for weekly report.',
            'report' => $report,
        ]);
    }
}
