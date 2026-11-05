# ExamTestHub

Bilingual (Hindi + English) EdTech platform for free/paid LMS courses, online test series and PDF notes.

## Current implementation status

The documentation baseline is preserved. The initial Laravel/Vue application foundation is now scaffolded on the development branch **feature/phase-1-foundation**. It includes the Laravel application entry points, Vue 3 + Inertia landing page, basic configuration, initial user schema, versioned API health route and CI workflow.

**Important:** This is an early scaffold, not a production-ready application. Composer/npm dependency resolution, local execution, automated test results and CI status have not yet been verified in a PHP/Node runtime. Authentication workflows, RBAC, Redis-backed infrastructure and all commerce/LMS/exam/PDF modules remain future work.

## Project documents

- [Roadmap](docs/ROADMAP.md)
- [Business Logic](docs/LOGIC.md)
- [PRD](docs/PRD.md)
- [TRD](docs/TRD.md)
- [UI/UX Document](docs/UI.md)
- [Architecture Decisions](docs/ADR.md)
- [Delivery and QA Plan](docs/DELIVERY.md)
- [Phase 1 implementation notes](docs/PHASE1.md)

## Technology baseline

- Laravel 13 / PHP 8.3+
- Vue 3 + Inertia.js
- Tailwind CSS 4 + Vite
- MySQL 8+ or verified compatible MariaDB
- Redis planned for shared cache, sessions and queues
- Sanctum for future API authentication
- Razorpay planned for payment processing

Dependency versions must be resolved and locked in a real development environment before deployment.

## Build sequence

Foundation -> authentication/RBAC/i18n -> catalog/orders/entitlements -> LMS -> question bank and exam runtime -> results -> PDF library -> REST API -> security/performance -> controlled production rollout.

## Capacity statement

50,000 concurrent active users is a capacity target requiring production-like load, failure and recovery testing. It is not a current capacity claim. A single small CloudPanel VPS is not sufficient evidence for this target.
