<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TestSeriesController extends Controller
{
    private function audit(Request $request, string $action, TestSeries $series, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => ['test_series_id' => $series->id, ...$metadata],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('Admin/TestSeries/Index', [
            'series' => TestSeries::with(['exams:id'])->withCount(['exams', 'enrollments'])->latest('id')->paginate(15),
            'exams' => Exam::where('status', 'published')->orderBy('title')->get(['id', 'title', 'duration_minutes']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'access_type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['required_if:access_type,paid', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3', 'in:INR'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_enrollments' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'exam_ids' => ['required', 'array', 'min:1', 'max:500'],
            'exam_ids.*' => ['integer', 'distinct', 'exists:exams,id'],
        ]);

        $published = Exam::whereIn('id', $data['exam_ids'])->where('status', 'published')->count();
        if ($published !== count($data['exam_ids'])) {
            abort(422, 'Only published exams can be included in a test series.');
        }
        if ($data['access_type'] === 'free') {
            $data['price'] = 0;
        }
        if ($data['status'] === 'published' && $data['starts_at'] && $data['ends_at'] && $data['ends_at'] <= $data['starts_at']) {
            abort(422, 'The series end must be later than its start.');
        }

        return $data;
    }

    private function syncExams(TestSeries $series, array $ids): void
    {
        $sync = [];
        foreach (array_values($ids) as $position => $id) {
            $sync[$id] = ['position' => $position + 1];
        }
        $series->exams()->sync($sync);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $ids = $data['exam_ids'];
        unset($data['exam_ids']);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $data['created_by'] = $request->user()->id;
        $series = TestSeries::create($data);
        $this->syncExams($series, $ids);
        $this->audit($request, 'test_series.created', $series, ['exam_count' => count($ids)]);

        return back()->with('success', 'Test series created.');
    }

    public function update(Request $request, TestSeries $testSeries): RedirectResponse
    {
        abort_if($testSeries->status === 'archived', 422, 'Archived test series cannot be edited.');
        $data = $this->validated($request);
        $ids = $data['exam_ids'];
        unset($data['exam_ids'], $data['status']);
        $testSeries->update($data);
        $this->syncExams($testSeries, $ids);
        $this->audit($request, 'test_series.updated', $testSeries, ['exam_count' => count($ids)]);

        return back()->with('success', 'Test series updated.');
    }

    public function updateStatus(Request $request, TestSeries $testSeries): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
        if ($data['status'] === 'published') {
            abort_if($testSeries->exams()->where('status', '!=', 'published')->exists() || $testSeries->exams()->count() < 1, 422, 'A published series must contain at least one published exam.');
        }
        $testSeries->update(['status' => $data['status']]);
        $this->audit($request, 'test_series.status_changed', $testSeries, ['status' => $data['status']]);

        return back()->with('success', 'Test series status updated.');
    }

    public function enrollments(TestSeries $testSeries): Response
    {
        return Inertia::render('Admin/TestSeries/Enrollments', [
            'series' => ['id' => $testSeries->id, 'title' => $testSeries->title, 'access_type' => $testSeries->access_type],
            'enrollments' => $testSeries->enrollments()->with('user:id,name,email')->latest()->paginate(30),
        ]);
    }

    public function activateEnrollment(Request $request, TestSeries $testSeries, User $user): RedirectResponse
    {
        $data = $request->validate(['payment_reference' => ['nullable', 'string', 'max:120']]);
        $enrollment = TestSeriesEnrollment::where('test_series_id', $testSeries->id)->where('user_id', $user->id)->firstOrFail();
        abort_if($testSeries->access_type === 'paid' && blank($data['payment_reference']), 422, 'A verified payment reference is required to activate a paid enrollment.');
        $enrollment->update([
            'status' => 'active',
            'payment_reference' => $data['payment_reference'] ?? $enrollment->payment_reference,
            'amount_paid' => $testSeries->access_type === 'paid' ? $testSeries->price : 0,
            'enrolled_at' => $enrollment->enrolled_at ?? now(),
        ]);
        $this->audit($request, 'test_series.enrollment_activated', $testSeries, ['user_id' => $user->id, 'payment_reference' => $data['payment_reference'] ?? null]);

        return back()->with('success', 'Enrollment activated.');
    }
}
