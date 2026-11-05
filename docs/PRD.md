# Product Requirements Document (PRD)

**Version:** 1.0 draft. **Status:** Review baseline. **Locales:** Hindi and English. **Clients:** responsive web first; future Android through REST API.

## Summary and goals
A single platform for free/paid courses, test series, timed exams, results and PDF notes. One student account, Razorpay checkout and centralized entitlements. Goals: reliable payment reconciliation; high-integrity exams; bilingual UI/content; reusable API; evidence-based path to 50,000 simultaneous active users.

## Non-goals for initial release
Native Android implementation, video transcoding, AI tutor, proctoring, multi-tenant white-label, subjective grading unless separately scoped, or an unverified 50k capacity guarantee.

## Personas
Guest visitor; student; instructor; content manager; exam manager; finance manager; support agent; administrator.

## Functional requirements
Priority: Must=P0, Should=P1, Could=P2.

### Identity and catalogue
- Must: secure registration/login, password reset, profile, locale, RBAC and object-level policies.
- Must: browse/search/filter courses, test series and notes; detail pages show content, language, price, access and expiry; distinct free/paid/owned/unavailable states.
- Should: OTP, admin MFA, reviews, coupons, bundles and featured content.

### LMS
- Must: manage courses/sections/chapters/lessons/resources; free preview and paid enrollment; video provider integration; progress/resume; entitlement checks.
- Should: quizzes, assignments, certificates and announcements.

### Test series and exams
- Must: manage series/tests/sections/questions/options/explanations; translations and validated bulk import; duration/marks/negative marking/schedule/attempt limits; server timer; durable autosave; idempotent submit; question snapshots; objective scoring.
- Should: randomization, bookmarks, rankings and analytics.
- Could: manual grading or proctoring.

### PDF notes
- Must: categorized free/paid materials, bilingual metadata, private storage, entitlement-checked preview/download, expiring signed URLs and audit.
- Should: watermark, quotas and bulk import.

### Commerce, admin and API
- Must: catalog/prices/orders/item snapshots, Razorpay checkout, server signature verification, signed webhooks, idempotent reconciliation, entitlement provisioning, student order history.
- Must: permission-based admin tools for students/content/tests/materials/orders/payments and validated imports.
- Must: versioned /api/v1, Sanctum, validation, pagination, rate limits, OpenAPI and core journey APIs.
- Should: coupons, refunds, invoices, subscriptions and bundles.

## Core journeys
Free course: browse -> detail -> register if needed -> enroll -> lesson -> progress -> resume. Paid test: browse -> checkout -> Razorpay -> verify -> entitlement -> attempt -> result. Paid PDF: detail -> checkout -> verify -> entitlement -> authorized preview/download. Exam: eligible test -> snapshot -> answer/save -> disconnect/resume -> submit/auto-submit -> evaluate -> release result.

## Non-functional goals (not measured guarantees)
- Capacity: representative workload for 50,000 active concurrent users.
- Proposed availability objective: 99.9% monthly for core journeys, pending approval.
- Initial p95 goals: cached read <500 ms; answer write <300 ms under agreed load.
- No acknowledged answer loss in tested failure scenarios; idempotent payment and submission.
- OWASP-aligned security review, least privilege, TLS and protected secrets.
- Accessibility target WCAG 2.2 AA review; complete reviewed Hindi/English UI.
- Logs, metrics, tracing, alerts; approved RPO/RTO and tested restore; privacy/retention/deletion process.

## Success metrics
Registration/activation, free-to-paid conversion, checkout success, webhook exceptions, lesson completion, exam completion, answer-save failures, resume success, scoring correctness, file authorization outcomes, p95/p99 latency and support incidents.

## MVP acceptance
Student registers and chooses language. Admin publishes free/paid courses, tests and PDFs. Razorpay sandbox verification works and duplicate webhook does not duplicate grants. Unpaid content is blocked. Lessons resume. Exam survives disconnect with acknowledged answers, uses snapshots and server deadline, and scores deterministically. Restricted PDF denied without entitlement and delivered via expiring authorized URL. Admin actions are authorized/audited. API, security, backup, monitoring and load gates pass before production scale release.

## Open decisions
Domain/brand; initial subjects and question types; free access, expiry, refunds, subscription/bundle policy; tax/invoice review; SMS/OTP/email vendor; video/storage vendor; guest checkout; privacy/retention/age/support rules; team and schedule; precise 50k workload (exam share, requests, autosave interval, peak bursts, geography and SLO).
