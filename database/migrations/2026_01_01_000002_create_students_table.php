<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('intern_id', 5)->unique(); // 5-digit numeric Intern ID
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->string('company_name')->nullable(); // Host Training Establishment
            $table->date('internship_start_date')->nullable();
            $table->date('internship_end_date')->nullable();
            $table->integer('target_hours')->default(400);
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
