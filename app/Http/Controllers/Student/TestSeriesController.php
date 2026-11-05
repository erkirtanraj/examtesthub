<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestSeriesController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $series = TestSeries::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->withCount('exams')
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('title')->paginate(12)->through(fn ($s) => [
                'id' => $s->id, 'title' => $s->title, 'description' => $s->description,
                'access_type' => $s->access_type, 'price' => $s->price, 'currency' => $s->currency,
                'exams_count' => $s->exams_count, 'enrollment' => $s->enrollments->first()
                    ? ['status' => $s->enrollments->first()->status] : null,
            ]);

        return Inertia::render('Student/TestSeries/Index', ['series' => $series]);
    }

    public function mySeries(Request $request): Response
    {
        $series = TestSeries::whereHas('enrollments', fn ($q) => $q->where('user_id', $request->user()->id))
            ->withCount('exams')->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('title')->paginate(12)->through(fn ($s) => [
                'id' => $s->id, 'title' => $s->title, 'access_type' => $s->access_type,
                'price' => $s->price, 'exams_count' => $s->exams_count,
                'enrollment' => ['status' => $s->enrollments->first()->status],
                'exams' => $s->exams()->where('exams.status', 'published')->get(['exams.id', 'exams.title', 'exams.duration_minutes']),
            ]);

        return Inertia::render('Student/TestSeries/Mine', ['series' => $series]);
    }

    public function enroll(Request $request, TestSeries $testSeries): RedirectResponse
    {
        abort_unless($testSeries->isAvailable(), 404);
        DB::transaction(function () use ($request, $testSeries) {
            $series = TestSeries::whereKey($testSeries->id)->lockForUpdate()->firstOrFail();
            abort_unless($series->isAvailable(), 404);
            $existing = TestSeriesEnrollment::where('test_series_id', $series->id)->where('user_id', $request->user()->id)->first();
            if ($existing?->status === 'active') {
                return;
            }
            $activeCount = $series->enrollments()->whereIn('status', ['active', 'pending_payment'])->count();
            $alreadyReserved = $existing && in_array($existing->status, ['active', 'pending_payment'], true);
            abort_if($series->max_enrollments && $activeCount >= $series->max_enrollments && ! $alreadyReserved, 422, 'Enrollment limit reached.');

            $status = $series->access_type === 'free' ? 'active' : 'pending_payment';
            TestSeriesEnrollment::updateOrCreate(
                ['test_series_id' => $series->id, 'user_id' => $request->user()->id],
                ['status' => $status, 'amount_paid' => $status === 'active' ? 0 : null,
                 'currency' => $series->currency, 'enrolled_at' => $status === 'active' ? now() : null]
            );
        });

        if ($testSeries->access_type === 'paid') {
            return back()->with('info', 'Payment is required. Your enrollment remains pending until a verified payment is activated by an administrator.');
        }

        return back()->with('success', 'You are enrolled in this test series.');
    }
}
