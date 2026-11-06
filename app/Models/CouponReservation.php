<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class CouponReservation extends Model
{
    protected $fillable = ['coupon_id', 'payment_id', 'user_id', 'discount_paise', 'status', 'expires_at', 'redeemed_at'];

    protected function casts(): array
    {
        return ['discount_paise' => 'integer', 'expires_at' => 'datetime', 'redeemed_at' => 'datetime'];
    }

    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
