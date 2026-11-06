# Phase 17 — Payment & Revenue Management

## Scope delivered

- Admin payment dashboard with 7/30/90/365-day reporting windows.
- Search by student name/email and Razorpay payment/order reference.
- Status filters for pending, captured, failed, refund pending and refunded payments.
- Gross collections, completed refunds, net revenue, failed payment and pending-refund totals.
- Admin-initiated full refunds through the Razorpay API, with a required reason.
- Refund tracking using the provider refund ID and a dedicated `refund_pending` state.
- Signed, event-ID-idempotent handling of Razorpay `refund.processed` webhooks.
- Access is revoked only after the provider confirms a completed full refund.
- Student-owned printable payment receipts with a Print / Save as PDF action.
- Payment list and refund actions are protected by `payments.view` and `payments.manage`.

## Razorpay setup

Keep existing Razorpay API credentials configured:

```dotenv
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
RAZORPAY_BASE_URL=https://api.razorpay.com/v1
```

Add the `refund.processed` event to the existing Razorpay webhook subscription. The endpoint remains:

`https://YOUR-DOMAIN/api/v1/payments/razorpay/webhook`

Use the same dedicated webhook secret. Never expose the API secret or webhook secret to the browser.

## Refund safety

- Only a captured payment with a provider payment ID can be refunded.
- This implementation requests a full refund; partial refunds are not enabled.
- A payment moves to `refund_pending` before the provider request is made.
- A completed API response or verified `refund.processed` webhook moves it to `refunded` and changes its enrollment status to `refunded`.
- A timeout or ambiguous server-side provider error remains `refund_pending` to avoid accidentally issuing a duplicate refund. Check Razorpay before taking further action.
- A clear provider 4xx rejection restores the payment to `captured`.
- Refund events are idempotent by Razorpay event ID.

## Receipt note

The student-facing document is a payment receipt, not a GST tax invoice. GST registration, tax calculations, invoice numbering, business address and statutory invoice fields have not been configured.

## Out of scope

Recurring subscriptions, subscription renewals, coupon/discount campaigns, partial refunds, settlement reconciliation and GST-compliant tax invoices require separate business rules and provider configuration. This phase does not enable recurring billing.

## Deployment

Back up the database, then run:

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
```

The seeder synchronizes role permissions; review custom role assignments before running it in production. Configure Razorpay test-mode refunds and webhook delivery before enabling this feature for live transactions.
