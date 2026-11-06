<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Role;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config([
            'services.razorpay.key_id' => 'rzp_test_key',
            'services.razorpay.key_secret' => 'test_secret',
            'services.razorpay.webhook_secret' => 'webhook_secret',
            'services.razorpay.base_url' => 'https://api.razorpay.com/v1',
        ]);
    }

    private function user(string $role, string $email): User
    {
        $r = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $user = User::create([
            'name' => 'Test Student', 'email' => $email,
            'password' => Hash::make('StrongPassword!2026'), 'is_active' => true,
        ]);
        $user->roles()->attach($r);

        return $user;
    }

    private function paidSeries(User $admin): TestSeries
    {
        return TestSeries::create([
            'title' => 'Paid Practice Series', 'slug' => 'paid-practice-'.uniqid(),
            'description' => 'Paid exam package', 'access_type' => 'paid',
            'price' => 199, 'currency' => 'INR', 'status' => 'published',
            'created_by' => $admin->id,
        ]);
    }

    private function paymentFor(User $student, TestSeries $series, string $status = 'pending', ?string $orderId = null): Payment
    {
        $enrollment = TestSeriesEnrollment::create([
            'test_series_id' => $series->id, 'user_id' => $student->id,
            'status' => 'pending_payment', 'currency' => 'INR',
        ]);

        return Payment::create([
            'test_series_enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'provider_order_id' => $orderId ?? 'order_test_123',
            'receipt' => 'receipt_'.uniqid(),
            'amount_paise' => 19900,
            'currency' => 'INR',
            'status' => $status,
        ]);
    }

    public function test_student_can_create_a_server_side_razorpay_order(): void
    {
        $admin = $this->user('super-admin', 'pay-admin1@example.test');
        $student = $this->user('student', 'pay-student1@example.test');
        $series = $this->paidSeries($admin);
        Http::fake(['https://api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_created_123', 'amount' => 19900, 'currency' => 'INR',
        ], 200)]);

        $this->actingAs($student)->post('/student/test-series/'.$series->id.'/checkout')
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id, 'provider_order_id' => 'order_created_123',
            'amount_paise' => 19900, 'currency' => 'INR', 'status' => 'pending',
        ]);
        $this->assertDatabaseHas('test_series_enrollments', [
            'test_series_id' => $series->id, 'user_id' => $student->id, 'status' => 'pending_payment',
        ]);
    }

    public function test_coupon_is_reserved_and_discounted_amount_is_sent_to_razorpay(): void
    {
        $admin = $this->user('super-admin', 'coupon-admin@example.test');
        $student = $this->user('student', 'coupon-student@example.test');
        $series = $this->paidSeries($admin);
        \App\Models\Coupon::create([
            'code' => 'SAVE20', 'discount_type' => 'percentage', 'discount_value' => 20,
            'minimum_amount' => 0, 'usage_limit' => 10, 'per_user_limit' => 1,
            'used_count' => 0, 'applies_to' => 'test_series', 'test_series_id' => $series->id,
            'is_active' => true,
        ]);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_coupon_123', 'amount' => 15920, 'currency' => 'INR',
            ], 200),
            'https://api.razorpay.com/v1/payments/pay_coupon_123' => Http::response([
                'id' => 'pay_coupon_123', 'order_id' => 'order_coupon_123',
                'amount' => 15920, 'currency' => 'INR', 'status' => 'captured',
            ], 200),
        ]);

        $this->actingAs($student)->post('/student/test-series/'.$series->id.'/checkout', [
            'coupon_code' => 'save20',
        ])->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.razorpay.com/v1/orders'
            && $request['amount'] === 15920);
        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id, 'coupon_code' => 'SAVE20',
            'subtotal_paise' => 19900, 'discount_paise' => 3980,
            'amount_paise' => 15920, 'status' => 'pending',
        ]);
        $this->assertDatabaseHas('coupon_reservations', [
            'user_id' => $student->id, 'status' => 'reserved', 'discount_paise' => 3980,
        ]);

        $payment = Payment::where('provider_order_id', 'order_coupon_123')->firstOrFail();
        $signature = hash_hmac('sha256', 'order_coupon_123|pay_coupon_123', 'test_secret');
        $this->actingAs($student)->post('/student/payments/'.$payment->id.'/verify', [
            'razorpay_order_id' => 'order_coupon_123',
            'razorpay_payment_id' => 'pay_coupon_123',
            'razorpay_signature' => $signature,
        ])->assertRedirect('/student/payments');

        $this->assertDatabaseHas('coupon_reservations', [
            'payment_id' => $payment->id, 'status' => 'redeemed',
        ]);
        $this->assertDatabaseHas('coupon_redemptions', [
            'payment_id' => $payment->id, 'user_id' => $student->id, 'discount_paise' => 3980,
        ]);
        $this->assertDatabaseHas('coupons', ['code' => 'SAVE20', 'used_count' => 1]);
    }

    public function test_checkout_retry_reuses_the_existing_pending_razorpay_order(): void
    {
        $admin = $this->user('super-admin', 'pay-admin-retry@example.test');
        $student = $this->user('student', 'pay-student-retry@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series);

        Http::fake();

        $this->actingAs($student)
            ->post('/student/test-series/'.$series->id.'/checkout')
            ->assertRedirect('/student/payments/'.$payment->id.'/checkout');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'provider_order_id' => 'order_test_123',
            'status' => 'pending',
        ]);
    }

    public function test_valid_checkout_signature_and_captured_provider_payment_activate_enrollment(): void
    {
        $admin = $this->user('super-admin', 'pay-admin2@example.test');
        $student = $this->user('student', 'pay-student2@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series);
        Http::fake(['https://api.razorpay.com/v1/payments/pay_test_123' => Http::response([
            'id' => 'pay_test_123', 'order_id' => 'order_test_123',
            'amount' => 19900, 'currency' => 'INR', 'status' => 'captured',
        ], 200)]);
        $signature = hash_hmac('sha256', 'order_test_123|pay_test_123', 'test_secret');

        $this->actingAs($student)->post('/student/payments/'.$payment->id.'/verify', [
            'razorpay_order_id' => 'order_test_123',
            'razorpay_payment_id' => 'pay_test_123',
            'razorpay_signature' => $signature,
        ])->assertRedirect('/student/payments');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'captured', 'provider_payment_id' => 'pay_test_123']);
        $this->assertDatabaseHas('test_series_enrollments', [
            'id' => $payment->test_series_enrollment_id, 'status' => 'active',
            'payment_reference' => 'pay_test_123',
        ]);
    }

    public function test_invalid_checkout_signature_does_not_activate_enrollment(): void
    {
        $admin = $this->user('super-admin', 'pay-admin3@example.test');
        $student = $this->user('student', 'pay-student3@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series);

        $this->actingAs($student)->post('/student/payments/'.$payment->id.'/verify', [
            'razorpay_order_id' => 'order_test_123',
            'razorpay_payment_id' => 'pay_test_bad',
            'razorpay_signature' => str_repeat('a', 64),
        ])->assertUnprocessable();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('test_series_enrollments', ['id' => $payment->test_series_enrollment_id, 'status' => 'pending_payment']);
    }

    public function test_signed_captured_webhook_activates_enrollment_and_duplicate_event_is_idempotent(): void
    {
        $admin = $this->user('super-admin', 'pay-admin4@example.test');
        $student = $this->user('student', 'pay-student4@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series);
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_webhook_123', 'order_id' => 'order_test_123',
                'amount' => 19900, 'currency' => 'INR', 'status' => 'captured',
            ]]],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'webhook_secret');
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_capture_123',
        ];

        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();
        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'captured', 'provider_payment_id' => 'pay_webhook_123']);
        $this->assertDatabaseHas('test_series_enrollments', ['id' => $payment->test_series_enrollment_id, 'status' => 'active']);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_webhook_amount_mismatch_never_activates_enrollment(): void
    {
        $admin = $this->user('super-admin', 'pay-admin5@example.test');
        $student = $this->user('student', 'pay-student5@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series);
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_wrong_amount', 'order_id' => 'order_test_123',
                'amount' => 1, 'currency' => 'INR',
            ]]],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'webhook_secret'),
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_wrong_amount',
        ];

        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('test_series_enrollments', ['id' => $payment->test_series_enrollment_id, 'status' => 'pending_payment']);
    }
    public function test_admin_can_request_a_full_refund_and_processed_response_revokes_access(): void
    {
        $admin = $this->user('super-admin', 'refund-admin@example.test');
        $student = $this->user('student', 'refund-student@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series, 'captured');
        $payment->update(['provider_payment_id' => 'pay_refund_123', 'verified_at' => now()]);
        $payment->enrollment()->update(['status' => 'active']);

        Http::fake(['https://api.razorpay.com/v1/payments/pay_refund_123/refund' => Http::response([
            'id' => 'rfnd_test_123', 'payment_id' => 'pay_refund_123',
            'amount' => 19900, 'status' => 'processed',
        ], 200)]);

        $this->actingAs($admin)->from('/admin/payments')->post('/admin/payments/'.$payment->id.'/refund', [
            'reason' => 'Duplicate payment reported by customer',
        ])->assertRedirect('/admin/payments');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id, 'status' => 'refunded',
            'provider_refund_id' => 'rfnd_test_123',
            'refund_reason' => 'Duplicate payment reported by customer',
        ]);
        $this->assertDatabaseHas('test_series_enrollments', [
            'id' => $payment->test_series_enrollment_id, 'status' => 'refunded',
        ]);
    }

    public function test_duplicate_payment_captured_webhook_cannot_reverse_a_pending_refund(): void
    {
        $admin = $this->user('super-admin', 'refund-race-admin@example.test');
        $student = $this->user('student', 'refund-race-student@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series, 'refund_pending');
        $payment->update([
            'provider_payment_id' => 'pay_refund_race',
            'provider_refund_id' => 'rfnd_refund_race',
        ]);
        $payment->enrollment()->update(['status' => 'active']);

        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_refund_race',
                'order_id' => $payment->provider_order_id,
                'amount' => $payment->amount_paise,
                'currency' => $payment->currency,
                'status' => 'captured',
            ]]],
        ], JSON_THROW_ON_ERROR);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'webhook_secret'),
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_capture_during_refund',
        ];

        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refund_pending']);
        $this->assertDatabaseHas('test_series_enrollments', [
            'id' => $payment->test_series_enrollment_id,
            'status' => 'active',
        ]);
    }

    public function test_refund_processed_webhook_is_signed_idempotent_and_revokes_access(): void
    {
        $admin = $this->user('super-admin', 'refund-webhook-admin@example.test');
        $student = $this->user('student', 'refund-webhook-student@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series, 'refund_pending');
        $payment->update(['provider_payment_id' => 'pay_refund_webhook', 'provider_refund_id' => 'rfnd_webhook_123']);
        $payment->enrollment()->update(['status' => 'active']);
        $body = json_encode([
            'event' => 'refund.processed',
            'payload' => ['refund' => ['entity' => [
                'id' => 'rfnd_webhook_123', 'payment_id' => 'pay_refund_webhook',
                'amount' => 19900, 'status' => 'processed',
            ]]],
        ], JSON_THROW_ON_ERROR);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'webhook_secret'),
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_refund_processed_123',
        ];

        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();
        $this->call('POST', '/api/v1/payments/razorpay/webhook', [], [], [], $server, $body)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('test_series_enrollments', ['id' => $payment->test_series_enrollment_id, 'status' => 'refunded']);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_student_can_view_invoice_only_for_own_captured_payment(): void
    {
        $admin = $this->user('super-admin', 'invoice-admin@example.test');
        $student = $this->user('student', 'invoice-owner@example.test');
        $other = $this->user('student', 'invoice-other@example.test');
        $series = $this->paidSeries($admin);
        $payment = $this->paymentFor($student, $series, 'captured');
        $payment->update(['provider_payment_id' => 'pay_invoice_123', 'verified_at' => now()]);

        $this->actingAs($student)->get('/student/payments/'.$payment->id.'/invoice')->assertOk();
        $this->actingAs($other)->get('/student/payments/'.$payment->id.'/invoice')->assertNotFound();
    }

    public function test_admin_payment_dashboard_exposes_revenue_and_refund_totals(): void
    {
        $admin = $this->user('super-admin', 'revenue-admin@example.test');
        $student = $this->user('student', 'revenue-student@example.test');
        $studentTwo = $this->user('student', 'revenue-student-two@example.test');
        $series = $this->paidSeries($admin);
        $captured = $this->paymentFor($student, $series, 'captured');
        $captured->update(['provider_payment_id' => 'pay_revenue_1', 'verified_at' => now()]);
        $refundedSeries = $this->paidSeries($admin);
        $refunded = $this->paymentFor($studentTwo, $refundedSeries, 'refunded', 'order_revenue_2');
        $refunded->update(['provider_payment_id' => 'pay_revenue_2', 'verified_at' => now(), 'refunded_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Payments/Index')
            ->where('summary.gross_revenue_paise', 39800)
            ->where('summary.refunded_paise', 19900)
            ->where('summary.net_revenue_paise', 19900));
    }

}
