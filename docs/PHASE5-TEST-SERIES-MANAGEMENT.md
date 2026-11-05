# Phase 5 — Test Series Management

## Features
- Admin creates, edits, publishes, schedules and archives test series.
- A series groups published exams in a defined order.
- Free and paid series metadata (INR pricing) and optional enrollment caps.
- Student catalogue and "My test series" views.
- Free enrollment activates immediately.
- Paid enrollment is recorded as pending payment and does not grant access.
- Admin enrollment view and controlled activation after a verified payment reference is supplied.
- Access control is enforced both in the student exam catalogue and at exam start.
- Audit events for series creation, edits, status changes and enrollment activation.

## Payment boundary
The repository does not currently contain a payment gateway integration. This phase deliberately does not trust client payment claims or automatically activate paid enrollments. Paid enrollments remain pending until an administrator verifies the payment and activates the enrollment with a transaction reference. Gateway checkout, webhook signature verification, refunds and reconciliation should be implemented as a dedicated payment phase.

## Access rules
- Published series must contain at least one published exam.
- Series start/end windows and enrollment caps are enforced during enrollment.
- Exams attached to one or more series require an active enrollment in at least one currently available published series.
- Exams not attached to a test series retain the existing standalone exam access behavior.
- Admin routes require the existing `tests.manage` permission.

## Verification
Feature tests cover free enrollment, pending paid enrollment, access denial without enrollment, successful exam start with active enrollment, and admin series creation.
