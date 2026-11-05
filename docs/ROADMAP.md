# ExamTestHub Roadmap

Status: proposed plan; dates depend on team and infrastructure discovery.

| Phase | Scope | Deliverables and exit gate |
|---|---|---|
| 0 Discovery | Audience, subjects, pricing, policies, domain, hosting, workload and SLO | PRD/TRD/UI/ADR sign-off; open blockers resolved |
| 1 Foundation | Laravel, CI, environments, auth, profile, RBAC, audit, i18n | Staging skeleton; secure login; bilingual shell; CI green |
| 2 Commerce | Catalog, pricing, orders, Razorpay, webhooks, entitlements | Signature, replay and duplicate-event tests pass; paid access only after server verification |
| 3 LMS | Courses, sections, chapters, lessons, resources, progress | Free/paid enroll, learn and resume journeys pass |
| 4 Exam authoring | Subjects, question bank, translations, import, tests and schedules | Import validation, revisions and preview pass |
| 5 Exam runtime | Attempts, snapshots, server timer, autosave, resume, submit, scoring | Integrity, disconnect/resume and scoring tests pass |
| 6 Results | Explanations, results, rankings and analytics | Scoring/ranking verified; reporting separated from hot write path |
| 7 PDF library | Materials, private storage, authorization, expiring links, audit | Unauthorized file access denied; link expiry verified |
| 8 API | Sanctum, /api/v1, OpenAPI and quotas | Contract tests and token revocation pass |
| 9 Hardening | Security, monitoring, backups, restore, load/failure tests | SLO/capacity gates pass on production-like topology |
| 10 Production | DNS/SSL, deployment, support, monitoring and rollback | Smoke tests and rollback drill pass |

## Indicative sequence
A 13-week schedule is an optimistic hypothesis for a focused team, not a commitment: W1 discovery; W2 foundation; W3 commerce; W4-5 LMS; W6-7 question bank and exam; W8 results; W9 PDFs; W10 API; W11-12 hardening and load tests; W13 pilot. A 50,000-user capacity validation may require additional work.

## Priorities
**P0 paid pilot:** auth/RBAC, bilingual UI, products/orders/payments/entitlements, free and paid LMS, question bank, timed exam with durable autosave/resume/submission/scoring, private PDF access, admin, audit, backups and monitoring.

**P1 public release:** coupons/refunds/invoices, schedules/randomization/bookmarks, analytics, certificates/notifications, API quotas, restore drills and security review.

**P2 later:** Android app, subscriptions/bundles after policy approval, advanced search/BI, multi-tenant support if needed. Proctoring needs separate privacy/legal/accessibility review.

## Release gates
- Alpha: P0 journeys pass staging; no critical/high security issues; payment and exam integrity tests pass.
- Pilot: capped cohort and exam traffic; monitoring/support/reconciliation ready; restore and rollback rehearsed.
- GA: production-like workload passes; capacity/SLO evidence approved; privacy, payment, accessibility and operations signed off.

## Key risks
Unknown concurrency mix -> define exam share, RPS, autosave cadence, bursts and SLO. Single VPS -> multiple app nodes and separate DB/cache/storage. Duplicate webhooks -> signature verification/idempotency/reconciliation. Lost answers -> durable acknowledgment, retries and resume tests. Active content edits -> immutable attempt snapshots. Media bandwidth -> private object storage and CDN. Scope expansion -> P0/P1/P2 governance.

## Governance
Each milestone needs an owner and acceptance evidence. Use feature branches and PR review. Database migrations should be backward-compatible where possible. Production changes require release notes and rollback instructions.
