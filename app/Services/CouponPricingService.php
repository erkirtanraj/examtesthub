<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponPricingService
{
    /**
     * Return a server-calculated quote in paise. The caller must persist this
     * exact quote on the payment before creating the payment-provider order.
     */
    public function quote(string $code, int $userId, int $subtotalPaise, string $productType, int $productId): array
    {
        $normalized = strtoupper(trim($code));
        $coupon = Coupon::query()->where('code', $normalized)->first();

        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is invalid, expired, or no longer available.']);
        }

        if ($subtotalPaise < (int) round(((float) $coupon->minimum_amount) * 100)) {
            throw ValidationException::withMessages(['coupon_code' => 'The minimum purchase amount for this coupon has not been met.']);
        }

        $matches = match ($coupon->applies_to) {
            'all' => true,
            'course' => $productType === 'course' && (int) $coupon->course_id === $productId,
            'test_series' => $productType === 'test_series' && (int) $coupon->test_series_id === $productId,
            default => false,
        };

        if (! $matches) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon does not apply to this item.']);
        }

        if ($coupon->per_user_limit !== null
            && $coupon->redemptions()->where('user_id', $userId)->count() >= $coupon->per_user_limit) {
            throw ValidationException::withMessages(['coupon_code' => 'You have already used this coupon the maximum number of times.']);
        }

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
