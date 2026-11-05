# Engineering Delivery & Quality Plan

## Branching and review
Protect main; no direct production commits. Use short-lived feature/fix/docs branches. PR includes outcome, linked requirement, tests, migration impact and rollback notes. Review security-sensitive changes. Never commit secrets or production data. Tag releases and maintain notes.

## Definition of ready
Outcome and acceptance criteria clear; dependencies and permissions known; UI/API impact documented; migration/backfill and rollback considered; security/privacy risk reviewed.

## Definition of done
Code follows module boundaries; server-side validation/authorization; tests updated; API/OpenAPI and UI docs updated; migrations/indexes reviewed; operational logs/metrics for critical paths; no secrets in logs; bilingual/accessibility verified; CI and review pass; deployment/rollback notes included.

## Test layers
1. Unit: pricing, eligibility, entitlement, scoring, expiry, translation fallback.
2. Feature: student/admin journeys and policy enforcement.
3. Integration: Razorpay, webhooks, object storage, Redis, queues.
4. Security: IDOR, role bypass, payment replay, answer leakage, file access, rate limits.
5. Browser: registration, purchase, course, exam, PDF in both locales.
6. Contract: OpenAPI and Android-oriented API behavior.
7. Performance: k6 staged, spike, soak and failure scenarios.
8. Recovery: backup restore, rollback and supported failover.

## Critical tests
Payments: valid/invalid signatures, tampered amount, duplicate/out-of-order webhook, redirect/webhook ordering, timeout, reconciliation, refund and coupon race.

Exams: idempotent start, attempt-limit race, answer retry/stale write, disconnect/resume, offline deadline, submit retry/concurrency, edit after snapshot, answer-key secrecy, scoring edge cases and result revision.

Files: no/expired/revoked entitlement, signed URL expiry, cross-user ID guessing, malicious/oversized upload, watermark and quota behavior.

## Release runbook
Confirm release commit/CI; test migrations against production-like data; verify backup/restore point; deploy immutable build; apply safe migrations; rebuild framework caches; gracefully restart workers; run health and student-journey smoke tests; monitor errors/queues/DB/exam-save metrics. Roll back code on failed checks; never blindly reverse destructive migrations.

## Incident playbooks before GA
Payment pending/duplicate reconciliation; exam autosave degradation; queue backlog; unauthorized PDF access; database/provider outage; deployment rollback and credential rotation.
