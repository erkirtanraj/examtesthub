# Phase 6 — Payment Gateway Integration

## Scope

- Razorpay order creation from the server using INR minor units (paise).
- Student checkout through Razorpay Checkout.
- Server-side checkout signature verification followed by a provider-side payment fetch.
- Verify order ID, payment ID, captured status, amount, and currency before activating enrollment.
- Signed webhook endpoint with event-ID idempotency for `payment.captured` and `payment.failed`.
- Student and admin payment history screens.
- Paid enrollment remains pending until a captured payment is verified. Client-side checkout callbacks alone never activate access.

## Configuration

Set these secrets in the deployment environment; never commit them:

```dotenv
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
RAZORPAY_BASE_URL=https://api.razorpay.com/v1
```

Configure the Razorpay webhook URL as `https://YOUR-DOMAIN/api/v1/payments/razorpay/webhook` and subscribe to `payment.captured` and `payment.failed`. Keep the webhook secret distinct from the API key secret.

## Routes

- `POST /student/test-series/{testSeries}/checkout` — create a provider order.
- `GET /student/payments/{payment}/checkout` — render checkout for its owning student.
- `POST /student/payments/{payment}/verify` — verify checkout signature and fetch payment state from Razorpay.
- `GET /student/payments` — student payment history.
- `GET /admin/payments` — admin payment history (existing `tests.manage` permission).
- `POST /api/v1/payments/razorpay/webhook` — signed provider webhook.

## Operational notes

- Use Razorpay test keys and a test-mode webhook before production.
- Configure the exact public webhook URL in the Razorpay dashboard.
- Do not expose `RAZORPAY_KEY_SECRET` or `RAZORPAY_WEBHOOK_SECRET` to frontend code.
- The key ID is public and is passed to Checkout.
- Refund initiation, refund webhooks, settlement reconciliation, GST invoices, and automated accounting are outside this phase.
- Provider credentials and live payment acceptance have not been configured by this code change.
