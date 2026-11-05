# ExamTestHub

ExamTestHub is a bilingual (Hindi + English) learning and exam platform built with Laravel, Vue 3 and Inertia.js. The repository contains working application modules for administration, question banks, online exams, test series, Razorpay payments and LMS courses.

## Implementation status

The following modules are implemented in the current `main` branch:

- Authentication, separate admin/student portals, role and permission checks, and audit logging.
- Admin user management, CSV import/export, subjects, topics and question-bank workflows.
- Exam authoring and the student test-taking flow.
- Free and paid test-series enrollment, with Razorpay order creation, server-side payment verification and signed webhook processing.
- LMS course administration with bilingual course details and curriculum hierarchy: course → sections → chapters → lessons.
- Free previews, paid course access, private lesson resources, student enrollment, lesson completion and progress/resume.
- Responsive portal navigation and admin/student payment history.
- Standalone PDF study-material library with bilingual titles/descriptions, subject/course organization, private storage, filtering and enrollment-protected downloads.

Phase 7 LMS Course Management was merged into `main` in November 2026. Its final GitHub Actions run passed the PHP feature-test job and the Vite production-build job. See [Phase 7 implementation notes](docs/PHASE-7-LMS-COURSE-MANAGEMENT.md) for scope and remaining manual verification.

Phase 8 adds a standalone PDF materials library. Files are stored on the private `local` disk; materials assigned to a course require an active or completed enrollment in that course. See [Phase 8 implementation notes](docs/PHASE-8-PDF-STUDY-MATERIALS.md) for details and verification steps.

**Production readiness is not implied by a passing CI run.** Database migrations must be tested against the target database, responsive behavior must be checked in browsers/devices, and Razorpay must be verified with sandbox credentials and a configured webhook before production use. No production deployment or live payment acceptance is claimed here.

## Technology

- PHP 8.3+ and Laravel 13
- Vue 3, Inertia.js and Tailwind CSS 4
- Vite for frontend builds
- MySQL 8+ or a verified compatible MariaDB version
- Razorpay for checkout and payment verification

Redis-backed queues/cache, large-scale capacity and production multi-node deployment require environment-specific configuration and validation; they are not capacity claims of this repository.

## Local development

Prerequisites: PHP 8.3+, Composer, Node.js/npm, and a supported database.

1. Clone the repository and install dependencies:

   ```powershell
   composer install
   npm install
   ```

2. Create the local environment file and application key:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Configure the database values in `.env`. Review migrations before applying them:

   ```powershell
   php artisan migrate:status
   php artisan migrate --pretend
   php artisan migrate
   ```

4. Build the frontend and start the application:

   ```powershell
   npm run build
   php artisan serve
   ```

For frontend development with hot reload, use `npm run dev` in a second terminal.

## Tests and build

Run the PHP feature suite and frontend production build:

```powershell
php artisan test
npm run build
```

The GitHub Actions workflow also runs Composer validation, PHP tests and the frontend build for pull requests.

## Razorpay configuration

Set the following values only in the local/deployment environment. Never commit API secrets or webhook secrets:

```dotenv
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
RAZORPAY_BASE_URL=https://api.razorpay.com/v1
```

Use Razorpay test credentials and configure the signed webhook URL before testing checkout end to end. Payment activation depends on server-side verification, not the browser callback alone.

## Project documentation

- [Product requirements](docs/PRD.md)
- [Technical requirements](docs/TRD.md)
- [Business logic](docs/LOGIC.md)
- [UI/UX specification](docs/UI.md)
- [Architecture decisions](docs/ADR.md)
- [Roadmap](docs/ROADMAP.md)
- [Delivery and QA plan](docs/DELIVERY.md)
- [Phase 6 — Payment Gateway](docs/PHASE6-PAYMENT-GATEWAY.md)
- [Phase 7 — LMS Course Management](docs/PHASE-7-LMS-COURSE-MANAGEMENT.md)
- [Phase 8 — PDF & Study Materials](docs/PHASE-8-PDF-STUDY-MATERIALS.md)
- [Admin Navigation and Responsive UI](docs/ADMIN-NAVIGATION-RESPONSIVE.md)

## Capacity statement

Any target such as 50,000 concurrent active users requires production-like load, spike, soak, failure and recovery testing on the intended infrastructure. It is a target to validate, not a current performance or capacity claim.
