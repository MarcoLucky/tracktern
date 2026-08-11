<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained('attendance')->onDelete('set null'); // Selected DTR entry
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('General');
            $table->timestamp('submitted_at')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'needs_revision'])->default('pending');
            $table->text('teacher_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
