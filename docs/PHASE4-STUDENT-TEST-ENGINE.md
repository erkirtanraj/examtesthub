# Phase 4 — Student Test Engine

## Delivered scope
- Student exam catalogue filtered by publication state and availability window.
- Server-created attempts with persisted question order, start time and expiry.
- Resume an existing in-progress attempt rather than creating a duplicate.
- Server-side answer persistence and mark-for-review state.
- Ownership checks on every attempt route.
- Server-enforced expiry, maximum attempt count and submission.
- Server-side objective grading, negative marking and result explanations.
- English/Hindi student-facing catalogue, attempt and result screens.

## Integrity controls
- Correct answers and explanations are withheld during the active attempt.
- The server validates attempt ownership, status, expiry and question membership for every answer write.
- Scores are calculated from stored answer keys and exam pivot marks; clients cannot submit scores.
- Submitted attempts are graded once and cannot be edited.
- Question order is stored when the attempt is created.

## Limitations to address in hardening
- Short answers currently use normalized exact matching, not semantic or manual grading.
- Option order is not persisted between page refreshes even when exam option shuffling is enabled.
- A browser timer triggers auto-submit, while server-side expiry checks protect answer writes and attempt display. For reliable background submission when a browser is closed, add a scheduled expiry worker in a later operations phase.
- This phase does not claim production load readiness; concurrency and load testing remain separate work.

## Local verification
After syncing the branch, run migrations and the feature test suite using your local environment.
