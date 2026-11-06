# SEO and security baseline

This PR provides crawler directives and deployment guidance. The public sitemap and page-level SEO metadata are implemented with the public homepage/CMS routes in PR #14; keep those runtime features together with their route and content model.

## Public crawl paths

- `public/robots.txt` allows public URLs and disallows application-only admin, student and API paths from compliant crawlers.
- The public application serves `/sitemap.xml` dynamically, listing the homepage, public catalogue landing pages and published CMS page/legal URLs. Do not add a static `public/sitemap.xml`, because it would shadow the Laravel route and become stale as CMS content changes.
- Never rely on robots.txt as access control. Private routes must remain protected by Laravel authentication and authorization.

## Page metadata

The public homepage, catalogue and published CMS pages render page-specific title, description and canonical metadata through Inertia's `Head` component in PR #14. The root Inertia view may provide defaults, but route-level metadata is the source for these public pages.

## Security headers

The application code does not currently define a complete host-independent CSP/HSTS policy. Configure transport security and response headers at the trusted deployment edge after confirming HTTPS, asset hosts, payment-provider integrations and any embedded media. Start CSP in report-only mode and review reports before enforcement.

## Repository validation

Run `composer validate --no-check-publish`, `php artisan test`, `npm ci` and `npm run build` in CI. This repository PR is not a substitute for testing the deployed environment.
