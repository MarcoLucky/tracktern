<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'intern_id',
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

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')->whereNull('time_out');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed')->whereNotNull('time_out');
    }
}
