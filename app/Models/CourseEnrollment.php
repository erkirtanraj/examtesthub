<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEnrollment extends Model
{
    protected $fillable = ['course_id', 'user_id', 'status', 'amount_paid', 'currency', 'payment_reference', 'enrolled_at', 'completed_at'];
    protected function casts(): array { return ['amount_paid' => 'decimal:2', 'enrolled_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function progress(): HasMany { return $this->hasMany(CourseLessonProgress::class, 'course_enrollment_id'); }
}
