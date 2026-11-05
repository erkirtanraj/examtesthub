<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    private function razorpayConfig(): array
    {
        $config = config('services.razorpay');
        abort_unless(filled($config['key_id'] ?? null) && filled($config['key_secret'] ?? null), 503, 'Razorpay is not configured.');

        return $config;
    }

    public function createOrder(Request $request, TestSeries $testSeries): RedirectResponse
    {
        abort_unless($testSeries->isAvailable() && $testSeries->access_type === 'paid', 404);
        $config = $this->razorpayConfig();
        $amountPaise = (int) round(((float) $testSeries->price) * 100);
        abort_if($amountPaise < 100, 422, 'The payment amount is invalid.');

        $enrollment = DB::transaction(function () use ($request, $testSeries) {
            $series = TestSeries::whereKey($testSeries->id)->lockForUpdate()->firstOrFail();
            abort_unless($series->isAvailable() && $series->access_type === 'paid', 404);
            $existing = TestSeriesEnrollment::where('test_series_id', $series->id)
                ->where('user_id', $request->user()->id)->lockForUpdate()->first();

            if ($existing?->status === 'active') {
                abort(409, 'You are already enrolled in this test series.');
            }

            $reserved = $series->enrollments()->whereIn('status', ['active', 'pending_payment'])->count();
            $alreadyReserved = $existing && in_array($existing->status, ['active', 'pending_payment'], true);
            abort_if($series->max_enrollments && $reserved >= $series->max_enrollments && ! $alreadyReserved, 422, 'Enrollment limit reached.');

            return TestSeriesEnrollment::updateOrCreate(
                ['test_series_id' => $series->id, 'user_id' => $request->user()->id],
                ['status' => 'pending_payment', 'amount_paid' => null, 'currency' => $series->currency, 'enrolled_at' => null]
            );
        });

        $receipt = 'eth_'.$enrollment->id.'_'.Str::lower(Str::random(12));
        $payment = Payment::create([
            'test_series_enrollment_id' => $enrollment->id,
            'user_id' => $request->user()->id,
            'receipt' => $receipt,
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        try {
            $response = Http::withBasicAuth($config['key_id'], $config['key_secret'])
                ->acceptJson()->timeout(20)->post(rtrim($config['base_url'], '/').'/orders', [
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => ['payment_record_id' => (string) $payment->id, 'test_series_id' => (string) $testSeries->id],
                ]);
            if (! $response->successful() || ! filled($response->json('id'))) {
                $payment->update(['status' => 'failed', 'failure_description' => 'Order creation failed at payment provider.']);
                Log::warning('Razorpay order creation failed', ['payment_id' => $payment->id, 'status' => $response->status()]);
                return back()->withErrors(['payment' => 'Unable to start payment. Please try again later.']);
            }

            $payment->update(['provider_order_id' => $response->json('id')]);
        } catch (\Throwable $exception) {
            $payment->update(['status' => 'failed', 'failure_description' => 'Payment provider unavailable.']);
            Log::error('Razorpay order request failed', ['payment_id' => $payment->id, 'exception' => $exception::class]);
            return back()->withErrors(['payment' => 'Payment service is temporarily unavailable.']);
        }

        return redirect()->route('student.payments.checkout', $payment);
    }

    public function createCourseOrder(Request $request, \App\Models\Course $course): RedirectResponse
    {
        abort_unless($course->isAvailable() && $course->access_type === 'paid', 404);
        $config = $this->razorpayConfig();
        $amountPaise = (int) round(((float) $course->price) * 100);
        abort_if($amountPaise < 100, 422, 'The payment amount is invalid.');

        $enrollment = DB::transaction(function () use ($request, $course) {
            $lockedCourse = \App\Models\Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCourse->isAvailable() && $lockedCourse->access_type === 'paid', 404);
            $existing = \App\Models\CourseEnrollment::where('course_id', $lockedCourse->id)
                ->where('user_id', $request->user()->id)->lockForUpdate()->first();
            if ($existing?->status === 'active' || $existing?->status === 'completed') {
                abort(409, 'You are already enrolled in this course.');
            }
            return \App\Models\CourseEnrollment::updateOrCreate(
                ['course_id' => $lockedCourse->id, 'user_id' => $request->user()->id],
                ['status' => 'pending_payment', 'amount_paid' => null, 'currency' => $lockedCourse->currency, 'enrolled_at' => null]
            );
        });

        $receipt = 'course_'.$enrollment->id.'_'.Str::lower(Str::random(12));
        $payment = Payment::create([
            'course_enrollment_id' => $enrollment->id,
            'user_id' => $request->user()->id,
            'receipt' => $receipt,
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        try {
            $response = Http::withBasicAuth($config['key_id'], $config['key_secret'])
                ->acceptJson()->timeout(20)->post(rtrim($config['base_url'], '/').'/orders', [
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => ['payment_record_id' => (string) $payment->id, 'course_id' => (string) $course->id],
                ]);
            if (! $response->successful() || ! filled($response->json('id'))) {
                $payment->update(['status' => 'failed', 'failure_description' => 'Order creation failed at payment provider.']);
                Log::warning('Razorpay course order creation failed', ['payment_id' => $payment->id, 'status' => $response->status()]);
                return back()->withErrors(['payment' => 'Unable to start payment. Please try again later.']);
            }
            $payment->update(['provider_order_id' => $response->json('id')]);
        } catch (\Throwable $exception) {
            $payment->update(['status' => 'failed', 'failure_description' => 'Payment provider unavailable.']);
            Log::error('Razorpay course order request failed', ['payment_id' => $payment->id, 'exception' => $exception::class]);
            return back()->withErrors(['payment' => 'Payment service is temporarily unavailable.']);
        }

        return redirect()->route('student.payments.checkout', $payment);
    }

    public function checkout(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        abort_unless($payment->status === 'pending' && filled($payment->provider_order_id), 404);
        $config = $this->razorpayConfig();

        return Inertia::render('Student/Payments/Checkout', [
            'payment' => [
                'id' => $payment->id,
                'order_id' => $payment->provider_order_id,
                'amount' => $payment->amount_paise,
                'currency' => $payment->currency,
                'key_id' => $config['key_id'],
                'series_title' => $payment->enrollment?->series?->title ?? $payment->courseEnrollment?->course?->title,
                'student_name' => $request->user()->name,
                'student_email' => $request->user()->email,
            ],
        ]);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'],
        ]);
        abort_unless(hash_equals((string) $payment->provider_order_id, $data['razorpay_order_id']), 422, 'Order mismatch.');

        $config = $this->razorpayConfig();
        $expected = hash_hmac('sha256', $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'], $config['key_secret']);
        abort_unless(hash_equals($expected, $data['razorpay_signature']), 422, 'Payment signature is invalid.');

        $providerResponse = Http::withBasicAuth($config['key_id'], $config['key_secret'])
            ->acceptJson()->timeout(20)->get(rtrim($config['base_url'], '/').'/payments/'.$data['razorpay_payment_id']);
        abort_unless($providerResponse->successful(), 422, 'Unable to verify payment with the provider.');
        $providerPayment = $providerResponse->json();

        abort_unless(($providerPayment['order_id'] ?? null) === $payment->provider_order_id, 422, 'Payment order does not match.');
        abort_unless((int) ($providerPayment['amount'] ?? 0) === $payment->amount_paise, 422, 'Payment amount does not match.');
        abort_unless(($providerPayment['currency'] ?? null) === $payment->currency, 422, 'Payment currency does not match.');
        abort_unless(($providerPayment['status'] ?? null) === 'captured', 422, 'Payment has not been captured.');

        $this->markCaptured($payment, $data['razorpay_payment_id']);

        return redirect()->route('student.payments.index')->with('success', 'Payment verified. Your test-series access is active.');
    }

    private function markCaptured(Payment $payment, string $providerPaymentId): void
    {
        DB::transaction(function () use ($payment, $providerPaymentId) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'captured') {
                return;
            }
            abort_if($locked->status === 'refunded', 409, 'This payment has been refunded.');
            $enrollment = $locked->course_enrollment_id
                ? \App\Models\CourseEnrollment::whereKey($locked->course_enrollment_id)->lockForUpdate()->firstOrFail()
                : TestSeriesEnrollment::whereKey($locked->test_series_enrollment_id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'provider_payment_id' => $providerPaymentId,
                'status' => 'captured',
                'failure_code' => null,
                'failure_description' => null,
                'verified_at' => now(),
            ]);
            $enrollment->update([
                'status' => 'active',
                'amount_paid' => number_format($locked->amount_paise / 100, 2, '.', ''),
                'currency' => $locked->currency,
                'payment_reference' => $providerPaymentId,
                'enrolled_at' => $enrollment->enrolled_at ?? now(),
            ]);
        });
    }

    public function webhook(Request $request): JsonResponse
    {
        $config = config('services.razorpay');
        abort_unless(filled($config['webhook_secret'] ?? null), 503, 'Webhook is not configured.');
        $signature = (string) $request->header('X-Razorpay-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $config['webhook_secret']);
        abort_unless($signature !== '' && hash_equals($expected, $signature), 401, 'Invalid webhook signature.');

        $payload = $request->json()->all();
        $eventId = (string) $request->header('X-Razorpay-Event-Id');
        abort_unless($eventId !== '' && strlen($eventId) <= 120, 400, 'Missing webhook event ID.');
        $eventType = (string) ($payload['event'] ?? '');
        $entity = $payload['payload']['payment']['entity'] ?? [];
        $orderId = $entity['order_id'] ?? null;
        $paymentId = $entity['id'] ?? null;

        $inserted = DB::transaction(function () use ($eventId, $eventType, $request, $entity, $orderId, $paymentId) {
            $payment = null;
            if (in_array($eventType, ['payment.captured', 'payment.failed'], true)) {
                $payment = Payment::where('provider_order_id', $orderId)->lockForUpdate()->first();
                abort_unless($payment, 404, 'Payment order not found.');
            }

            $created = DB::table('payment_webhook_events')->insertOrIgnore([
                'provider_event_id' => $eventId,
                'event_type' => $eventType,
                'payload_hash' => hash('sha256', $request->getContent()),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($created === 0) {
                return false;
            }

            if ($eventType === 'payment.captured' && $payment && $paymentId
                && (int) ($entity['amount'] ?? 0) === $payment->amount_paise
                && ($entity['currency'] ?? null) === $payment->currency) {
                if ($payment->status !== 'captured' && $payment->status !== 'refunded') {
                    $enrollment = $payment->course_enrollment_id
                        ? \\App\\Models\\CourseEnrollment::whereKey($payment->course_enrollment_id)->lockForUpdate()->firstOrFail()
                        : TestSeriesEnrollment::whereKey($payment->test_series_enrollment_id)->lockForUpdate()->firstOrFail();
                    $payment->update([
                        'provider_payment_id' => $paymentId,
                        'status' => 'captured',
                        'failure_code' => null,
                        'failure_description' => null,
                        'verified_at' => now(),
                    ]);
                    $enrollment->update([
                        'status' => 'active',
                        'amount_paid' => number_format($payment->amount_paise / 100, 2, '.', ''),
                        'currency' => $payment->currency,
                        'payment_reference' => $paymentId,
                        'enrolled_at' => $enrollment->enrolled_at ?? now(),
                    ]);
                }
            } elseif ($eventType === 'payment.failed' && $payment && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                    'failure_code' => $entity['error_code'] ?? null,
                    'failure_description' => Str::limit((string) ($entity['error_description'] ?? 'Payment failed.'), 500, ''),
                ]);
            }

            return true;
        });

        return response()->json(['status' => $inserted ? 'accepted' : 'duplicate']);
    }

    public function studentIndex(Request $request): Response
    {
        return Inertia::render('Student/Payments/Index', [
            'payments' => Payment::with(['enrollment.series:id,title', 'courseEnrollment.course:id,title'])
                ->where('user_id', $request->user()->id)->latest()->paginate(20),
        ]);
    }

    public function adminIndex(): Response
    {
        return Inertia::render('Admin/Payments/Index', [
            'payments' => Payment::with(['user:id,name,email', 'enrollment.series:id,title', 'courseEnrollment.course:id,title'])->latest()->paginate(30),
        ]);
    }
}
