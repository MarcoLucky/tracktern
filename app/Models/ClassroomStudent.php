<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomStudent extends Pivot
{
    protected $table = 'classroom_students';

    protected $fillable = [
        'classroom_id',
        'student_id',
        'joined_at',
        'status',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
