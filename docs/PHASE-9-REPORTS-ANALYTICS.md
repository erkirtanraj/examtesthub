# Phase 9 — Reports, Analytics & Performance Dashboard

## Scope

Phase 9 adds operational reporting for administrators and a private performance view for each student. It reads existing platform records and introduces no analytics tables or generated sample metrics.

### Admin dashboard

Route: GET /admin/reports (requires the existing reports.view permission).

The reporting window can be set to 7, 30 or 90 days.

- Student registrations: total accounts assigned the student role, plus new student registrations created in the selected window.
- Exam activity: submitted attempts in the selected window and the average score percentage across attempts with a positive total-mark value.
- Revenue: captured payments with a verification timestamp in the selected window. Amounts are converted from paise to INR.
- Enrollments: course and test-series enrollments with active or completed status and an enrolled_at timestamp in the selected window.
- Trends: daily submitted attempts, average attempt percentage, captured payment revenue, course enrollments and test-series enrollments.
- Most attempted exams: up to eight exams ranked by submitted attempt count, with distinct student count and average score percentage.
- Recent activity: latest submitted exam attempts and captured payments within the selected window.

The overview page links to the reports dashboard. The reports route is permission-controlled using reports.view; the existing platform seeder already assigns that permission to super-admin, admin and exam-manager roles.

### Student performance

Route: GET /student/performance (requires results.view-own).

The page shows the authenticated student's own submitted attempts only:

- Total submitted attempts.
- Average and best score percentage over submitted attempts with a positive total-mark value.
- Active or completed course and test-series enrollments.
- Up to twelve latest submitted exam results, including score, correct, incorrect and unanswered counts.
- A compact score-history visualization for the latest results.

All attempt queries are scoped by the authenticated user ID. The page does not return question answers, answer keys or another student's information.

## Metric definitions and caveats

- An in-progress attempt is excluded from performance and attempt reports.
- Score percentage is calculated as score / total_marks × 100. Negative-marking scores may therefore be negative or exceed neither a guaranteed pass threshold nor a grade.
- No pass/fail classification is added because the current exam schema does not store an exam-specific passing threshold.
- Revenue reports use captured payment records with verified_at. Pending, failed or unverified payments are excluded.
- Student total is a lifetime count of accounts with the student role; new registrations are period-scoped.
- Enrollment reporting counts active/completed enrollments by enrolled_at, not pending-payment records.
- The reporting window uses the application timezone. The dashboard is an aggregate operational view, not an accounting ledger or downloadable statutory financial statement.

## Database and deployment

No migration is added in Phase 9. The feature uses the existing users/roles, exam_attempts, exams, payments, course_enrollments and test_series_enrollments tables.

## Verification

Feature tests cover:

- Admin report permission enforcement.
- Aggregate attempt, score, student, enrollment and captured-payment metrics.
- Exclusion of pending payments from revenue.
- Rejection of unsupported reporting windows.
- Student performance permission enforcement.
- Isolation of a student's performance data from other students.

Manual verification should include both English and Hindi locale, 7/30/90-day filters, responsive layouts, and a comparison of dashboard totals with the relevant records in the target database.
