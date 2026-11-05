# Technical Requirements Document (TRD)

**Status:** Proposed target architecture; not implemented. **Style:** API-first Laravel modular monolith.

## Stack
Supported Laravel release; PHP 8.3+ subject to host/framework compatibility; Vue 3 + Inertia; Tailwind; MySQL 8+ or compatible MariaDB after feature verification; Redis for cache/session/queue; Laravel session auth + Sanctum; policies/RBAC; Razorpay signed webhooks; private S3-compatible object storage; external video CDN; OpenAPI; Pest/PHPUnit; k6; Nginx/PHP-FPM; CloudPanel initially. Lock versions in Phase 1 after compatibility, support, advisories and license checks.

## Modules and organization
Bounded contexts: Identity, Catalog, Commerce, Entitlements, LMS, Assessment Authoring, Exam Runtime, Study Materials, Notifications, Reporting/Audit, Admin.

```text
app/Domain/{Identity,Catalog,Commerce,Entitlements,Learning,Assessment,ExamRuntime,StudyMaterials,Notifications,Reporting}/
app/Application/{Actions,DTOs,Queries,Commands}/
app/Infrastructure/{Payments/Razorpay,Storage,Queue,Observability}/
app/Http/{Controllers,Middleware,Requests,Resources}/
app/Policies/
resources/js/{Pages,Components,Layouts,i18n}/
routes/{web.php,api.php}
database/{migrations,seeders}
tests/{Unit,Feature,Integration,Security,Browser,Performance}
```
Keep controllers thin; use application services and policies. Avoid premature abstractions.

## Core data model
Use consistent bigint or ULID strategy (ADR required), foreign keys, UTC timestamps and intentional soft deletes. Translation rows keyed by entity and locale.

**Identity:** users, profiles, roles, permissions, model_has_roles, model_has_permissions, Sanctum personal_access_tokens, audit_logs.

**Catalog:** products(id,type,validated entity reference,status,published_at), product_prices(currency,amount_minor,validity), categories/mappings and translations. Prefer stable products catalog over unvalidated polymorphic entitlement IDs.

**LMS:** courses/course_translations/categories; instructors/course_instructors; course_sections, chapters, lessons/lesson_translations; lesson_resources, files; enrollments(user_id,course_id,entitlement_id,status); lesson_progress(user_id,lesson_id,status,last_position,completed_at); optional reviews. Unique enrollment/progress identities.

**Assessment authoring:** subjects/topics; questions(status,type,version,difficulty,created_by); question_translations; question_options and option_translations; protected question_answers; question_explanations/translations; tests, test_sections, test_questions; test_series, test_series_tests, schedules; import_batches/import_rows.

**Exam runtime:** test_attempts(user_id,test_id,status,started_at,ends_at,submitted_at,locale,idempotency_key); attempt_questions(attempt_id,source_question_id,question_version,display_order,snapshot_payload); attempt_answers(attempt_id,attempt_question_id,answer_payload,version,saved_at); attempt_events; test_results(attempt_id,revision,score,max_score,accuracy,evaluated_at,released_at); rebuildable leaderboard_snapshots. Unique (attempt_id,attempt_question_id), scoped idempotency. Never serialize answer keys before release.

**Materials:** study_materials, material_translations, categories; files(storage_disk,object_key,mime_type,size_bytes,checksum,visibility); material_files; material_downloads/access audit.

**Commerce:** orders(user_id,order_number,currency,subtotal_minor,discount_minor,tax_minor,total_minor,status); order_items with product and purchase-time snapshots; payments(order_id,gateway,gateway_order_id,gateway_payment_id,amount_minor,currency,status); payment_webhooks unique (gateway,event_id); refunds; coupons/redemptions; subscriptions only if approved; user_entitlements(user_id,product_id,source_order_item_id,starts_at,expires_at,status,revoked_at,reason); entitlement_events.

