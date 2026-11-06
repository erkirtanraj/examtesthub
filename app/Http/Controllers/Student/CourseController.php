<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->withCount(['sections', 'enrollments'])
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('title')->paginate(12)->through(fn ($course) => [
                'id' => $course->id, 'title' => $course->title, 'title_hi' => $course->title_hi,
                'slug' => $course->slug, 'description' => $course->description, 'description_hi' => $course->description_hi,
                'category' => $course->category, 'access_type' => $course->access_type,
                'price' => $course->price, 'currency' => $course->currency,
                'sections_count' => $course->sections_count,
                'enrollment' => $course->enrollments->first()?->only(['id','status']),
            ]);
        return Inertia::render('Student/Courses/Index', ['courses' => $courses]);
    }

    public function myCourses(Request $request): Response
    {
        $courses = Course::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $request->user()->id))
            ->withCount('sections')
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('title')->paginate(12)->through(fn ($course) => [
                'id' => $course->id, 'title' => $course->title, 'title_hi' => $course->title_hi,
                'slug' => $course->slug, 'description' => $course->description, 'description_hi' => $course->description_hi,
                'category' => $course->category, 'access_type' => $course->access_type,
                'price' => $course->price, 'currency' => $course->currency,
                'sections_count' => $course->sections_count,
                'enrollment' => $course->enrollments->first()?->only(['id','status']),
            ]);
        return Inertia::render('Student/Courses/Index', ['courses' => $courses, 'mine' => true]);
    }

    public function show(Request $request, Course $course): Response
    {
        abort_unless($course->isAvailable(), 404);

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $request->user()->id)
            ->first();
        $hasAccess = $enrollment && in_array($enrollment->status, ['active', 'completed'], true);

        $course->load(['sections.chapters.lessons' => fn ($q) => $q->where('status', 'published')->orderBy('position')]);

        // Only expose curriculum metadata here. Lesson bodies and video URLs are
        // returned by the dedicated lesson endpoint after the access check.
        $sections = $course->sections->map(fn ($section) => [
            'id' => $section->id,
            'title' => $section->title,
            'title_hi' => $section->title_hi,
            'description' => $section->description,
            'description_hi' => $section->description_hi,
            'position' => $section->position,
            'chapters' => $section->chapters->map(fn ($chapter) => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'title_hi' => $chapter->title_hi,
                'position' => $chapter->position,
                'lessons' => $chapter->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'title_hi' => $lesson->title_hi,
                    'type' => $lesson->type,
                    'duration_minutes' => $lesson->duration_minutes,
                    'is_preview' => $lesson->is_preview,
                    'is_locked' => ! $hasAccess && ! $lesson->is_preview,
                ])->values(),
            ])->values(),
        ])->values();

        $lessonIds = $course->sections->flatMap(fn ($section) => $section->chapters)
            ->flatMap(fn ($chapter) => $chapter->lessons)->pluck('id')->all();
        $lessonCount = count($lessonIds);
        $completedIds = $enrollment
            ? $enrollment->progress()->where('status', 'completed')->whereIn('lesson_id', $lessonIds)->pluck('lesson_id')->all()
            : [];
        $completed = count($completedIds);
        $resumeLessonId = $hasAccess
            ? collect($lessonIds)->first(fn ($lessonId) => ! in_array($lessonId, $completedIds, true))
            : null;

        return Inertia::render('Student/Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'title_hi' => $course->title_hi,
                'slug' => $course->slug,
                'description' => $course->description,
                'description_hi' => $course->description_hi,
                'category' => $course->category,
                'access_type' => $course->access_type,
                'price' => $course->price,
                'currency' => $course->currency,
                'sections' => $sections,
            ],
            'enrollment' => $enrollment?->only(['id', 'status', 'enrolled_at', 'completed_at']),
            'progress' => ['total' => $lessonCount, 'completed' => $completed],
            'resume_lesson_id' => $resumeLessonId,
        ]);
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isAvailable(), 404);
        abort_if($course->access_type === 'paid', 409, 'Paid courses must use secure checkout.');
        $enrollment = CourseEnrollment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $request->user()->id],
            ['status' => 'active', 'amount_paid' => 0, 'currency' => $course->currency, 'enrolled_at' => now()]
        );
        if ($enrollment->wasRecentlyCreated) {
            $user = $request->user();
            $preferences = $user->notification_preferences ?? [];
            if ($preferences['course_updates'] ?? true) {
                $user->notify(new PlatformNotification('Course enrollment confirmed', 'You are now enrolled in '.$course->title.'. Start learning from your course dashboard.', '/student/courses/'.$course->slug, 'course_update'));
            }
        }
        return back()->with('success', 'You are enrolled in this course.');
    }

    public function lesson(Request $request, Course $course, CourseLesson $lesson): Response
    {
        abort_unless($course->isAvailable(), 404);
        abort_unless($lesson->status === 'published' && $lesson->chapter()->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->exists(), 404);
        $enrollment = CourseEnrollment::where('course_id', $course->id)->where('user_id', $request->user()->id)->whereIn('status', ['active', 'completed'])->first();
        abort_unless($enrollment || $lesson->is_preview, 403);
        if ($lesson->type === 'video') {
            $host = strtolower((string) parse_url($lesson->video_url, PHP_URL_HOST));
            $path = trim((string) parse_url($lesson->video_url, PHP_URL_PATH), '/');
            parse_str((string) parse_url($lesson->video_url, PHP_URL_QUERY), $query);
            $videoId = $host === 'youtu.be' ? explode('/', $path)[0] : ($query['v'] ?? (preg_match('~(?:embed|shorts)/([^/?]+)~', $path, $matches) ? $matches[1] : null));
            if (in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be'], true) && $videoId) {
                $lesson->setAttribute('embed_url', 'https://www.youtube-nocookie.com/embed/'.$videoId);
            } elseif (str_contains($host, 'vimeo.com') && preg_match('~([0-9]+)$~', $path, $matches)) {
                $lesson->setAttribute('embed_url', 'https://player.vimeo.com/video/'.$matches[1]);
            }
        }
        $lesson->load('resources');
        $lesson->setRelation('resources', $lesson->resources->map(fn ($resource) => $resource->only(['id', 'title', 'title_hi', 'type', 'url', 'file_size'])));
        $progress = $enrollment ? CourseLessonProgress::firstOrCreate(
            ['course_enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            ['status' => 'in_progress', 'started_at' => now()]
        ) : null;
        return Inertia::render('Student/Courses/Lesson', [
            'course' => ['title' => $course->title, 'title_hi' => $course->title_hi, 'slug' => $course->slug],
            'lesson' => $lesson, 'progress' => $progress,
            'can_complete' => (bool) $enrollment,
        ]);
    }

    public function downloadResource(Request $request, Course $course, CourseLesson $lesson, \App\Models\CourseLessonResource $resource): StreamedResponse
    {
        abort_unless($course->isAvailable() && $lesson->status === 'published', 404);
        abort_unless($resource->lesson_id === $lesson->id
            && $lesson->chapter()->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->exists(), 404);
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['active', 'completed'])->first();
        abort_unless($enrollment || $lesson->is_preview, 403);
        abort_unless($resource->type === 'file' && $resource->file_path
            && Storage::disk('local')->exists($resource->file_path), 404);
        return Storage::disk('local')->download($resource->file_path, $resource->original_name ?: basename($resource->file_path));
    }

    public function complete(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        abort_unless($course->isAvailable(), 404);
        abort_unless($lesson->status === 'published'
            && $lesson->chapter()->whereHas('section', fn ($q) => $q->where('course_id', $course->id))->exists(), 404);

        DB::transaction(function () use ($request, $course, $lesson) {
            $enrollment = CourseEnrollment::where('course_id', $course->id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['active', 'completed'])
                ->lockForUpdate()
                ->firstOrFail();

            CourseLessonProgress::updateOrCreate(
                ['course_enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
                ['status' => 'completed', 'completed_at' => now(), 'started_at' => $enrollment->enrolled_at ?? now()]
            );

            $total = CourseLesson::where('status', 'published')
                ->whereHas('chapter.section', fn ($q) => $q->where('course_id', $course->id))
                ->count();
            $completed = $enrollment->progress()->where('status', 'completed')
                ->whereHas('lesson', fn ($q) => $q->where('status', 'published'))
                ->count();

            if ($total > 0 && $completed >= $total) {
                $enrollment->update(['status' => 'completed', 'completed_at' => $enrollment->completed_at ?? now()]);
            } else {
                // A completed course may receive new published lessons later.
                $enrollment->update(['status' => 'active', 'completed_at' => null]);
            }
        });

        return back()->with('success', 'Lesson marked as complete.');
    }
}
