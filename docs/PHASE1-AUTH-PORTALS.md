# Phase 1 — Authentication, RBAC and Portals

## Portal structure
- Public website: /
- Student portal: /student/login, /student/dashboard, /register
- Admin portal: /admin/login, /admin/dashboard
- API token endpoints: POST /api/v1/auth/token, DELETE /api/v1/auth/token

These are separate portal experiences within one Laravel application and one identity/database layer. Admin and student login endpoints enforce different role allowlists.

## Roles
- super-admin: bypasses permission checks; receives every seeded permission.
- admin: operational platform management.
- content-manager: courses, lessons and materials.
- exam-manager: questions, tests and results.
- student: own learning, test-taking and profile capabilities.

Role and permission records are stored in MySQL. Route middleware aliases are role and permission.

## Provision the initial Super Admin
Set these values in the local .env file. Never commit the password:

    SUPER_ADMIN_NAME="ExamTestHub Super Admin"
    SUPER_ADMIN_EMAIL=admin@examtesthub.com
    SUPER_ADMIN_PASSWORD="use-a-unique-random-password-at-least-16-characters"

Then run:

    php artisan config:clear
    php artisan migrate
    php artisan db:seed

The seeder creates the role/permission catalogue and provisions the configured Super Admin only if the email does not already exist. Re-running it does not reset an existing administrator's password.

## Security notes
- Session login regenerates the session ID.
- Logout invalidates the session and rotates the CSRF token.
- Portal login is role-restricted and rate-limited to five attempts per email/IP per minute.
- Student registration only assigns the student role; public input cannot choose roles.
- Passwords are hashed by the User model cast and validated to at least 12 characters with mixed case, numbers and symbols.
- Sanctum personal access tokens are scoped to student read/write abilities and can be revoked.
- Do not enable production debug mode. Add email verification, password reset delivery, MFA for administrators, audit logging and deployment secrets before production launch.

## Verification
Run:

    php artisan migrate:status
    php artisan db:seed
    php artisan route:list
    php artisan test
    npm.cmd run build

CI and local test/build results must be checked before this phase is called production-ready.
