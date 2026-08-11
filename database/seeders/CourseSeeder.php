<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['course_name' => 'Bachelor of Science in Information Technology', 'course_code' => 'BSIT'],
            ['course_name' => 'Bachelor of Science in Computer Science', 'course_code' => 'BSCS'],
            ['course_name' => 'Bachelor of Science in Information Systems', 'course_code' => 'BSIS'],
        ];

        foreach ($courses as $c) {
            Course::firstOrCreate(['course_code' => $c['course_code']], $c);
        }
    }
}
