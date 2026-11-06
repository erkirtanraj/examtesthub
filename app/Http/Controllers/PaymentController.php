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

        // Lock the series row so concurrent retries for one student serialize
        // before checking or creating the enrollment's pending payment record.
        $payment = DB::transaction(function () use ($request, $testSeries) {
            $series = TestSeries::whereKey($testSeries->id)->lockForUpdate()->firstOrFail();
            abort_unless($series->isAvailable() && $series->access_type === 'paid', 404);

            $enrollment = TestSeriesEnrollment::where('test_series_id', $series->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment?->status === 'active') {
                abort(409, 'You are already enrolled in this test series.');
            }

            $reserved = $series->enrollments()->whereIn('status', ['active', 'pending_payment'])->count();
            $alreadyReserved = $enrollment && in_array($enrollment->status, ['active', 'pending_payment'], true);
            abort_if($series->max_enrollments && $reserved >= $series->max_enrollments && ! $alreadyReserved, 422, 'Enrollment limit reached.');

            if (! $enrollment) {
                $enrollment = TestSeriesEnrollment::create([
                    'test_series_id' => $series->id,
                    'user_id' => $request->user()->id,
                    'status' => 'pending_payment',
                    'amount_paid' => null,
                    'currency' => $series->currency,
                    'enrolled_at' => null,
                ]);
            } else {
                $enrollment->update([
                    'status' => 'pending_payment',
                    'amount_paid' => null,
                    'currency' => $series->currency,
                    'enrolled_at' => null,
                ]);
            }

            $amountPaise = (int) round(((float) $series->price) * 100);
            abort_if($amountPaise < 100, 422, 'The payment amount is invalid.');

            $pendingPayment = Payment::where('test_series_enrollment_id', $enrollment->id)
                ->where('status', 'pending')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($pendingPayment) {
                if (filled($pendingPayment->provider_order_id)) {
                    return $pendingPayment;
                }

                if ($pendingPayment->created_at && $pendingPayment->created_at->gt(now()->subMinutes(2))) {
                    abort(409, 'Your payment order is still being prepared. Please retry shortly.');
                }

                // Recover an abandoned order-creation attempt before starting a new one.
                $pendingPayment->update([
                    'status' => 'failed',
                    'failure_description' => 'Payment order creation timed out; a new attempt is required.',
                ]);
            }

            $receipt = 'eth_'.$enrollment->id.'_'.Str::lower(Str::random(12));

            return Payment::create([
                'test_series_enrollment_id' => $enrollment->id,
                'user_id' => $request->user()->id,
                'receipt' => $receipt,
                'amount_paise' => $amountPaise,
                'currency' => 'INR',
                'status' => 'pending',
            ]);
        });

        // A retry must return the same payable Razorpay order, never create another.
        if (filled($payment->provider_order_id)) {
            return redirect()->route('student.payments.checkout', $payment);
        }

        try {
            $response = Http::withBasicAuth($config['key_id'], $config['key_secret'])
                ->acceptJson()->timeout(20)->post(rtrim($config['base_url'], '/').'/orders', [
                    'amount' => $payment->amount_paise,
                    'currency' => $payment->currency,
                    'receipt' => $payment->receipt,
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

        // Serialize order creation per course enrollment. Concurrent retries either
        // reuse the active provider order or are told that order creation is in progress.
        $payment = DB::transaction(function () use ($request, $course, $amountPaise) {
            $lockedCourse = \App\Models\Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCourse->isAvailable() && $lockedCourse->access_type === 'paid', 404);

            $enrollment = \App\Models\CourseEnrollment::where('course_id', $lockedCourse->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment && in_array($enrollment->status, ['active', 'completed'], true)) {
                abort(409, 'You are already enrolled in this course.');
            }

            if (! $enrollment) {
                $enrollment = \App\Models\CourseEnrollment::create([
                    'course_id' => $lockedCourse->id,
                    'user_id' => $request->user()->id,
                    'status' => 'pending_payment',
                    'amount_paid' => null,
                    'currency' => $lockedCourse->currency,
                    'enrolled_at' => null,
                ]);
            } else {
                $enrollment->update([
                    'status' => 'pending_payment',
                    'amount_paid' => null,
                    'currency' => $lockedCourse->currency,
                    'enrolled_at' => null,
                ]);
            }

            $amountPaise = (int) round(((float) $lockedCourse->price) * 100);
            abort_if($amountPaise < 100, 422, 'The payment amount is invalid.');

            $pendingPayment = Payment::where('course_enrollment_id', $enrollment->id)
                ->where('status', 'pending')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($pendingPayment) {
                if (filled($pendingPayment->provider_order_id)) {
                    return $pendingPayment;
                }

                if ($pendingPayment->created_at && $pendingPayment->created_at->gt(now()->subMinutes(2))) {
                    abort(409, 'Your payment order is still being prepared. Please retry shortly.');
                }

                // Recover an abandoned order-creation attempt before starting a new one.
                $pendingPayment->update([
                    'status' => 'failed',
                    'failure_description' => 'Payment order creation timed out; a new attempt is required.',
                ]);
            }

            $receipt = 'course_'.$enrollment->id.'_'.Str::lower(Str::random(12));

            return Payment::create([
                'course_enrollment_id' => $enrollment->id,
                'user_id' => $request->user()->id,
                'receipt' => $receipt,
                'amount_paise' => $amountPaise,
                'currency' => 'INR',
                'status' => 'pending',
            ]);
        });

        // A retry must return the same payable Razorpay order, never create another.
        if (filled($payment->provider_order_id)) {
            return redirect()->route('student.payments.checkout', $payment);
        }

        try {
            $response = Http::withBasicAuth($config['key_id'], $config['key_secret'])
                ->acceptJson()->timeout(20)->post(rtrim($config['base_url'], '/').'/orders', [
                    'amount' => $payment->amount_paise,
                    'currency' => $payment->currency,
                    'receipt' => $payment->receipt,
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
        abort_unless($payment->status === 'pending', 409, 'This payment is no longer payable.');
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

        return redirect()->route('student.payments.index')->with('success', 'Payment verified. Your purchased access is active.');
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
        $refundEntity = $payload['payload']['refund']['entity'] ?? [];
        $orderId = $entity['order_id'] ?? null;
        $paymentId = $entity['id'] ?? null;
        $refundId = $refundEntity['id'] ?? null;

        $inserted = DB::transaction(function () use ($eventId, $eventType, $request, $entity, $refundEntity, $orderId, $paymentId, $refundId) {
            $payment = null;
            if (in_array($eventType, ['payment.captured', 'payment.failed'], true)) {
                $payment = Payment::where('provider_order_id', $orderId)->lockForUpdate()->first();
                abort_unless($payment, 404, 'Payment order not found.');
            } elseif ($eventType === 'refund.processed') {
                $payment = Payment::where('provider_refund_id', $refundId)->lockForUpdate()->first();
                abort_unless($payment, 404, 'Refund record not found.');
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
                if (in_array($payment->status, ['pending', 'failed'], true)) {
                    $enrollment = $payment->course_enrollment_id
                        ? \App\Models\CourseEnrollment::whereKey($payment->course_enrollment_id)->lockForUpdate()->firstOrFail()
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
            } elseif ($eventType === 'refund.processed' && $payment && $payment->status === 'refund_pending'
                && (int) ($refundEntity['amount'] ?? 0) === $payment->amount_paise) {
                $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
                $enrollment = $payment->course_enrollment_id
                    ? \App\Models\CourseEnrollment::whereKey($payment->course_enrollment_id)->lockForUpdate()->first()
                    : TestSeriesEnrollment::whereKey($payment->test_series_enrollment_id)->lockForUpdate()->first();
                $enrollment?->update(['status' => 'refunded']);
            }

            return true;
        });

        return response()->json(['status' => $inserted ? 'accepted' : 'duplicate']);
    }

    public function invoice(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        abort_unless(in_array($payment->status, ['captured', 'refund_pending', 'refunded'], true), 404);

        $payment->load(['enrollment.series:id,title', 'courseEnrollment.course:id,title', 'user:id,name,email']);

        return Inertia::render('Student/Payments/Invoice', [
            'payment' => [
                'id' => $payment->id,
                'receipt' => $payment->receipt,
                'payment_id' => $payment->provider_payment_id,
                'item' => $payment->courseEnrollment?->course?->title ?? $payment->enrollment?->series?->title ?? 'Learning purchase',
                'student' => $payment->user?->name,
                'email' => $payment->user?->email,
                'amount' => $payment->amount_paise / 100,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'paid_at' => $payment->verified_at?->toIso8601String(),
                'refunded_at' => $payment->refunded_at?->toIso8601String(),
            ],
        ]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $locked = DB::transaction(function () use ($payment, $data) {
            $record = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'captured' && filled($record->provider_payment_id), 409, 'Only captured payments can be refunded.');
            $record->update(['status' => 'refund_pending', 'refund_reason' => $data['reason']]);

            return $record;
        });

        try {
            $config = $this->razorpayConfig();
            $response = Http::withBasicAuth($config['key_id'], $config['key_secret'])
                ->acceptJson()->timeout(20)
                ->post(rtrim($config['base_url'], '/').'/payments/'.$locked->provider_payment_id.'/refund', [
                    'notes' => ['payment_record_id' => (string) $locked->id, 'reason' => $data['reason']],
                ]);

            if (! $response->successful() || ! filled($response->json('id'))) {
                if ($response->clientError()) {
                    $locked->update(['status' => 'captured', 'refund_reason' => null]);
                }
                Log::warning('Razorpay refund request failed', ['payment_id' => $locked->id, 'status' => $response->status()]);
                return back()->withErrors(['refund' => 'The provider did not confirm the refund request. Check the payment provider dashboard before retrying.']);
            }

            $locked->update(['provider_refund_id' => $response->json('id')]);
            if ($response->json('status') === 'processed') {
                $this->finalizeRefund($locked);
            }
        } catch (\Throwable $exception) {
            // Keep the record in refund_pending: a network timeout may happen after
            // the provider accepted the request. Reconcile it before another attempt.
            Log::error('Razorpay refund request outcome is unknown', ['payment_id' => $locked->id, 'exception' => $exception::class]);
            return back()->withErrors(['refund' => 'The refund request outcome is not confirmed. Check Razorpay before taking further action.']);
        }

        return back()->with('success', 'Refund request submitted to Razorpay.');
    }

    private function finalizeRefund(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'refunded') {
                return;
            }
            if ($locked->status !== 'refund_pending') {
                return;
            }
            $locked->update(['status' => 'refunded', 'refunded_at' => now()]);
            $enrollment = $locked->course_enrollment_id
                ? \App\Models\CourseEnrollment::whereKey($locked->course_enrollment_id)->lockForUpdate()->first()
                : TestSeriesEnrollment::whereKey($locked->test_series_enrollment_id)->lockForUpdate()->first();
            $enrollment?->update(['status' => 'refunded']);
        });
    }

    public function studentIndex(Request $request): Response
    {
        return Inertia::render('Student/Payments/Index', [
            'payments' => Payment::with(['enrollment.series:id,title', 'courseEnrollment.course:id,title'])
                ->where('user_id', $request->user()->id)->latest()->paginate(20),
        ]);
    }

    public function adminIndex(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,captured,failed,refund_pending,refunded'],
            'search' => ['nullable', 'string', 'max:120'],
            'days' => ['nullable', 'integer', 'in:7,30,90,365'],
        ]);
        $days = (int) ($filters['days'] ?? 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $query = Payment::query()->with(['user:id,name,email', 'enrollment.series:id,title', 'courseEnrollment.course:id,title'])
            ->where('created_at', '>=', $from)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['search'] ?? null, function ($builder, $term) {
                $builder->where(function ($search) use ($term) {
                    $search->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                        ->orWhere('provider_payment_id', 'like', "%{$term}%")
                        ->orWhere('provider_order_id', 'like', "%{$term}%");
                });
            });
        $summary = Payment::query()->where('created_at', '>=', $from);
        $captured = (clone $summary)->whereIn('status', ['captured', 'refund_pending', 'refunded']);
        $gross = (int) $captured->sum('amount_paise');
        $refunded = (int) (clone $summary)->where('status', 'refunded')->sum('amount_paise');

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $query->latest()->paginate(30)->withQueryString(),
            'filters' => ['status' => $filters['status'] ?? '', 'search' => $filters['search'] ?? '', 'days' => $days],
            'summary' => [
                'gross_revenue_paise' => $gross,
                'refunded_paise' => $refunded,
                'net_revenue_paise' => max(0, $gross - $refunded),
                'captured_count' => (clone $summary)->whereIn('status', ['captured', 'refund_pending', 'refunded'])->count(),
                'failed_count' => (clone $summary)->where('status', 'failed')->count(),
                'pending_refunds' => (clone $summary)->where('status', 'refund_pending')->count(),
            ],
        ]);
    }
}
