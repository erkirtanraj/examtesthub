<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonProgress extends Model
{
    protected $table = 'course_lesson_progress';
    protected $fillable = ['course_enrollment_id', 'lesson_id', 'status', 'progress_seconds', 'started_at', 'completed_at'];
    protected function casts(): array { return ['started_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function enrollment(): BelongsTo { return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id'); }
    public function lesson(): BelongsTo { return $this->belongsTo(CourseLesson::class, 'lesson_id'); }
}
