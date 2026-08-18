<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeacherReportController extends Controller
{
    protected PdfReportService $pdfReportService;

    public function __construct(PdfReportService $pdfReportService)
    {
        $this->pdfReportService = $pdfReportService;
    }

    /**
     * Export Classroom Summary Report payload.
     */
    public function exportClassroom(Request $request, int $classroomId): Response
    {
        $teacher = $request->user()->teacher;

        $classroom = Classroom::where('teacher_id', $teacher->id)->findOrFail($classroomId);
        $pdf = $this->pdfReportService->generateClassroomSummaryPdf($classroom);
        $filename = 'classroom-summary-' . strtolower($classroom->classroom_code) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export Individual Student Dossier payload for teacher.
     */
    public function exportStudentDossier(Request $request, int $studentId): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $student = Student::whereHas('classrooms', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->findOrFail($studentId);

        $dtrReport = $this->pdfReportService->generateStudentDtrReport($student);
        $progressReport = $this->pdfReportService->generateStudentProgressReport($student);

        return response()->json([
            'message' => 'Student Dossier compiled successfully.',
            'dtr_report' => $dtrReport,
            'progress_report' => $progressReport,
        ]);
    }
}
