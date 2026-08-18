<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\Task;
use App\Services\AttendanceService;
use App\Services\RenderedHoursService;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_student_must_leave_current_classroom_before_joining_another(): void
    {
        $studentUser = User::where('email', 'student@tracktern.edu')->first();
        $student = $studentUser->student;
        $teacher = Teacher::first();

        $secondClassroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'course_id' => $student->course_id,
            'classroom_name' => 'BSIT 4-B Practicum & Internship',
            'classroom_code' => 'AB123',
            'required_hours' => 400,
            'semester' => '2nd Semester',
            'academic_year' => '2025-2026',
        ]);

        $joinWhileEnrolled = $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/student/classroom/join', [
                'classroom_code' => $secondClassroom->classroom_code,
            ]);

        $joinWhileEnrolled->assertStatus(422)
            ->assertJsonPath('message', 'You are already enrolled in a classroom. Leave your current class before joining another.');

        $currentClassroomId = $student->classrooms()->first()->id;
        $leaveResponse = $this->actingAs($studentUser, 'sanctum')
            ->deleteJson("/api/v1/student/classroom/{$currentClassroomId}/leave");

        $leaveResponse->assertStatus(200);

        $joinAfterLeaving = $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/student/classroom/join', [
                'classroom_code' => $secondClassroom->classroom_code,
            ]);

        $joinAfterLeaving->assertStatus(200);
        $this->assertEquals(1, $student->fresh()->classrooms()->count());
        $this->assertTrue($student->fresh()->classrooms()->where('classroom_id', $secondClassroom->id)->exists());
    }

    public function test_student_can_submit_task_with_attachment(): void
    {
        Storage::fake('public');

        $studentUser = User::where('email', 'student@tracktern.edu')->first();
        $attendance = $studentUser->student->attendance()->first();

        $response = $this->actingAs($studentUser, 'sanctum')
            ->post('/api/v1/student/tasks', [
                'attendance_id' => $attendance->id,
                'title' => 'Submitted UI Proof',
                'category' => 'Documentation',
                'description' => 'Uploaded proof of the completed interface work.',
                'attachments' => [
                    UploadedFile::fake()->create('task-proof.pdf', 128, 'application/pdf'),
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('task.attachments.0.original_filename', 'task-proof.pdf');

        $task = Task::where('title', 'Submitted UI Proof')->first();
        $attachment = $task->attachments()->first();

        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_student_dashboard_reports_daily_task_submission_count(): void
    {
        $studentUser = User::where('email', 'student@tracktern.edu')->first();
        $student = $studentUser->student;
        $attendance = $student->attendance()->first();

        Task::where('student_id', $student->id)->delete();

        Task::create([
            'student_id' => $student->id,
            'classroom_id' => $attendance->classroom_id,
            'attendance_id' => $attendance->id,
            'title' => 'Today Task A',
            'description' => 'Submitted today.',
            'category' => 'General',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        Task::create([
            'student_id' => $student->id,
            'classroom_id' => $attendance->classroom_id,
            'attendance_id' => $attendance->id,
            'title' => 'Today Task B',
            'description' => 'Submitted today too.',
            'category' => 'General',
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        Task::create([
            'student_id' => $student->id,
            'classroom_id' => $attendance->classroom_id,
            'attendance_id' => $attendance->id,
            'title' => 'Older Task',
            'description' => 'Submitted yesterday.',
            'category' => 'General',
            'submitted_at' => now()->subDay(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/student/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('summary.daily_tasks_submitted', 2);
    }

    public function test_teacher_can_update_profile_details(): void
    {
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();

        $response = $this->actingAs($teacherUser, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Prof. Maria Reyes',
                'email' => 'maria.reyes@tracktern.edu',
                'contact_number' => '09175551234',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Prof. Maria Reyes')
            ->assertJsonPath('user.email', 'maria.reyes@tracktern.edu')
            ->assertJsonPath('user.teacher.contact_number', '09175551234');

        $this->assertDatabaseHas('teachers', [
            'user_id' => $teacherUser->id,
            'contact_number' => '09175551234',
        ]);
    }

    public function test_teacher_can_unenroll_student_from_classroom(): void
    {
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();
        $classroom = Classroom::where('classroom_code', 'CR902')->first();
        $student = $classroom->students()->first();

        $response = $this->actingAs($teacherUser, 'sanctum')
            ->deleteJson("/api/v1/teacher/classrooms/{$classroom->id}/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Student unenrolled from classroom successfully.');

        $this->assertDatabaseMissing('classroom_students', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_student_can_update_organization_location(): void
    {
        $studentUser = User::where('email', 'student@tracktern.edu')->first();

        $response = $this->actingAs($studentUser, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => $studentUser->name,
                'contact_number' => '09179998888',
                'company_name' => 'Acme Tech Solutions Inc.',
                'organization_location' => 'Taguig City',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.student.organization_location', 'Taguig City');

        $this->assertDatabaseHas('students', [
            'user_id' => $studentUser->id,
            'organization_location' => 'Taguig City',
        ]);
    }

    public function test_student_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $studentUser = User::where('email', 'student@tracktern.edu')->first();

        $response = $this->actingAs($studentUser, 'sanctum')
            ->post('/api/v1/auth/profile/photo', [
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 120, 120),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.student.profile_photo_path', fn ($path) => str_starts_with($path, 'profile_photos/'))
            ->assertJsonPath('user.student.profile_photo_url', fn ($url) => str_contains($url, '/storage/profile_photos/'));

        Storage::disk('public')->assertExists($studentUser->fresh()->student->profile_photo_path);
    }

    public function test_student_classroom_information_includes_teacher_phone(): void
    {
        $studentUser = User::where('email', 'student@tracktern.edu')->first();
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();
        $teacherUser->teacher->update(['contact_number' => '09170000001']);

        $response = $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/student/classroom');

        $response->assertStatus(200)
            ->assertJsonPath('0.teacher.contact_number', '09170000001');
    }

    public function test_teacher_can_download_classroom_summary_pdf(): void
    {
        $teacherUser = User::where('email', 'teacher@tracktern.edu')->first();
        $classroom = Classroom::where('classroom_code', 'CR902')->first();

        $response = $this->actingAs($teacherUser, 'sanctum')
            ->get("/api/v1/teacher/reports/classroom/{$classroom->id}/export");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('Acme Tech Solutions Inc.', $response->getContent());
        $this->assertStringContainsString('Makati City', $response->getContent());
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
