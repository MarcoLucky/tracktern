<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
    public function exportClassroom(Request $request, int $classroomId): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $classroom = Classroom::where('teacher_id', $teacher->id)->findOrFail($classroomId);

        $reportData = $this->pdfReportService->generateClassroomReport($classroom);

        return response()->json([
            'message' => 'Classroom Summary Report compiled successfully.',
            'report' => $reportData,
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
