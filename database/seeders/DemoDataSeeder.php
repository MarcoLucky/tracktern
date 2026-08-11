<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Course;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $bsitCourse = Course::where('course_code', 'BSIT')->first();

        // Create Demo Teacher
        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@tracktern.edu'],
            [
                'name' => 'Prof. Maria Santos',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
            ]
        );

        $teacher = Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'employee_number' => 'EMP-2026-001',
                'department' => 'College of Information Technology',
            ]
        );

        // Create Demo Classroom
        $classroom = Classroom::firstOrCreate(
            ['classroom_code' => 'CR902'],
            [
                'teacher_id' => $teacher->id,
                'course_id' => $bsitCourse?->id,
                'classroom_name' => 'BSIT 4-A Practicum & Internship',
                'required_hours' => 400,
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->addDays(60),
            ]
        );

        // Create Demo Student 1
        $studentUser1 = User::firstOrCreate(
            ['email' => 'student@tracktern.edu'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]
        );

        $student1 = Student::firstOrCreate(
            ['user_id' => $studentUser1->id],
            [
                'intern_id' => '58492',
                'course_id' => $bsitCourse?->id,
                'company_name' => 'Acme Tech Solutions Inc.',
                'internship_start_date' => Carbon::now()->subDays(30),
                'internship_end_date' => Carbon::now()->addDays(60),
                'target_hours' => 400,
                'contact_number' => '09171234567',
            ]
        );

        if (!$student1->classrooms()->where('classroom_id', $classroom->id)->exists()) {
            $student1->classrooms()->attach($classroom->id, [
                'joined_at' => Carbon::now()->subDays(30),
                'status' => 'active',
            ]);
        }

        // Create Demo Student 2
        $studentUser2 = User::firstOrCreate(
            ['email' => 'student2@tracktern.edu'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]
        );

        $student2 = Student::firstOrCreate(
            ['user_id' => $studentUser2->id],
            [
                'intern_id' => '10293',
                'course_id' => $bsitCourse?->id,
                'company_name' => 'Global Software Innovations',
                'internship_start_date' => Carbon::now()->subDays(30),
                'internship_end_date' => Carbon::now()->addDays(60),
                'target_hours' => 400,
                'contact_number' => '09189876543',
            ]
        );

        if (!$student2->classrooms()->where('classroom_id', $classroom->id)->exists()) {
            $student2->classrooms()->attach($classroom->id, [
                'joined_at' => Carbon::now()->subDays(30),
                'status' => 'active',
            ]);
        }

        // Seed Attendance for Student 1
        $latestAttendance = null;
        for ($i = 10; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            if ($date->isWeekend()) continue;

            $timeIn = (clone $date)->setTime(8, 0, 0);
            $timeOut = (clone $date)->setTime(17, 0, 0);

            $latestAttendance = Attendance::firstOrCreate([
                'student_id' => $student1->id,
                'date' => $date->toDateString(),
            ], [
                'classroom_id' => $classroom->id,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'rendered_minutes' => 540,
                'status' => 'completed',
                'notes' => 'On-site internship duty',
                'ip_address' => '127.0.0.1',
            ]);
        }

        // Seed Sample Task Logs linked to Attendance DTR
        Task::firstOrCreate([
            'student_id' => $student1->id,
            'title' => 'Implemented REST API Endpoints for User Module',
        ], [
            'classroom_id' => $classroom->id,
            'attendance_id' => $latestAttendance?->id,
            'description' => 'Developed controllers, Eloquent models, and form validation for user registration and authentication APIs.',
            'category' => 'Development',
            'submitted_at' => Carbon::now()->subDays(2),
            'status' => 'approved',
            'teacher_feedback' => 'Great work on following Laravel best practices.',
            'reviewed_at' => Carbon::now()->subDay(),
        ]);

        Task::firstOrCreate([
            'student_id' => $student1->id,
            'title' => 'Database Schema Design & Migration Setup',
        ], [
            'classroom_id' => $classroom->id,
            'attendance_id' => $latestAttendance?->id,
            'description' => 'Created database migration scripts for DTR, tasks, and attendance tables.',
            'category' => 'Database',
            'submitted_at' => Carbon::now()->subHours(5),
            'status' => 'pending',
        ]);
    }
}
