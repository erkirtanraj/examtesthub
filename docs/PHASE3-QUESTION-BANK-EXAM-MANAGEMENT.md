# Phase 3 — Question Bank and Exam Management

## Question bank
- Subject and topic taxonomy with active/inactive subjects.
- Reusable single-choice, multiple-choice, true/false and short-answer questions.
- Hindi/English language metadata, difficulty, explanation, marks and negative marks.
- Draft, published and archived question states.
- Search, subject, difficulty, status and language filters with pagination.
- CSV import/export. Imports create drafts for review; answer keys are stored as JSON.
- Questions linked to published or scheduled exams cannot be edited. Questions linked to any exam cannot be deleted.

## Exam management
- Create and edit exams with descriptions and candidate instructions.
- Configure duration, maximum attempts, start/end timestamps and question/option shuffling.
- Select up to 500 published questions and preserve their selected order and marks.
- Draft, scheduled, published and archived states.
- Scheduling requires start and end timestamps; the end must be later than the start.
- Publishing/scheduling requires at least one question and all linked questions must remain published.
- Archived exams are read-only.

## Authorization and audit
- Question bank and subject/topic routes require questions.manage.
- Exam routes require tests.manage.
- Routes also require authentication, an active account and an allowed administrative role.
- Existing role seeding grants these permissions to Super Admin, Admin and Exam Manager.
- Create, update, delete, import and status changes are recorded in audit_logs.

## Question CSV format
Exact header:
subject,topic,type,prompt,options_json,correct_answer_json,explanation,difficulty,language,marks,negative_marks

Example row:
Mathematics,Algebra,single_choice,"Solve x + 2 = 5","{""A"":""1"",""B"":""2"",""C"":""3"",""D"":""4""}","[""C""]",,easy,en,1,0.25

- Subject and optional topic must already exist by exact name.
- Supported types: single_choice, multiple_choice, true_false, short_answer.
- Supported difficulty: easy, medium, hard.
- Supported language: en, hi.
- Imported questions are drafts and must be reviewed/published before use in an exam.
- Upload limit: 10 MB.

## Local verification
After syncing the branch and backing up your database:

    cd F:\AI_Tools\Examtesthub
    git fetch origin
    git switch feature/phase-3-question-bank-exam-management
    git pull origin feature/phase-3-question-bank-exam-management
    composer install
    npm.cmd ci
    php artisan migrate
    php artisan db:seed
    php artisan test
    npm.cmd run build

Do not run migrate:fresh against a database containing real data. These migrations add new tables; they do not intentionally drop existing application tables.
