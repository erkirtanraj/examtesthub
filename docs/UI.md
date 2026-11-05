# UI / UX Design Document

**Direction:** Modern, trustworthy, accessible EdTech. **Locales:** Hindi and English. **Clients:** Responsive web; Android-ready API.

## Principles
Separate learning, exams and materials. Show free/paid, ownership and expiry before opening content. Exam UX prioritizes focus, readability, speed and truthful save feedback. Use stable IDs across locales, responsive mobile-first layouts, semantic states (not color alone), keyboard/touch accessibility and reduced motion.

## Information architecture
**Public:** Home; Courses/details; Test Series/details; Study Materials/details; pricing/offers; help/about/contact; login/register; privacy/terms/refund.

**Student:** Dashboard; My Courses/player/lesson; My Test Series/instructions/exam/results; My Study Materials/preview; Orders/Payments; Notifications; Profile/Language/Security.

**Admin:** Overview; Students/Roles; Courses; Test Series/Tests; Question Bank/Imports; Materials/Files; Products/Prices/Coupons; Orders/Payments/Refunds; Reports; Settings/Translations/Audit.

## Screen inventory
| Screen | Key content | States |
|---|---|---|
| Home | Hero, categories, featured items | Loading, empty, error |
| Catalogue | Search, filters, locale, price, category | Empty, loading, filtered |
| Product detail | Description, syllabus, language, price, expiry, preview, CTA | Free, paid, owned, unavailable |
| Login/register | Email/mobile, password, verification, locale | Invalid, throttled, success |
| Student dashboard | Continue, upcoming tests, results, purchases | First visit, empty, loading |
| Course overview | Sections, chapters, progress, access | Locked, enrolled, expired |
| Lesson player | Video/content, resources, progress | Loading, playback error, complete |
| Test series | Included tests, schedules, attempts | Not purchased, active, expired |
| Test instructions | Duration, marks, rules, readiness | Ineligible, scheduled, ready |
| Exam workspace | Question, options, palette, timer, save state | Saving, saved, offline, error, deadline |
| Submit dialog | Answered/unanswered/marked counts | Confirm, submitting, already submitted |
| Result | Score, correct/wrong/unanswered, explanation/rank | Pending, released, revised |
| Material | Metadata, preview, download | Locked, preview, downloadable, expired |
| Checkout | Items, coupon, tax, payment CTA | Pending, success, failed |
| Orders | Items, timeline, invoice/refund | Empty, pending, paid, refunded |
| Admin forms | Fields, validation, preview, publish | Draft, validation error, published |
| Question import | Upload, mapping, row errors, job status | Invalid, partial error, complete |
| Payments admin | Gateway IDs, webhook, reconciliation | Pending, verified, failed, disputed |
| Reports | Filtered metrics, charts, export | Empty, partial, delayed |

## Exam workspace
Desktop: header with title, server-derived timer estimate and connection/save status; main question; side palette; footer with previous/next, mark for review and submit. Mobile: compact header, readable question, accessible palette drawer and reachable navigation.

Palette states: not visited; visited/unanswered; answered; marked for review; answered + marked. Use icons/shapes/text as well as color and announce changes to assistive tech.

Save states: Saving; Saved at time; Offline—changes not confirmed by server; Save failed—retrying/retry. Never show Saved before server acknowledgment. Local buffered input is not server-durable. Timer uses server ends_at and server time; client is display only. Server enforces deadline.

## Design system and accessibility
Semantic tokens: primary, primary-subtle, success, warning, danger, neutral, focus. Suggested navy/indigo with neutral surfaces and restrained green; validate contrast. Use accessible sans-serif with Devanagari-compatible fallback (e.g. Noto Sans Devanagari). Components: buttons, inputs, cards, badges, server-side tables, dialogs/drawers, alerts, toasts, skeletons, empty states, language selector, breadcrumbs, tabs, exam palette and save indicator.

UI strings use translation keys; Hindi reviewed by fluent editor. Allow longer strings to wrap; locale-aware dates/numbers; stable question/option identity; support math, passages and images. Ensure keyboard order, visible focus, labels/errors, contrast, non-color status, screen-reader announcements, touch targets, zoom/reflow and reduced motion.

## Common states and acceptance
Every async view defines loading, empty, validation error, network/server error with safe retry, permission denied, expired/unavailable and success. Checkout/exam retries reuse idempotency key and display existing state, not duplicates. Verify responsive registration, product states, lesson progress, exam save/timer behavior, payment/PDF states, permission-aware admin, Hindi wrapping and accessibility.
