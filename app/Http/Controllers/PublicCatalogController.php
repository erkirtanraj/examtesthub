<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudyMaterial;
use App\Models\TestSeries;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    public function index(Request $request, string $type): Response
    {
        abort_unless(in_array($type, ['courses', 'materials', 'test-series'], true), 404);

        $search = $request->validate(['search' => ['nullable', 'string', 'max:160']])['search'] ?? null;

        if ($type === 'courses') {
            $items = Course::query()
                ->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->withCount('sections')
                ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('title', 'like', "%{$search}%")->orWhere('title_hi', 'like', "%{$search}%")))
                ->orderBy('title')->paginate(12)->withQueryString()
                ->through(fn ($course) => [
                    'id' => $course->id, 'title' => $course->title, 'title_hi' => $course->title_hi,
                    'slug' => $course->slug, 'description' => $course->description, 'description_hi' => $course->description_hi,
                    'category' => $course->category, 'access_type' => $course->access_type,
                    'price' => $course->price, 'currency' => $course->currency, 'sections_count' => $course->sections_count,
                ]);
        } elseif ($type === 'materials') {
            $items = StudyMaterial::query()->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('course_id')->orWhereHas('course', fn ($c) => $c->where('status', 'published')
                    ->where(fn ($s) => $s->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                    ->where(fn ($s) => $s->whereNull('ends_at')->orWhere('ends_at', '>', now()))))
                ->with(['subject:id,name', 'course:id,title,title_hi,slug,access_type'])
                ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('title', 'like', "%{$search}%")->orWhere('title_hi', 'like', "%{$search}%")))
                ->latest('published_at')->paginate(12)->withQueryString()
                ->through(fn ($material) => [
                    'id' => $material->id, 'title' => $material->title, 'title_hi' => $material->title_hi,
                    'description' => $material->description, 'description_hi' => $material->description_hi,
                    'language' => $material->language, 'file_size' => $material->file_size,
                    'subject' => $material->subject?->name,
                    'course' => $material->course ? ['title' => $material->course->title, 'title_hi' => $material->course->title_hi, 'slug' => $material->course->slug, 'access_type' => $material->course->access_type] : null,
                    'requires_login' => true,
                ]);
        } else {
            $items = TestSeries::query()->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->withCount('exams')
                ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
                ->orderBy('title')->paginate(12)->withQueryString()
                ->through(fn ($series) => [
                    'id' => $series->id, 'title' => $series->title, 'description' => $series->description,
                    'access_type' => $series->access_type, 'price' => $series->price, 'currency' => $series->currency,
                    'exams_count' => $series->exams_count,
                ]);
        }

        return Inertia::render('Public/Catalog', [
            'type' => $type,
            'items' => $items,
            'search' => $search,
        ]);
    }
}
