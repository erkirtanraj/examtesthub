# Phase 7 — LMS Course Management

Status: merged into `main` on 2026-11-05 (merge commit `f9e45a640f05af6fb85b3c18818c618aad9cc5de`). The final Phase 7 GitHub Actions run passed both the PHP feature-test job and the frontend production-build job. Manual responsive QA, target-database migration verification and Razorpay sandbox testing remain before production deployment.

## Scope delivered

- Admin course catalogue with draft/published/archived lifecycle, bilingual titles/descriptions, category, free/paid pricing and availability dates.
- Course content hierarchy: course → sections → chapters → lessons.
- Video lessons (YouTube/Vimeo HTTPS URLs) and text lessons with English/Hindi content.
- Free preview lessons and lesson publication controls.
- External HTTPS resource links and private file attachments (PDF, Office documents and ZIP; 20 MB maximum) served only through an authenticated enrollment-checked download route.
- Student course catalogue, My Courses, course curriculum, lesson player, lesson completion and progress/resume.
- Free enrollment and paid course checkout through the existing Razorpay payment controller, signature verification and webhook processing.
- Admin enrollment report and payment history shared with test-series purchases.
- Course enrollment/progress records are separate from test-series enrollment records.

## Data model

- `courses`
- `course_sections`
- `course_chapters`
- `course_lessons`
- `course_lesson_resources`
- `course_enrollments`
- `course_lesson_progress`

The existing `payments` table is extended with nullable `course_enrollment_id`; existing `test_series_enrollment_id` records remain supported. Course purchase verification continues to use the existing Razorpay credentials, order API, signature verification and webhook idempotency.

## Authorization

- `courses.view`: admin catalogue/builder and student course pages.
- `courses.manage`: course create/update/publication.
- `lessons.manage`: sections, chapters, lessons and resource authoring.
- Student lesson access and private attachment downloads are checked server-side against active/completed enrollment, except explicitly marked preview lessons.

Existing permission slugs are reused; no duplicate RBAC permissions are introduced.

## Routes

Admin:
- `/admin/courses`
- `/admin/courses/{course}/builder`
- `/admin/courses/{course}/enrollments`

Student:
- `/student/courses`
- `/student/my-courses`
- `/student/courses/{course:slug}`
- `/student/courses/{course:slug}/lessons/{lesson}`

## Verification completed

- GitHub Actions run [42072046409](https://github.com/erkirtanraj/examtesthub/actions/runs/42072046409): PHP feature tests passed.
- The same CI run completed the Vite production build successfully.

## Verification still required

- Apply migrations 009/010 to a backed-up local or staging database and verify the application against that database.
- Run manual responsive QA at the viewport matrix in [Admin Navigation and Responsive UI](ADMIN-NAVIGATION-RESPONSIVE.md), including English and Hindi.
- Test free enrollment, preview/locked lessons, protected file downloads and progress tracking in a browser.
- Test Razorpay in sandbox with valid credentials and a configured webhook secret before production use.

No production deployment or live Razorpay transaction has been performed or verified as part of this phase.
