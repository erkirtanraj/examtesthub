<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    protected $fillable = [
        'subject_id', 'topic_id', 'created_by', 'type', 'prompt', 'options',
        'correct_answer', 'explanation', 'difficulty', 'language', 'marks',
        'negative_marks', 'status',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answer' => 'array',
            'marks' => 'decimal:2',
            'negative_marks' => 'decimal:2',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class)->withPivot(['position', 'marks', 'negative_marks'])->withTimestamps();
    }
}
