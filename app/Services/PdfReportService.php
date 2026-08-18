<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\Task;
use App\Models\WeeklyReport;

class PdfReportService
{
    protected RenderedHoursService $renderedHoursService;

    public function __construct(RenderedHoursService $renderedHoursService)
    {
        $this->renderedHoursService = $renderedHoursService;
    }

    /**
     * Generate structured data payload for Student DTR PDF report.
     */
    public function generateStudentDtrReport(Student $student, ?int $classroomId = null): array
    {
        $attendanceQuery = Attendance::where('student_id', $student->id)->orderBy('date', 'desc');
        if ($classroomId) {
            $attendanceQuery->where('classroom_id', $classroomId);
        }

        $records = $attendanceQuery->get();
        $progress = $this->renderedHoursService->calculateStudentProgress($student, $classroomId);

        return [
            'title' => 'Student Daily Time Record (DTR) Report',
            'generated_at' => now()->format('F j, Y h:i A'),
            'student' => [
                'name' => $student->user ? $student->user->name : 'N/A',
                'email' => $student->user ? $student->user->email : 'N/A',
                'student_number' => $student->student_number,
                'student_code' => $student->student_code,
                'company_name' => $student->company_name,
                'organization_location' => $student->organization_location,
                'course' => $student->course ? $student->course->course_name : 'N/A',
            ],
            'progress' => $progress,
            'attendance_records' => $records->map(fn ($item) => [
                'date' => $item->date ? $item->date->format('Y-m-d') : 'N/A',
                'time_in' => $item->time_in ? $item->time_in->format('h:i:s A') : 'N/A',
                'time_out' => $item->time_out ? $item->time_out->format('h:i:s A') : 'Pending',
                'rendered_hours' => $item->rendered_hours,
                'status' => $item->status,
                'notes' => $item->notes,
            ]),
        ];
    }

    /**
     * Generate structured data payload for Student Progress PDF report.
     */
    public function generateStudentProgressReport(Student $student, ?int $classroomId = null): array
    {
        $progress = $this->renderedHoursService->calculateStudentProgress($student, $classroomId);
        $tasks = Task::where('student_id', $student->id)->orderBy('created_at', 'desc')->get();
        $weeklyReports = WeeklyReport::where('student_id', $student->id)->orderBy('week_number', 'desc')->get();

        return [
            'title' => 'Student Internship Progress & Performance Report',
            'generated_at' => now()->format('F j, Y h:i A'),
            'student' => [
                'name' => $student->user ? $student->user->name : 'N/A',
                'email' => $student->user ? $student->user->email : 'N/A',
                'student_number' => $student->student_number,
                'company_name' => $student->company_name,
                'organization_location' => $student->organization_location,
            ],
            'progress' => $progress,
            'tasks_summary' => $tasks->map(fn ($t) => [
                'title' => $t->title,
                'category' => $t->category,
                'status' => $t->status,
                'submitted_at' => $t->submitted_at ? $t->submitted_at->format('Y-m-d') : 'N/A',
                'feedback' => $t->teacher_feedback,
            ]),
            'weekly_reports_summary' => $weeklyReports->map(fn ($r) => [
                'week_number' => $r->week_number,
                'coverage' => ($r->coverage_start_date ? $r->coverage_start_date->format('M d, Y') : '') . ' - ' . ($r->coverage_end_date ? $r->coverage_end_date->format('M d, Y') : ''),
                'status' => $r->status,
                'feedback' => $r->teacher_feedback,
            ]),
        ];
    }

