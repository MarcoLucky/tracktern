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
                'course' => $student->course ? $student->course->course_name : 'N/A',
            ],
            'progress' => $progress,
            'attendance_records' => $records->map(fn ($item) => [
                'date' => $item->date ? $item->date->format('Y-m-d') : 'N/A',
                'time_in' => $item->time_in ? $item->time_in->format('h:i:s A') : 'N/A',
                'time_out' => $item->time_out ? $item->time_out->format('h:i:s A') : 'Pending',
                'rendered_minutes' => $item->rendered_minutes,
                'rendered_hours' => round($item->rendered_minutes / 60, 2),
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
}
