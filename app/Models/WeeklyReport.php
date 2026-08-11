<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'week_number',
        'coverage_start_date',
        'coverage_end_date',
        'activities',
        'problems_encountered',
        'skills_learned',
        'reflections',
        'submitted_at',
        'status',
        'teacher_feedback',
        'reviewed_at',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }
}
