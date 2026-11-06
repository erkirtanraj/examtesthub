<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\TestSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => Coupon::query()->with(['course:id,title', 'testSeries:id,title'])
                ->withCount('redemptions')->latest()->paginate(20)->withQueryString(),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'testSeries' => TestSeries::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Coupon::create($this->validated($request));
        return back(303)->with('success', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));
        return back(303)->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        abort_if($coupon->redemptions()->exists(), 409, 'A coupon with redemptions cannot be deleted. Deactivate it instead.');
        $coupon->delete();
        return back(303)->with('success', 'Coupon deleted.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0', 'max:10000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'applies_to' => ['required', Rule::in(['all', 'course', 'test_series'])],
            'course_id' => ['nullable', 'required_if:applies_to,course', 'integer', 'exists:courses,id'],
            'test_series_id' => ['nullable', 'required_if:applies_to,test_series', 'integer', 'exists:test_series,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_value' => 'Percentage discounts cannot exceed 100%.']);
        }

        if ($data['applies_to'] !== 'course') $data['course_id'] = null;
        if ($data['applies_to'] !== 'test_series') $data['test_series_id'] = null;
        $data['code'] = strtoupper($data['code']);
        $data['minimum_amount'] = $data['minimum_amount'] ?? 0;

        return $data;
    }
}
