<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\Task;
use App\Services\AttendanceService;
use App\Services\RenderedHoursService;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class TrackTernBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_demo_users_and_relations_seeded_properly(): void
    {
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();
        $this->assertNotNull($teacherUser);
        $this->assertTrue($teacherUser->isTeacher());

        $studentUser = User::where('email', 'student@tracktern.edu')->first();
        $this->assertNotNull($studentUser);
        $this->assertTrue($studentUser->isStudent());
        $this->assertEquals('58492', $studentUser->student->intern_id);

        $classroom = Classroom::where('classroom_code', 'CR902')->first();
        $this->assertNotNull($classroom);
    }

    public function test_quick_kiosk_time_in_and_time_out_with_intern_id(): void
    {
        /** @var AttendanceService $attendanceService */
        $attendanceService = app(AttendanceService::class);

        $student = Student::where('intern_id', '10293')->first();
        $this->assertNotNull($student);

        // Kiosk Time In
        $resultIn = $attendanceService->quickKioskAttendance($student->intern_id, 'time-in', '127.0.0.1', 'TestAgent');
        $this->assertTrue($resultIn['success']);
        $this->assertEquals('10293', $resultIn['intern_id']);

        // Assert unclosed session constraint prevents double Time In
        $this->expectException(Exception::class);
        $attendanceService->quickKioskAttendance($student->intern_id, 'time-in');
    }

    public function test_kiosk_time_out_calculates_rendered_minutes(): void
    {
        /** @var AttendanceService $attendanceService */
        $attendanceService = app(AttendanceService::class);

        $student = Student::where('intern_id', '10293')->first();
        $attendanceService->quickKioskAttendance($student->intern_id, 'time-in');

        // Kiosk Time Out
        $resultOut = $attendanceService->quickKioskAttendance($student->intern_id, 'time-out', '127.0.0.1', 'TestAgent');
        $this->assertTrue($resultOut['success']);
        $this->assertGreaterThanOrEqual(0, $resultOut['rendered_minutes']);
    }

    public function test_student_progress_service_calculates_accurate_badge_and_hours(): void
    {
        /** @var RenderedHoursService $progressService */
        $progressService = app(RenderedHoursService::class);

        $student = Student::where('intern_id', '58492')->first();
        $progress = $progressService->calculateStudentProgress($student);

        $this->assertEquals('58492', $progress['intern_id']);
        $this->assertGreaterThan(0, $progress['total_rendered_hours']);
        $this->assertArrayHasKey('status_badge', $progress);
    }

    public function test_task_approval_endpoints(): void
    {
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();
        $task = Task::where('status', 'pending')->first();

        $this->assertNotNull($task);

        $response = $this->actingAs($teacherUser, 'sanctum')
            ->postJson("/api/v1/teacher/tasks/{$task->id}/approve", [
                'feedback' => 'Approved in automated feature test.',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $task->fresh()->status);
    }

    public function test_pdf_report_service_generation(): void
    {
        /** @var PdfReportService $pdfService */
        $pdfService = app(PdfReportService::class);

        $student = Student::where('intern_id', '58492')->first();
        $dtrReport = $pdfService->generateStudentDtrReport($student);

        $this->assertEquals('Student Daily Time Record (DTR) Report', $dtrReport['title']);
        $this->assertNotEmpty($dtrReport['attendance_records']);
    }
}
