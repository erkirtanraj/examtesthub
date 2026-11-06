<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\TestSeries;
use App\Models\TestSeriesEnrollment;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBulkContentController extends Controller
{
    private const RESOURCES = [
        'courses' => ['permission' => 'courses.manage', 'table' => 'courses'],
        'exams' => ['permission' => 'tests.manage', 'table' => 'exams'],
        'questions' => ['permission' => 'questions.manage', 'table' => 'questions'],
        'subjects' => ['permission' => 'questions.manage', 'table' => 'subjects'],
        'materials' => ['permission' => 'materials.manage', 'table' => 'study_materials'],
        'test-series' => ['permission' => 'tests.manage', 'table' => 'test_series'],
        'website-content' => ['permission' => 'settings.manage', 'table' => 'website_contents'],
    ];

    public function destroy(Request $request, string $resource): RedirectResponse
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);
        $config = self::RESOURCES[$resource];
        abort_unless($request->user()?->hasPermissionTo($config['permission']), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists($config['table'], 'id')],
        ]);
        $ids = array_values(array_map('intval', $data['ids']));
        $records = $this->records($resource, $ids);
        abort_if($records->count() !== count($ids), 404, 'One or more selected records no longer exist.');
        $this->assertDeletable($resource, $ids);

        $filePaths = [];
        DB::transaction(function () use ($request, $resource, $records, &$filePaths): void {
            foreach ($records as $record) {
                if ($record instanceof StudyMaterial) $filePaths[] = $record->file_path;
                if ($record instanceof Exam) $record->questions()->detach();
                if ($record instanceof TestSeries) $record->exams()->detach();
                $record->delete();
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'admin.bulk_deleted',
                'metadata' => ['resource' => $resource, 'ids' => $records->modelKeys(), 'count' => $records->count()],
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent())->limit(500, ''),
            ]);
        });

        if ($filePaths !== []) Storage::disk('local')->delete(array_filter($filePaths));

        return back()->with('success', $records->count().' '.$this->label($resource).' deleted.');
    }

    private function records(string $resource, array $ids)
    {
        return match ($resource) {
            'courses' => Course::whereIn('id', $ids)->get(),
            'exams' => Exam::whereIn('id', $ids)->get(),
            'questions' => Question::whereIn('id', $ids)->get(),
            'subjects' => Subject::whereIn('id', $ids)->get(),
            'materials' => StudyMaterial::whereIn('id', $ids)->get(),
            'test-series' => TestSeries::whereIn('id', $ids)->get(),
            'website-content' => WebsiteContent::whereIn('id', $ids)->get(),
            default => abort(404),
        };
    }

    private function assertDeletable(string $resource, array $ids): void
    {
        $blocked = match ($resource) {
            'courses' => CourseEnrollment::whereIn('course_id', $ids)->exists(),
            'exams' => Exam::whereIn('id', $ids)->where(fn ($q) => $q->whereHas('attempts')->orWhereHas('testSeries'))->exists(),
            'questions' => Question::whereIn('id', $ids)->whereHas('exams')->exists(),
            'subjects' => Question::whereIn('subject_id', $ids)->exists() || StudyMaterial::whereIn('subject_id', $ids)->exists(),
            'test-series' => TestSeriesEnrollment::whereIn('test_series_id', $ids)->exists(),
            default => false,
        };
        abort_if($blocked, 422, match ($resource) {
            'courses' => 'Selected courses have student enrollments. Archive them instead of deleting.',
            'exams' => 'Selected exams have attempts or belong to a test series. Remove those dependencies first.',
            'questions' => 'Selected questions are used by an exam. Remove them from every exam before deleting.',
            'subjects' => 'Selected subjects still contain questions or study materials. Move or delete those records first.',
            'test-series' => 'Selected test series have enrollments. Archive them instead of deleting.',
            default => 'One or more selected records cannot be deleted.',
        });
    }

    private function label(string $resource): string
    {
        return match ($resource) {
            'test-series' => 'test series item(s)',
            'website-content' => 'website content item(s)',
            'materials' => 'study material(s)',
            default => str($resource)->replace('-', ' ')->singular()->toString().' record(s)',
        };
    }
}
