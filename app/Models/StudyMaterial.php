<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterial extends Model
{
    protected $fillable = [
        'title', 'title_hi', 'description', 'description_hi',
        'subject_id', 'course_id', 'language', 'status',
        'file_path', 'original_name', 'mime_type', 'file_size',
        'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
