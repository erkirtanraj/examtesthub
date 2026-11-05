<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title', 'title_hi', 'slug', 'description', 'description_hi', 'category',
        'access_type', 'price', 'currency', 'status', 'published_at',
        'starts_at', 'ends_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'published_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sections(): HasMany { return $this->hasMany(CourseSection::class)->orderBy('position'); }
    public function enrollments(): HasMany { return $this->hasMany(CourseEnrollment::class); }

    public function isAvailable(): bool
    {
        $now = now();
        return $this->status === 'published'
            && (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->ends_at || $this->ends_at->gt($now));
    }
}
