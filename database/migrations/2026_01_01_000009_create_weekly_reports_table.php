<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->onDelete('cascade');
            $table->integer('week_number');
            $table->date('coverage_start_date');
            $table->date('coverage_end_date');
            $table->text('activities');
            $table->text('problems_encountered')->nullable();
            $table->text('skills_learned')->nullable();
            $table->text('reflections')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'needs_revision'])->default('pending');
            $table->text('teacher_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
