# Phase 1 — Application Foundation

## Scope in this commit
- Laravel 13 / PHP 8.3 baseline, pending dependency resolution and CI verification.
- Vue 3 + Inertia + Vite + Tailwind CSS 4 application shell.
- Bilingual landing-page prototype (English/Hindi toggle in current page state).
- Versioned API health endpoint and Sanctum-protected identity endpoint.
- CI workflow for PHP tests and frontend production build.

## Not yet implemented
Authentication screens and workflows, persistent locale middleware, database migrations/configuration, Redis connectivity, RBAC, admin dashboard, audit logging, payment, LMS and examination features. These remain follow-on work and must not be represented as complete.

## Local verification checklist
1. Run Composer validation and install dependencies.
2. Copy .env.example to .env and configure local DB credentials.
3. Generate the application key.
4. Install npm dependencies and build frontend assets.
5. Run the Laravel test suite.

A composer.lock and package-lock.json must be generated and committed after dependency resolution in a real PHP/Node environment. CI must pass before this phase is considered complete.
