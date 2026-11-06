<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponReservation;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponPricingService
{
    public function quote(string $code, int $userId, int $subtotalPaise, string $productType, int $productId): array
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is invalid, expired, or no longer available.']);
        }

        if ($subtotalPaise < (int) round(((float) $coupon->minimum_amount) * 100)) {
            throw ValidationException::withMessages(['coupon_code' => 'The minimum purchase amount for this coupon has not been met.']);
        }

        $this->assertAppliesTo($coupon, $productType, $productId);

        if ($coupon->per_user_limit !== null
            && $coupon->redemptions()->where('user_id', $userId)->count() >= $coupon->per_user_limit) {
            throw ValidationException::withMessages(['coupon_code' => 'You have already used this coupon the maximum number of times.']);
        }

        return $this->calculate($coupon, $subtotalPaise);
    }

    /**
     * Reserve usage while holding the coupon row lock. Call inside the transaction
     * that creates the pending payment. A reservation prevents concurrent orders
     * from exceeding global or per-user limits before a payment is captured.
     */
    public function reserveForPayment(string $code, Payment $payment, int $subtotalPaise, string $productType, int $productId): array
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->lockForUpdate()->first();
        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is invalid, expired, or no longer available.']);
        }

        CouponReservation::query()->where('coupon_id', $coupon->id)
            ->where('status', 'reserved')->where('expires_at', '<=', now())
            ->update(['status' => 'released', 'updated_at' => now()]);

        if ($subtotalPaise < (int) round(((float) $coupon->minimum_amount) * 100)) {
            throw ValidationException::withMessages(['coupon_code' => 'The minimum purchase amount for this coupon has not been met.']);
        }
        $this->assertAppliesTo($coupon, $productType, $productId);

        $active = $coupon->reservations()->where('status', 'reserved')->where('expires_at', '>', now());
        $reservedGlobal = (clone $active)->count();
        if ($coupon->usage_limit !== null && $coupon->used_count + $reservedGlobal >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon has reached its usage limit.']);
        }

        if ($coupon->per_user_limit !== null) {
            $redeemed = $coupon->redemptions()->where('user_id', $payment->user_id)->count();
            $reserved = (clone $active)->where('user_id', $payment->user_id)->count();
            if ($redeemed + $reserved >= $coupon->per_user_limit) {
                throw ValidationException::withMessages(['coupon_code' => 'You have already used or reserved this coupon the maximum number of times.']);
            }
        }

        $quote = $this->calculate($coupon, $subtotalPaise);
        CouponReservation::updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'coupon_id' => $coupon->id,
                'user_id' => $payment->user_id,
                'discount_paise' => $quote['discount_paise'],
                'status' => 'reserved',
                'expires_at' => now()->addMinutes(30),
                'redeemed_at' => null,
            ]
        );

        return $quote;
    }

    /** Mark the reservation redeemed in the same transaction as payment capture. */
    public function redeemForPayment(Payment $payment): void
    {
        $reservation = CouponReservation::query()->where('payment_id', $payment->id)->lockForUpdate()->first();
        if (! $reservation || $reservation->status === 'redeemed') {
            return;
        }
        if ($reservation->status !== 'reserved') {
            throw new \RuntimeException('Coupon reservation is not redeemable.');
        }

        $coupon = Coupon::query()->whereKey($reservation->coupon_id)->lockForUpdate()->firstOrFail();
        CouponRedemption::firstOrCreate(
            ['coupon_id' => $coupon->id, 'payment_id' => $payment->id],
            ['user_id' => $reservation->user_id, 'discount_paise' => $reservation->discount_paise]
        );
        $coupon->increment('used_count');
        $reservation->update(['status' => 'redeemed', 'redeemed_at' => now()]);
    }

    public function releaseForPayment(Payment $payment): void
    {
        CouponReservation::query()->where('payment_id', $payment->id)
            ->where('status', 'reserved')
            ->update(['status' => 'released', 'updated_at' => now()]);
    }

    private function assertAppliesTo(Coupon $coupon, string $productType, int $productId): void
    {
        $matches = match ($coupon->applies_to) {
            'all' => true,
            'course' => $productType === 'course' && (int) $coupon->course_id === $productId,
            'test_series' => $productType === 'test_series' && (int) $coupon->test_series_id === $productId,
            default => false,
        };
        if (! $matches) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon does not apply to this item.']);
        }
    }

    private function calculate(Coupon $coupon, int $subtotalPaise): array
    {
        $discount = $coupon->discount_type === 'percentage'
            ? (int) round($subtotalPaise * ((float) $coupon->discount_value) / 100)
            : (int) round(((float) $coupon->discount_value) * 100);

        if ($coupon->maximum_discount !== null) {
            $discount = min($discount, (int) round(((float) $coupon->maximum_discount) * 100));
        }
        $discount = min(max($discount, 0), max($subtotalPaise - 100, 0));
        if ($discount <= 0) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon does not provide a valid discount for this purchase.']);
        }

        return [
            'coupon' => $coupon,
            'subtotal_paise' => $subtotalPaise,
            'discount_paise' => $discount,
            'amount_paise' => $subtotalPaise - $discount,
        ];
    }
}
