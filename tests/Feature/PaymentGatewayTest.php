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

    private function paymentFor(User $student, TestSeries $series, string $status = 'pending'): Payment
    {
        $enrollment = TestSeriesEnrollment::create([
            'test_series_id' => $series->id, 'user_id' => $student->id,
            'status' => 'pending_payment', 'currency' => 'INR',
        ]);

        return Payment::create([
            'test_series_enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'provider_order_id' => 'order_test_123',
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
}
