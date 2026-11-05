<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    protected $fillable = [
        'chapter_id', 'title', 'title_hi', 'description', 'description_hi', 'type',
        'video_url', 'content', 'content_hi', 'is_preview', 'duration_minutes', 'status', 'position',
    ];
    protected function casts(): array { return ['is_preview' => 'boolean']; }
    public function chapter(): BelongsTo { return $this->belongsTo(CourseChapter::class, 'chapter_id'); }
    public function resources(): HasMany { return $this->hasMany(CourseLessonResource::class, 'lesson_id')->orderBy('position'); }
}
