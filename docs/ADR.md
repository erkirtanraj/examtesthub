# Architecture Decision Records

Current decisions describe the proposed baseline. Amend or supersede rather than silently changing architecture.

1. **Modular monolith before microservices — Accepted.** One Laravel app organized by bounded contexts; revisit only when independent scaling/deployment or fault isolation is evidenced.
2. **Vue 3 + Inertia web — Accepted.** Reactive web experience with one Laravel business layer and simpler initial deployment.
3. **REST + Sanctum — Accepted.** Versioned /api/v1 contract for future Android and integrations.
4. **Central catalog and entitlements — Accepted.** Orders purchase catalog products; one service grants/revokes access consistently across LMS, exams and materials.
5. **Razorpay server verification — Accepted.** Verify checkout and webhook signatures; unique event IDs and idempotent reconciliation. Browser redirect is not proof.
6. **Redis — Accepted; topology pending.** Shared cache/session/queue for multi-node readiness; select managed/HA setup after infrastructure discovery.
7. **Private object storage — Accepted.** Restricted PDFs stored privately; authorize before short-lived signed URL issuance.
8. **Snapshot-based exam runtime — Accepted.** Server-authoritative timer and immutable attempt content/scoring snapshots.
9. **CloudPanel initial deployment — Accepted.** Initial control plane, not evidence of 50k capacity.
10. **Translation rows — Accepted.** Stable domain IDs with per-locale records.

## Open decisions
Bigint vs ULID; exact Laravel/PHP versions after compatibility check; permission package; video/object-storage vendor; email/SMS/OTP provider; tax/invoice rules; subscription/bundle/refund policies; concurrency workload/topology/SLO/budget.
