<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseChapter extends Model
{
    protected $fillable = ['section_id', 'title', 'title_hi', 'position'];
    public function section(): BelongsTo { return $this->belongsTo(CourseSection::class, 'section_id'); }
    public function lessons(): HasMany { return $this->hasMany(CourseLesson::class, 'chapter_id')->orderBy('position'); }
}