    /**
     * Generate class summary report for Teacher.
     */
    public function generateClassroomReport(Classroom $classroom): array
    {
        $classroom->load(['teacher.user', 'course', 'students.user']);

        $studentsProgress = $classroom->students->map(function ($student) use ($classroom) {
            return $this->renderedHoursService->calculateStudentProgress($student, $classroom->id);
        });

        return [
            'title' => 'Classroom Internship Performance Report',
            'generated_at' => now()->format('F j, Y h:i A'),
            'classroom' => [
                'name' => $classroom->classroom_name,
                'code' => $classroom->classroom_code,
                'teacher' => $classroom->teacher && $classroom->teacher->user ? $classroom->teacher->user->name : 'N/A',
                'course' => $classroom->course ? $classroom->course->course_name : 'N/A',
                'required_hours' => $classroom->required_hours,
            ],
            'total_students' => $studentsProgress->count(),
            'students' => $studentsProgress,
        ];
    }

    /**
     * Generate a downloadable classroom summary PDF.
     */
    public function generateClassroomSummaryPdf(Classroom $classroom): string
    {
        $report = $this->generateClassroomReport($classroom);
        $classroomInfo = $report['classroom'];
        $lines = [
            'Generated: ' . $report['generated_at'],
            'Classroom: ' . $classroomInfo['name'],
            'Invitation Code: ' . $classroomInfo['code'],
            'Teacher: ' . $classroomInfo['teacher'],
            'Course: ' . $classroomInfo['course'],
            'Required Hours: ' . $classroomInfo['required_hours'],
            'Total Students: ' . $report['total_students'],
            '',
            'Student Summary',
            str_repeat('-', 72),
        ];

        if ($report['students']->isEmpty()) {
            $lines[] = 'No students enrolled in this classroom.';
        }

        foreach ($report['students'] as $index => $student) {
            $lines[] = ($index + 1) . '. ' . $student['student_name'] . ' (' . $student['intern_id'] . ')';
            $lines[] = '   Company: ' . ($student['company_name'] ?? 'N/A');
            $lines[] = '   Location: ' . ($student['organization_location'] ?? 'N/A');
            $lines[] = '   Required Hours: ' . $student['required_target_hours'];
            $lines[] = '   Rendered Hours: ' . $student['total_rendered_hours'];
            $lines[] = '   Remaining Hours: ' . $student['remaining_hours'];
            $lines[] = '   Progress: ' . $student['progress_percentage'] . '%';
            $lines[] = '   Status: ' . $student['status_badge'];
            $lines[] = '';
        }

        return $this->buildTextPdf($report['title'], $lines);
    }

    private function buildTextPdf(string $title, array $lines): string
    {
        $wrappedLines = [];
        foreach ($lines as $line) {
            $line = $this->normalizePdfText((string) $line);
            if ($line === '') {
                $wrappedLines[] = '';
                continue;
            }

            foreach (explode("\n", wordwrap($line, 96, "\n", true)) as $wrappedLine) {
                $wrappedLines[] = $wrappedLine;
            }
        }

        $pages = array_chunk($wrappedLines, 47);
        if (!$pages) {
            $pages = [[]];
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>',
        ];

        $pageIds = [];
        $nextObjectId = 5;

        foreach ($pages as $pageIndex => $pageLines) {
            $content = "BT\n/F2 15 Tf\n50 760 Td\n";
            $content .= '(' . $this->escapePdfText($title . ' - Page ' . ($pageIndex + 1)) . ") Tj\n";
            $content .= "/F1 9 Tf\n0 -24 Td\n";

            foreach ($pageLines as $line) {
                $content .= '(' . $this->escapePdfText($line) . ") Tj\n0 -13 Td\n";
            }

            $content .= "ET\n";

            $contentObjectId = $nextObjectId++;
            $pageObjectId = $nextObjectId++;

            $objects[$contentObjectId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
            $objects[$pageObjectId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentObjectId} 0 R >>";
            $pageIds[] = $pageObjectId;
        }

        $objects[2] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [' . implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageIds)) . '] >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $maxObjectId = max(array_keys($objects));

        for ($id = 1; $id <= $maxObjectId; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$objects[$id]}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= $maxObjectId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function normalizePdfText(string $value): string
    {
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $converted = $converted === false ? $value : $converted;

        return preg_replace('/[^\x20-\x7E]/', '', $converted) ?? '';
    }

    private function escapePdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalizePdfText($value));
    }
}
