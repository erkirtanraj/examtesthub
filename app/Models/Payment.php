<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'test_series_enrollment_id', 'course_enrollment_id', 'user_id', 'provider', 'provider_order_id',
        'provider_payment_id', 'provider_refund_id', 'receipt', 'amount_paise', 'currency', 'status',
        'failure_code', 'failure_description', 'refund_reason', 'verified_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return ['amount_paise' => 'integer', 'verified_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TestSeriesEnrollment::class, 'test_series_enrollment_id');
    }

    public function courseEnrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
