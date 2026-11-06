# Local Demo Content

This repository includes an explicit, repeatable demo seeder for local UI and workflow testing.

## Contents

Running `DemoContentSeeder` creates or updates demo records for:

- 12 subjects: General Knowledge, Indian History, Indian Geography, Indian Polity, Indian Economy, General Science, Physics, Chemistry, Biology, Mathematics, Reasoning, and English.
- 12 free, published foundation courses, each with one section, one chapter, and one preview lesson.
- 12 free, published test series, each containing one published practice exam.
- 12 published exams with 5 multiple-choice questions each (60 questions total).
- 12 generated PDF study notes in the private local storage disk.

Demo records use `demo-` slugs or `[DEMO]` question prompts. The PDFs are synthetic sample notes for testing only, not official or complete exam-preparation materials.

## Run locally

From the project root:

```powershell
php artisan db:seed --class="Database\\Seeders\\DemoContentSeeder"
```

The seeder is idempotent for its demo identifiers. It does not reset tables, create student accounts, enroll students, create payment records, or add demo content to the default `DatabaseSeeder`. Run it explicitly only in a local or disposable development environment.

PDF files are generated under:

```
storage/app/private/demo/study-materials/
```

The study-material records have no course restriction so a logged-in student can test the general study-material library and PDF download route without a course enrollment.

## Verify

```powershell
php artisan tinker --execute="echo 'Subjects: '.App\\Models\\Subject::where('slug','like','demo-%')->count().PHP_EOL; echo 'Courses: '.App\\Models\\Course::where('slug','like','demo-course-%')->count().PHP_EOL; echo 'Test series: '.App\\Models\\TestSeries::where('slug','like','demo-series-%')->count().PHP_EOL; echo 'Questions: '.App\\Models\\Question::where('prompt','like','[DEMO]%')->count().PHP_EOL; echo 'PDF materials: '.App\\Models\\StudyMaterial::where('file_path','like','demo/study-materials/%')->count().PHP_EOL;"
```

Expected counts: 12 subjects, 12 courses, 12 test series, 60 questions, and 12 PDF materials.
