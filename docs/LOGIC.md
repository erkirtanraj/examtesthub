# Business Logic

One account spans LMS, tests and notes. A catalog Product represents a course, test series, material, bundle or approved subscription. Orders preserve purchase-time price/product snapshots. Payments record gateway transactions; Entitlements are the authoritative access grants. Store money as integer minor units plus currency; timestamps in UTC.

## Authorization
Use granular permissions and object-level policies. Suggested roles: super-admin, admin, content-manager, exam-manager, finance-manager, support-agent, instructor and student. Students access only their own orders, attempts, progress and profile. Admin actions require permission and audit history.

## Catalog and pricing
Catalogue visibility, purchase availability and access are separate. Free products follow configured public-preview or registered-enrollment policy. Paid items need valid price, currency and sale window. Order item snapshots preserve title, price, discount, tax and expiry policy. Bundle composition is snapshotted. Expiry changes access, not historical records.

## Razorpay and order state
Order states: draft -> pending_payment -> paid | payment_failed | cancelled | partially_refunded | refunded. Payment states: created -> authorized | captured | failed | refunded | partially_refunded.
1. Server validates products, prices, eligibility and coupons.
2. Server creates pending order and immutable item snapshots.
3. Server creates Razorpay order with server-held credentials.
4. Server verifies checkout signature; never trust client status.
5. Webhook verifies signature against raw body and webhook secret.
6. Persist gateway event ID with unique constraint; process idempotently.
7. Reconcile payment/order and grant entitlement transactionally or through transactional outbox.
8. Browser redirect alone is never proof. Handle duplicate, delayed and out-of-order events and reconcile pending states.

Refund policy must define whether partial refund preserves, reduces or revokes access. Preserve audit history.

## Entitlements
Grant includes user, product, source order item/plan, start, expiry, status and revocation reason. Access requires active user and entitlement, start time reached, expiry not passed (or null), and no product restriction. Direct purchase grants item; bundle grants snapshotted components; subscription grants covered products while valid; free enrollment may grant free entitlement. Prevent duplicate grants. Manual grants require permission, reason and audit. All LMS, exam and PDF checks use the same service.

## LMS
Course -> Section -> Chapter -> Lesson -> Resource/Quiz. Preview is distinct from enrollment. Restricted lessons require active entitlement. Progress is user-specific and idempotent. Completion policy is configurable. Course edits must not erase historical progress. Certificates require configured completion criteria and unique verification ID.

## Exam lifecycle
States: created -> in_progress -> submitted | auto_submitted | expired | invalidated.
- Start validates publication, schedule, entitlement, attempt limit and eligibility; create transactionally and idempotently.
- Server sets started_at and ends_at; browser timer is display-only.
- Snapshot question, chosen translation, options/order, marks and scoring config at start. Edits after start do not alter the attempt.
- Answer save validates owner, attempt state, deadline, question membership and payload. Use idempotency and version checks to prevent stale-tab overwrite.
- A save is acknowledged only after durable persistence. Resume uses server state. Reject late writes and auto-finalize by server deadline.
- Submission/finalization is idempotent and scores only once.

MVP scoring: single-correct MCQ. Multi-select only after explicit all-or-nothing or partial-credit policy. Correct answers remain server-side until release. Define negative-mark floor, unanswered scoring, tie-breakers and result visibility. Re-evaluation appends audited revision. Client may buffer unsent answers, but must distinguish them from server-saved data. Proctoring is outside MVP and requires separate review.

## PDF and bilingual content
Restricted files remain private. Authorize before issuing short-lived signed URL. Signed URLs are bearer credentials; use short TTL and do not log. Download limits are not DRM; watermark is deterrence, not screenshot prevention. Audit downloads; validate and scan uploads.

Interface locale en/hi with explicit fallback. Translation rows share stable entity IDs. Question and option translations share semantic answer mapping. Missing exam translation follows explicit policy; do not silently mix languages. Snapshot locale content into attempt.

## Background jobs and audit
Queue notifications, certificates, imports, analytics, rankings and exports. Critical answer persistence and payment reconciliation must not rely on best-effort jobs. Audit privileged content/pricing changes, manual grants/revocations, payment/refund transitions, exam publication, attempt invalidation and result revisions.
