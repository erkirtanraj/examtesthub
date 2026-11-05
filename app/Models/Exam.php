<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exam extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'instructions', 'duration_minutes',
        'max_attempts', 'starts_at', 'ends_at', 'shuffle_questions',
        'shuffle_options', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)->withPivot(['position', 'marks', 'negative_marks'])->withTimestamps()->orderByPivot('position');
    }
}
