# SEO and security baseline

This baseline is intentionally limited to the repository's public entry point. It does not replace production-host configuration or a runtime security assessment.

## Public crawl paths

- `public/robots.txt` allows public URLs and disallows application-only admin, student and API paths from compliant crawlers.
- `public/sitemap.xml` includes only the public homepage. Add other URLs only when they are public, canonical and intended for indexing.
- Never rely on robots.txt as access control. Private routes must remain protected by Laravel authentication and authorization.

## Public homepage metadata

The Inertia root view supplies default title, description, canonical and social metadata. The Welcome page can override title and description via Inertia's `Head` component.

## Security headers

The application code does not currently define a complete host-independent CSP/HSTS policy. Configure transport security and response headers at the trusted deployment edge after confirming HTTPS, asset hosts, payment-provider integrations and any embedded media. Start CSP in report-only mode and review reports before enforcement.

## Repository validation

Run `composer validate --no-check-publish`, `php artisan test`, `npm ci` and `npm run build` in CI. This repository PR is not a substitute for testing the deployed environment.
