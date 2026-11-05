# Phase 8 — PDF & Study Materials Management

## Scope

Phase 8 adds a standalone study-material library alongside the existing lesson-level resource attachments.

- Admins and content managers can upload, replace, publish, archive and delete PDF materials.
- Material records support English/Hindi titles and descriptions, subject assignment, language metadata and optional course association.
- The student library supports title/description search, subject filtering and language filtering.
- Files are stored on Laravel's private local disk. The application does not return public storage URLs or expose file paths in the student listing.
- A material without a course association is available to authenticated students once published.
- A material associated with a course is downloadable only by a student with an active or completed enrollment in that course. The course must also be currently available.

## Routes

### Admin (authenticated, active account, admin role, materials.manage)

- GET /admin/materials — list, filter and manage materials.
- POST /admin/materials — upload a PDF.
- POST /admin/materials/{studyMaterial} — update metadata and optionally replace the PDF.
- PATCH /admin/materials/{studyMaterial}/status — publish, archive or return to draft.
- DELETE /admin/materials/{studyMaterial} — delete the record and stored file.

The existing materials.manage permission is used. It is already defined for super-admin, admin and content-manager roles by the platform seeder.

### Student (authenticated, active account, student role, courses.view)

- GET /student/materials — browse published study materials.
- GET /student/materials/{studyMaterial}/download — authorize and stream the private PDF.

## File and access controls

- Upload validation accepts PDF files only and limits each file to 50 MB.
- Files are saved below study-materials/ on the private local filesystem disk.
- Student listings expose file name/size metadata but not the storage path.
- Download requests re-check publication state, course availability and enrollment server-side.
- Course-linked materials use existing course enrollments and Razorpay activation. This phase does not add a separate material checkout or payment flow.
- Replacing a PDF deletes the previous private file after the record is updated. Deleting a material removes its stored file.
- Audit events are recorded for create, update, status change and delete actions.

## Database

Migration: 2026_11_05_000011_create_study_materials_table.php.

Before applying it in a local or production environment:

1. Back up the database.
2. Review the migration status and SQL preview.
3. Apply the migration using the normal deployment procedure.

Do not use migrate:fresh or migrate:refresh on a database with real data.

## Verification

The feature test suite covers:

- Authorized admin PDF upload and private file persistence.
- Admin permission enforcement.
- Free published PDF download.
- Course-linked material denial before enrollment.
- Download after active enrollment.
- Draft material invisibility and download denial.
- Expired course access denial.

Also verify manually in both English and Hindi, including mobile widths, a PDF near the 50 MB limit, replacing/deleting a file, and a paid-course student before and after successful Razorpay enrollment.

## Not included in this phase

This is a file library and protected download workflow, not an in-browser PDF annotation/editor, OCR/search-inside-PDF engine, watermarking service, or independent paid-material storefront. Those features need separate requirements and validation.
