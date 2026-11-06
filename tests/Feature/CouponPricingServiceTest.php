<?php

namespace Tests\Feature;

use App\Models\Coupon;
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
}
