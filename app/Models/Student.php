<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'intern_id',
        'course_id',
        'company_name',
        'organization_location',
        'profile_photo_path',
        'internship_start_date',
        'internship_end_date',
        'target_hours',
        'contact_number',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected $casts = [
        'internship_start_date' => 'date',
        'internship_end_date' => 'date',
        'target_hours' => 'integer',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        return Storage::url($this->profile_photo_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_students')
            ->withPivot('joined_at', 'status')
            ->withTimestamps();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function weeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }
}
