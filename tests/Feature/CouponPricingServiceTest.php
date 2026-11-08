<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\CouponPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CouponPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_amount' => 0,
            'applies_to' => 'all',
            'is_active' => true,
            'used_count' => 0,
        ], $overrides));
    }

    public function test_percentage_coupon_calculates_discount_in_paise(): void
    {
        $this->coupon();

        $quote = app(CouponPricingService::class)->quote(' save10 ', 5, 19900, 'course', 12);

        $this->assertSame(19900, $quote['subtotal_paise']);
        $this->assertSame(1990, $quote['discount_paise']);
        $this->assertSame(17910, $quote['amount_paise']);
    }

    public function test_fixed_coupon_respects_maximum_discount_and_never_makes_total_negative(): void
    {
        $this->coupon([
            'code' => 'FIXED',
            'discount_type' => 'fixed',
            'discount_value' => 500,
            'maximum_discount' => 100,
        ]);

        $quote = app(CouponPricingService::class)->quote('FIXED', 5, 19900, 'course', 12);

        $this->assertSame(10000, $quote['discount_paise']);
        $this->assertSame(9900, $quote['amount_paise']);
    }

    public function test_coupon_rejects_expired_or_inactive_codes(): void
    {
        $this->coupon(['ends_at' => now()->subMinute()]);

        $this->expectException(ValidationException::class);
        app(CouponPricingService::class)->quote('SAVE10', 5, 19900, 'course', 12);
    }

    public function test_coupon_rejects_exhausted_global_usage_limit(): void
    {
        $this->coupon(['usage_limit' => 3, 'used_count' => 3]);

        $this->expectException(ValidationException::class);
        app(CouponPricingService::class)->quote('SAVE10', 5, 19900, 'course', 12);
    }

    public function test_coupon_rejects_purchase_below_minimum(): void
    {
        $this->coupon(['minimum_amount' => 250]);

        $this->expectException(ValidationException::class);
        app(CouponPricingService::class)->quote('SAVE10', 5, 19900, 'course', 12);
    }

    private function payment(): Payment
    {
        $user = User::create([
            'name' => 'Coupon Student',
            'email' => 'coupon-'.uniqid().'@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('StrongPassword!2026'),
            'is_active' => true,
        ]);

        return Payment::create([
            'user_id' => $user->id,
            'receipt' => 'coupon-'.uniqid(),
            'amount_paise' => 17910,
            'currency' => 'INR',
            'status' => 'pending',
        ]);
    }

    public function test_reservation_consumes_global_limit_before_another_order_can_reserve(): void
    {
        $this->coupon(['usage_limit' => 1]);
        $firstPayment = $this->payment();
        $secondPayment = $this->payment();
        $service = app(CouponPricingService::class);

        DB::transaction(fn () => $service->reserveForPayment('SAVE10', $firstPayment, 19900, 'course', 12));

        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $service->reserveForPayment('SAVE10', $secondPayment, 19900, 'course', 12));
    }

    public function test_capture_redemption_is_idempotent_and_increments_usage_once(): void
    {
        $coupon = $this->coupon(['usage_limit' => 2]);
        $payment = $this->payment();
        $service = app(CouponPricingService::class);

        DB::transaction(fn () => $service->reserveForPayment('SAVE10', $payment, 19900, 'course', 12));
        $payment->update(['status' => 'captured']);
        DB::transaction(fn () => $service->redeemForPayment($payment));
        DB::transaction(fn () => $service->redeemForPayment($payment));

        $this->assertDatabaseHas('coupon_reservations', ['payment_id' => $payment->id, 'status' => 'redeemed']);
        $this->assertDatabaseHas('coupon_redemptions', ['coupon_id' => $coupon->id, 'payment_id' => $payment->id]);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseCount('coupon_redemptions', 1);
    }

    public function test_verified_capture_can_redeem_a_previously_released_reservation(): void
    {
        $coupon = $this->coupon();
        $payment = $this->payment();
        $service = app(CouponPricingService::class);

        DB::transaction(fn () => $service->reserveForPayment('SAVE10', $payment, 19900, 'course', 12));
        $service->releaseForPayment($payment);
        $payment->update(['status' => 'captured']);

        DB::transaction(fn () => $service->redeemForPayment($payment));

        $this->assertDatabaseHas('coupon_reservations', ['payment_id' => $payment->id, 'status' => 'redeemed']);
        $this->assertDatabaseHas('coupon_redemptions', ['coupon_id' => $coupon->id, 'payment_id' => $payment->id]);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_failed_payment_can_release_coupon_reservation(): void
    {
        $this->coupon();
        $payment = $this->payment();
        $service = app(CouponPricingService::class);

        DB::transaction(fn () => $service->reserveForPayment('SAVE10', $payment, 19900, 'course', 12));
        $service->releaseForPayment($payment);

        $this->assertDatabaseHas('coupon_reservations', ['payment_id' => $payment->id, 'status' => 'released']);
    }

}