## Index candidates
Validate with EXPLAIN: products(status,type,published_at); enrollments(user_id,course_id) unique; lesson_progress(user_id,lesson_id) unique; tests(status,availability); test_questions(test_id,sort_order); test_attempts(user_id,test_id,status,started_at); attempt_answers(attempt_id,attempt_question_id) unique; payments(gateway,gateway_payment_id) unique; payment_webhooks(gateway,event_id) unique; user_entitlements(user_id,product_id,status,starts_at,expires_at). Avoid unnecessary high-write indexes.

## Transactions
Order/items snapshot in one DB transaction. Never hold transaction across Razorpay network calls. Verify and persist unique webhook before idempotent reconciliation. Grant entitlement in same DB transaction as order state or use transactional outbox. Attempt start transactionally enforces limits and snapshots questions. Answer save validates owner/state/deadline/membership/version. Submission locks/transitions attempt once and evaluates snapshot. Re-evaluation appends revision/audit.

## API
Base /api/v1; JSON UTF-8; UTC ISO timestamps; money in minor units plus currency; bounded pagination and stable errors.
- /auth/register, /auth/login, /auth/logout, /auth/me
- /courses, /courses/{slug}, /enrollments, /lessons/{id}, /lessons/{id}/progress
- /test-series, /tests/{id}, /tests/{id}/attempts
- /attempts/{id}, /attempts/{id}/answers, /attempts/{id}/submit, /attempts/{id}/result
- /study-materials, /study-materials/{id}, /study-materials/{id}/download
- /orders, /payments/razorpay/orders, /payments/razorpay/verify
- Permission-protected admin routes.

Use Sanctum, Form Requests, API Resources, rate limits, OpenAPI, token revocation and Idempotency-Key on retry-sensitive writes. Never expose answer keys before release, private object keys, secrets or webhook payloads.

## Exam runtime
Server sets started_at/ends_at; client timer is display only. Snapshot locale text, option order, question version, marks and scoring policy. Save small answer payloads with durable acknowledgment, request IDs and version checks. Resume from server state; reject late writes; auto-finalize at deadline. Submit idempotently and evaluate once. Keep reports off write path; cache immutable test metadata. Load test DB pool, IOPS, lock contention and answer-save latency.

## Payment and storage
Keep Razorpay credentials in secrets. Verify checkout and webhook signatures against raw body; unique event IDs; handle duplicate/delayed events; reconcile pending payments. PDFs private; authorize before short-lived signed URL. Validate and scan uploads. Signed delivery cannot prevent redistribution after download.

## Localization
Locale middleware resolves en/hi. UI translation catalogs; content translation rows. Question semantics shared; attempts record and snapshot chosen locale. Test fallback.

## Security and operations
TLS, secure cookies, strong password hashing, admin MFA, least privilege, CSRF, output escaping, upload validation, object-level authorization, rate limits, secret rotation, encrypted backups, audit, dependency scanning, privacy/retention/deletion workflow. Threat model: account takeover, payment replay, entitlement spoofing, answer leakage, IDOR, exam abuse and file URL leakage.

Track request rate and p50/p95/p99; error rate; PHP-FPM; queue lag; DB connections/locks/slow queries; Redis memory/hit rate; webhook backlog; answer-save latency/failures; active attempts. Alert on saturation and integrity errors. Define RPO/RTO, back up and test restore. Maintain deploy, rollback, restore, reconciliation and exam-incident runbooks.

## Scale and deployment
Pilot: CloudPanel Nginx/PHP-FPM, secured DB/Redis, private object storage and external media delivery. Scale: Cloudflare/WAF -> load balancer -> stateless Laravel nodes -> Redis HA -> MySQL primary/read replicas -> queue workers; private object storage/CDN. Shared sessions/cache/queues/storage are required for horizontal scaling. A single small VPS is not a 50k-user topology.

Define exam share, requests/user/minute, answer-save interval/payload, login/start and result bursts, geography, cache ratio and SLO. Run staged, spike and soak k6 tests; publish topology, scripts, RPS, p95/p99, DB writes, metrics and failures. Validate failover/recovery; do not claim 50k before evidence. Use separate local/CI/staging/production secrets and databases; docroot is Laravel public/; use immutable builds, safe migrations, graceful worker restart, health checks and rollback. Privileged server changes require authorized admin.
