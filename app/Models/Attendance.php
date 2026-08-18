<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $appends = [
        'rendered_hours',
    ];

    protected $hidden = [
        'rendered_minutes',
    ];

    protected $fillable = [
        'student_id',
        'classroom_id',
        'date',
        'time_in',
        'time_out',
        'rendered_minutes',
        'status',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'rendered_minutes' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getRenderedHoursAttribute(): float
    {
        return round(((int) $this->rendered_minutes) / 60, 2);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')->whereNull('time_out');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed')->whereNotNull('time_out');
    }
}
