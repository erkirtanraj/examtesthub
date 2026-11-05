# Admin Navigation and Responsive UI

Status: responsive navigation shell and LMS Course Management are implemented on `main`. Phase 7 was merged on 2026-11-05. The responsive QA matrix below remains a manual release check.

## Admin sidebar

The navigation is grouped by existing route areas and filtered using the permissions already shared with the authenticated user. Backend route middleware remains the source of authorization; hiding a link is only a usability measure.

| Group | Menu item | Route | Permission |
|---|---|---|---|
| Dashboard | Overview | `/admin/dashboard` | Admin portal role |
| User Management | All Users | `/admin/users` | `users.view` |
| User Management | Roles | `/admin/roles` | `roles.manage` |
| User Management | Permissions | `/admin/permissions` | `permissions.manage` |
| Academic Management | Subjects & Topics | `/admin/subjects` | `questions.manage` |
| Academic Management | Question Bank | `/admin/questions` | `questions.manage` |
| Exam Management | All Exams | `/admin/exams` | `tests.manage` |
| Exam Management | Test Series | `/admin/test-series` | `tests.manage` |
| Exam Management | Payments | `/admin/payments` | `tests.manage` |
| LMS Course Management | All Courses | `/admin/courses` | `courses.view` |
| Administration & Security | Audit Activity | `/admin/activity` | `audit.view` |
| Administration & Security | My Profile | `/admin/profile` | `profile.manage` |

Student navigation is kept separate and includes dashboard, test series, my test series, available exams and payment history.

## Planned admin modules (not live links yet)

These modules should appear in the navigation only after their routes, authorization, controllers, screens and tests are delivered:

- PDF & Study Materials: material catalogue, uploads, categories, private file access and download history.
- Reports & Analytics: registration, enrollment, revenue, exam participation, student performance and exports.
- Notifications: announcements, email templates, delivery history.
- Website Content: pages, blog, banners, FAQs and SEO.
- Extended System Settings: localization, payment provider configuration status, storage, queues, health and maintenance.
- Extended Security: session review and security events.

Do not duplicate existing question, exam, test-series, user, payment or audit workflows. Course enrollments remain distinct from test-series enrollments. Course payments reuse the existing verified Razorpay infrastructure.

## Responsive behavior

- **Mobile (< 1024px):** header menu button opens a slide-in navigation drawer; backdrop closes it; selecting a route closes the drawer. Navigation is vertically scrollable.
- **Laptop/desktop (>= 1024px):** persistent 260px navigation column beside a min-width-zero content column.
- Header controls remain usable at 320px viewport width; the account name is hidden on narrow screens to preserve space.
- Main content uses fluid padding and typography; long text and code wrap safely.
- Tables are placed in horizontal overflow containers so columns remain readable without forcing the whole page wider than the viewport.
- Forms and cards should use single-column layouts on narrow screens and progressively expand at larger breakpoints.
- Controls use touch-friendly minimum heights and inputs use 16px text on mobile to avoid unwanted browser zoom.
- Hindi strings must wrap naturally; no fixed-height labels or cards that clip Devanagari text.

## Responsive QA matrix

Test at minimum:

| Device class | Viewport | Required checks |
|---|---:|---|
| Small mobile | 320 x 640 | No page-level horizontal scroll; menu opens/closes; forms fit |
| Typical mobile | 390 x 844 | Drawer, cards, tables, buttons and Hindi text |
| Large mobile | 430 x 932 | Navigation and content remain usable |
| Tablet | 768 x 1024 | Layout remains readable; table scrolling stays local |
| Laptop | 1366 x 768 | Persistent sidebar; no clipped content |
| Desktop | 1920 x 1080 | Content width remains controlled and readable |

For every admin page verify: loading/empty/error states, form validation, action buttons, permission-based navigation, keyboard focus, 200% zoom/reflow, and both English and Hindi. Confirm no horizontal overflow outside table containers. Run PHP tests and the Vite production build in CI before merging.

## Implementation notes

The shared layout is `resources/js/Layouts/PortalLayout.vue`; global responsive safeguards live in `resources/css/app.css`; the admin landing page is `resources/js/Pages/Admin/Dashboard.vue`. Existing admin table pages already use `overflow-x-auto` wrappers; keep that convention for future table screens.
