<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonResource extends Model
{
    protected $table = 'course_lesson_resources';
    protected $fillable = ['lesson_id', 'title', 'title_hi', 'type', 'url', 'file_path', 'mime_type', 'file_size', 'position'];
    public function lesson(): BelongsTo { return $this->belongsTo(CourseLesson::class, 'lesson_id'); }
}
