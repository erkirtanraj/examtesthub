# ExamTestHub Development Roadmap and Merge Policy

Last updated: 2026-11-06

## Project direction

Build ExamTestHub on its existing Laravel architecture, using these repositories as feature references:

- [Examforge](https://github.com/erkirtanraj/Examforge)
- [LMS_Quiz_Website](https://github.com/erkirtanraj/LMS_Quiz_Website)

Port suitable capabilities into Laravel conventions. Do not copy incompatible architecture or duplicate features that ExamTestHub already provides. Reuse existing authentication, authorization, exam, LMS, payment, notification, reporting, and audit patterns wherever appropriate.

## Mandatory development and merge workflow

This policy applies to **every feature and every phase until the complete agreed feature roadmap is finished**.

1. Inspect the current repository and relevant reference implementation before coding.
2. Create a dedicated feature branch from the latest integration branch. Never develop directly on the integration branch.
3. Implement one coherent feature/phase at a time.
4. Add or update automated tests, migrations, authorization checks, validation, and documentation as applicable.
5. Run the relevant backend tests, frontend checks/build, and CI workflows. Record any checks that could not be run and why.
6. Open a pull request with scope, implementation notes, test evidence, risks, and migration/deployment notes.
7. **Before every merge into the main/integration branch, perform a fresh review of the complete PR diff and changed files.** Check correctness, security, authorization, data integrity, regressions, test coverage, migrations, and compatibility with previously merged phases.
8. Fix all actionable review findings, rerun affected tests/checks, and review the updated diff again.
9. Present the review result and CI status to the repository owner and wait for explicit approval to merge.
10. Merge only after that approval. Do not auto-merge, bypass review, or merge merely because CI is green.
11. After merge, verify the resulting integration branch and update this roadmap with the phase status and PR/commit reference.
12. Do not deploy to production unless separately and explicitly instructed.

**Stop condition:** This review-before-merge rule remains active through the final feature in the roadmap. It is not a one-time instruction and must be repeated for every PR.

## Current repository baseline

- Existing ExamTestHub capabilities include Laravel authentication/roles, question bank, exam engine, test series, course/LMS functionality, payment handling, PDF study materials, notifications, reports/analytics, and admin bulk actions.
- Admin bulk actions were delivered in PR #27: https://github.com/erkirtanraj/examtesthub/pull/27
- PR #27 added pagination-safe selection and guarded bulk operations across admin content areas, preserving dependency protections and authorization.
- Do not reimplement those capabilities without first identifying a concrete gap.
- Coupon/discount functionality is the next planned feature. The branch `feature/coupon-code-discounts` was created from the post-PR-#27 baseline; implementation is not yet recorded as complete.

## Feature roadmap

Track each item as Not started, In progress, In review, Approved, or Merged. Update this file after each phase.

| Order | Feature / phase | Reference | Status | Acceptance focus |
|---:|---|---|---|---|
| 1 | Coupon and discount management | Examforge + LMS_Quiz_Website | In progress (coupon schema, pricing service, payment fields, and admin CRUD UI/routes committed; checkout integration, redemption accounting, tests, and review pending) | Admin CRUD; percentage/fixed discounts; validity and usage limits; minimum spend; course/test-series scope; server-side checkout calculation; payment audit trail; atomic usage consumption; tests for invalid, expired, exhausted, scoped, and valid coupons |
| 2 | Student leaderboard and ranking | Examforge | Not started | Fair ranking rules, privacy controls, pagination, anti-abuse safeguards, tests |
| 3 | Exam sections and advanced attempt controls, including pause/resume where permitted | Examforge | Not started | Server-authoritative timers and attempt state; safe resume rules; section navigation; auditability; tests |
| 4 | Course/test-series bundles | LMS_Quiz_Website | Not started | Bundle catalogue, pricing, entitlements, purchase lifecycle, access checks |
| 5 | Student wishlist / saved courses | LMS_Quiz_Website | Not started | Authenticated user ownership, duplicate prevention, usable student UI, tests |
| 6 | Advanced student analytics | Both references | Not started | Accurate aggregation, date filters, role-scoped data, performance and privacy |
| 7 | Teacher and doubt-management portal | LMS_Quiz_Website | Not started | Role permissions, doubt lifecycle, teacher assignment, notifications, audit trail |
| 8 | Certificates and public verification | Examforge | Not started | Eligibility rules, unique verification, revocation/status, privacy-safe public page |
| 9 | Push notifications and mobile API improvements | LMS_Quiz_Website | Not started | Authenticated device registration, preferences, queue/retry handling, API authorization |
| 10 | Advanced SEO and marketing tools | LMS_Quiz_Website | Not started | Metadata, canonical URLs, sitemap/structured data, safe content controls, tests |

Roadmap order may be changed only after documenting the reason and impact. Add newly agreed features to this file before implementation.

## Cross-cutting quality requirements

- Follow Laravel project conventions and existing code patterns.
- Enforce authorization on the server for every privileged operation; never rely on hiding UI controls.
- Validate all user input and calculate all prices/discounts/entitlements server-side.
- Use database transactions and appropriate locking/idempotency for payment and inventory/usage-sensitive operations.
- Preserve historical records and protect records with dependencies.
- Add migrations that are safe for existing data and document rollback/operational considerations.
- Keep secrets out of source control and logs.
- Add automated tests for success paths, failure paths, authorization, boundary conditions, and regressions.
- Maintain accessible, responsive student and admin interfaces.
- Avoid duplicate lists or duplicate workflows when extending existing admin screens.
- Update this roadmap and relevant project documentation as each feature progresses.

## Phase completion checklist

- [ ] Requirements and reference behavior inspected
- [ ] Existing implementation checked to avoid duplication
- [ ] Dedicated feature branch created from latest integration baseline
- [ ] Backend/frontend implementation complete
- [ ] Authorization, validation, data integrity, and security reviewed
- [ ] Automated tests added and passing
- [ ] Frontend production build/checks passing
- [ ] CI workflows passing
- [ ] Full PR diff reviewed before merge
- [ ] Review findings fixed and re-reviewed
- [ ] Explicit repository-owner approval received
- [ ] PR merged only after approval
- [ ] Integration branch verified after merge
- [ ] Roadmap status and PR/commit links updated
- [ ] Production deployment separately authorized, if requested
