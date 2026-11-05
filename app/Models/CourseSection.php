<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    protected $fillable = ['course_id', 'title', 'title_hi', 'description', 'description_hi', 'position'];
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function chapters(): HasMany { return $this->hasMany(CourseChapter::class, 'section_id')->orderBy('position'); }
}
