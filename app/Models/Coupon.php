<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'minimum_amount', 'maximum_discount',
        'usage_limit', 'per_user_limit', 'used_count', 'starts_at', 'ends_at',
        'applies_to', 'course_id', 'test_series_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function testSeries(): BelongsTo { return $this->belongsTo(TestSeries::class); }
    public function redemptions(): HasMany { return $this->hasMany(CouponRedemption::class); }\n    public function reservations(): HasMany { return $this->hasMany(CouponReservation::class); }

    public function isCurrentlyValid(): bool
    {
        $now = now();

        return $this->is_active
            && (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->ends_at || $this->ends_at->gte($now))
            && (! $this->usage_limit || $this->used_count < $this->usage_limit);
    }
}
