# Phase 2 — Admin User Management

## Implemented scope

- Permission-protected user directory with name/email search, role/status filters and 10/25/50/100 pagination.
- User creation, detail view, profile/role editing and reversible activation/deactivation.
- System Super Admin protection: cannot deactivate the Super Admin or remove its protected role; users cannot deactivate themselves.
- Role registry with permission assignment. The system Super Admin role is protected from editing.
- Granular permission registry; role and permission administration is restricted by dedicated permissions.
- Admin profile and preferred-language update, plus current-password-verified password changes.
- Password changes revoke the user's Sanctum personal access tokens.
- CSV export streams users in 500-row chunks. CSV import accepts a 5 MB CSV/TXT file with the exact header:
  `name,email,password,role,preferred_locale`
  Allowed imported roles are student, content-manager and exam-manager. Super Admin/admin assignment is never accepted by bulk import. Rows failing validation are rejected and summarized.
- Audit events record actor, affected user where applicable, action, metadata, IP address and user agent.
- Inactive accounts cannot authenticate to the web portals; inactive authenticated accounts are blocked from admin/student routes and Sanctum-protected API routes. Deactivation revokes existing API tokens.
- Admin navigation reflects the authenticated user's permissions.

## Security notes

- All management endpoints are protected server-side by authentication, active-account and role middleware plus route-specific permissions.
- Only a Super Admin may assign the privileged admin or super-admin roles, create/edit roles, and create permissions.
- CSV import is throttled and validates every row. Export is permission-gated.
- Passwords are hashed by Laravel's hashed cast; never include passwords in exports or audit metadata.
- User deletion is intentionally not exposed; deactivation preserves referential integrity and audit history.

## Local verification (run after syncing this branch)

```powershell
cd F:\AI_Tools\Examtesthub
git pull origin feature/phase-1-auth-dashboards
composer install
npm.cmd ci
php artisan migrate
php artisan db:seed
php artisan test
npm.cmd run build
```

Do not run `migrate:fresh` against a database containing user data. Configure the Super Admin password locally in `.env`; never commit that file.

## CSV import example

```csv
name,email,password,role,preferred_locale
Demo Student,demo.student@example.test,StrongPassword!2026,student,en
```

The CSV contains temporary credentials; transfer it securely and delete it after import.
