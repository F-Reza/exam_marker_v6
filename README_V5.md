# Exam Marker Full System V5

Laravel 12 demo-functional SaaS implementation for the complete Exam Marker flow.

## Quick start (SQLite)
```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```
Open `http://127.0.0.1:8000`.

For asynchronous queue testing, set `EXAM_MARKER_ASYNC=true`, set an appropriate `QUEUE_CONNECTION`, and run:
```bash
php artisan queue:work
```

## What works locally without external credentials
Authentication, role routing, plans/permissions, private uploads, standard and bulk paper creation, demo OCR/marking, queue/synchronous processing, question results, teacher edits, selected recheck, finalisation, analytics, result report preview/download, Mode 3 corrected-paper ZIP, students/import, teacher accounts/allocation, secure parent links, demo mobile-link log, admin plan/user management, settings, notifications and support.

## External services required for production
Real handwriting OCR, real AI marking, payment gateway, production SMS/WhatsApp and production email provider credentials.

See `docs/SYSTEM_FLOW_AND_MODULES.md` for the complete 30-part system documentation.
