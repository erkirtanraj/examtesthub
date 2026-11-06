<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseCertificateController extends Controller
{
    public function show(Course $course): Response
    {
        abort_unless($course->isAvailable() || $course->status === 'archived', 404);

        $certificate = DB::transaction(function () use ($course) {
            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('user_id', request()->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($enrollment->status === 'completed' && $enrollment->completed_at, 403, 'Complete every published lesson to receive your certificate.');

            $publishedLessonCount = CourseLesson::query()
                ->where('status', 'published')
                ->whereHas('chapter.section', fn ($query) => $query->where('course_id', $course->id))
                ->count();

            $completedLessonCount = $enrollment->progress()
                ->where('status', 'completed')
                ->whereHas('lesson', fn ($query) => $query->where('status', 'published'))
                ->count();

            abort_unless($publishedLessonCount > 0 && $completedLessonCount >= $publishedLessonCount, 403, 'Course completion could not be verified.');

            return CourseCertificate::firstOrCreate(
                ['course_enrollment_id' => $enrollment->id],
                [
                    'certificate_number' => 'ETH-'.now()->format('Y').'-'.Str::upper(Str::random(12)),
                    'issued_at' => now(),
                ]
            );
        });

        return Inertia::render('Student/Courses/Certificate', [
            'certificate' => [
                'number' => $certificate->certificate_number,
                'issued_at' => $certificate->issued_at->toDateString(),
            ],
            'student' => ['name' => request()->user()->name],
            'course' => ['title' => $course->title, 'title_hi' => $course->title_hi],
        ]);
    }
}
