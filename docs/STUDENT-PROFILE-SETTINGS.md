# Student Profile and Admin Settings

## Student portal
Students can open **My Profile** from the portal header or sidebar. The page supports updating the student's own name, email address and preferred language, plus changing the account password after verifying the current password.

The profile route is scoped to the authenticated student account. No student ID is accepted from the browser, so students cannot submit profile changes for another account. Passwords must be at least 12 characters and contain uppercase and lowercase letters, a number and a symbol. Password changes revoke the account's personal access tokens and are audited.

## Admin controls
Users with the `settings.manage` permission can open **Administration → Settings → Student Profile Settings**. These global controls independently govern:
- Whether students can save profile details.
- Whether students can edit their name.
- Whether students can edit their email.
- Whether students can change their preferred language.
- Whether students can change their password.

Settings changes are written to the audit log. Profile controls are enforced by the backend as well as reflected in the Vue form, so hiding or enabling a field in the browser is not the security boundary.

## Deployment
Run the new Laravel migration with `php artisan migrate`. The settings row is created on first access with all options enabled. No existing user rows are modified.
