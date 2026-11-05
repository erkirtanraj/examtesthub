<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestSeries extends Model
{
    protected $table = 'test_series';

    protected $fillable = [
        'title', 'slug', 'description', 'access_type', 'price', 'currency',
        'status', 'starts_at', 'ends_at', 'max_enrollments', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'test_series_exam')
            ->withPivot(['position'])->withTimestamps()->orderByPivot('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TestSeriesEnrollment::class, 'test_series_id');
    }

    public function isAvailable(): bool
    {
        $now = now();
        return $this->status === 'published'
            && (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->ends_at || $this->ends_at->gt($now));
    }
}
