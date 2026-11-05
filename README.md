# ExamTestHub

Bilingual (Hindi + English) EdTech platform for free/paid LMS courses, online test series and PDF notes. Target stack: Laravel modular monolith, Vue 3 + Inertia, MySQL/MariaDB, Redis, Razorpay, CloudPanel initial deployment and versioned REST API for future Android client.

> Status: Documentation baseline only. The repository was empty when inspected; no application implementation is represented as complete. 50,000 concurrent users is a capacity target that requires production-like load and failure testing, not a current guarantee.

## Project documents
- [Roadmap](docs/ROADMAP.md)
- [Business Logic](docs/LOGIC.md)
- [PRD](docs/PRD.md)
- [TRD](docs/TRD.md)
- [UI/UX Document](docs/UI.md)
- [Architecture Decisions](docs/ADR.md)
- [Delivery and QA Plan](docs/DELIVERY.md)

## Build sequence
Discovery/sign-off -> foundation/auth/RBAC/i18n -> catalog/orders/entitlements -> LMS -> question bank and exam runtime -> results -> PDF library -> API -> security/performance -> controlled production rollout.
