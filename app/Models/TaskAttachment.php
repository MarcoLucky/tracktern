<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'file_path',
        'original_filename',
        'file_type',
        'file_size',
    ];

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
