# SEO and security baseline

## Public crawl paths

- `public/robots.txt` permits public content and asks compliant crawlers not to crawl the admin, student and API application paths.
- Laravel serves `/sitemap.xml` dynamically. It includes the public homepage and catalogue landing pages, plus published CMS page/legal URLs only.
- Unpublished CMS content and authenticated application areas are excluded from the sitemap.
- Robots directives are crawler guidance, not access control. Laravel authentication and authorization remain mandatory.

## Page metadata

Public homepage, catalogue and CMS pages render page-specific title, meta description and canonical URL through Inertia's Head component. CMS page descriptions are derived from the published English content with a safe 160-character limit; a title-based fallback is used when content is not plain text.

## Security headers

The application does not define a complete host-independent CSP/HSTS policy. Configure transport security and response headers at the trusted deployment edge after confirming HTTPS, asset hosts, payment-provider integrations and embedded media. Start CSP in report-only mode and review reports before enforcement.

## Repository validation

CI validates Composer dependencies, runs the Laravel test suite and builds the frontend. Run `composer validate --no-check-publish`, `php artisan test`, `npm ci` and `npm run build` locally when changing this baseline.
