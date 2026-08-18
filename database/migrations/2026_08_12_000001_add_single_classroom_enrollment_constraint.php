<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classroom_students', function (Blueprint $table) {
            $table->unique('student_id', 'classroom_students_student_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('classroom_students', function (Blueprint $table) {
            $table->dropUnique('classroom_students_student_id_unique');
        });
    }
};
