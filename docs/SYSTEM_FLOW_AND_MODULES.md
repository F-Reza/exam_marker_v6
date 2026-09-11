# Exam Marker V5 — System Flow and Module Documentation

This document is the functional map for the Laravel V5 package. It follows the requested 30-module flow and is intended for the developer, tester and product owner.

## Public flow
Public visitors can view the homepage, How It Works, pricing, registration and login. Uploads are unavailable until authentication. Registration creates a Free Mode account. Login checks account status and routes the user to the correct dashboard/portal.

## 1. Authentication, authorisation and role-based dashboard routing
- Roles: platform admin, coaching-class admin, teacher and student.
- Plans: Free, Mode 1, Mode 2 and Mode 3.
- `DashboardController` redirects students to the student portal and teachers to the teacher workspace.
- Coaching users use the full dashboard.
- Admin-only routes use `RequireAdmin`; role routes use `RequireRole`; plan features use `RequirePlanFeature`.
- Organisation ownership is centralised through `User::organisationOwnerId()`.

## 2. Free Mode, Mode 1, Mode 2 and Mode 3 permissions
- Free: one lifetime paper check, question-wise marks, total marks and basic feedback.
- Mode 1: multiple plan-limited checks, paper history and limited student records; no bulk upload, analytics or downloads.
- Mode 2: percentage, grade, graphs, topic analysis, strengths/weaknesses, manual AI mark/feedback editing, selected-question recheck, final approval, result-report download, student records, bulk WA and student import. No corrected-paper download, parent details, parent communication or multiple teachers.
- Mode 3: all Mode 2 features plus corrected-paper package, parent fields, parent email/mobile links, secure public links, communication log, multiple teacher logins and teacher-wise paper allocation.

## 3. Complete sidebar module explanation
Dashboard/Workspace; Check New Paper; Bulk Check Papers when available; My Papers; Students when available; Reports; Analytics when available; Parent Messages in Mode 3; Teachers & Team for Mode 3 coaching admins; Settings; Admin Plans and User Management for platform admins; Plan & Billing; Help & Support; Notifications; Logout.

## 4. QP + optional MS + WA upload workflow
The standard upload wizard has three UI stages. Assessment Details is first. QP and optional MS are displayed together on one page. WA is uploaded on the final page. Only PDF, DOC and DOCX are accepted for examination documents. Files are stored privately.

## 5. Queue-based paper processing
`ProcessAssessment` implements `ShouldQueue`. With `EXAM_MARKER_ASYNC=true`, the job is dispatched to the configured Laravel queue. In local/demo mode the system can dispatch synchronously. Status changes: uploaded → queued → processing → review_required or failed.

## 6. AI/OCR adapter workflow
`DocumentReader` is an adapter contract. `DemoDocumentReader` provides a local no-credential implementation. `ProductionDocumentReader` is the explicit extension point for Azure/Google/AWS/custom OCR. `DocumentAwarePaperMarker` combines document reading with the marking engine.

## 7. Question-wise AI marking and confidence
Each `QuestionResult` stores question number, topic, maximum marks, AI marks, teacher marks, confidence, AI feedback, teacher comment, criteria, review status and recheck metadata. The included demo engine creates deterministic question-wise results for local testing.

## 8. Teacher editing, recheck and final approval
Mode 2/3 users can edit final marks and teacher comments. A selected question can be rechecked without overwriting an existing teacher mark. Finalisation recalculates percentage and grade, creates/refreshes the report version and generates a secure parent token where applicable.

## 9. Reports and download permissions
Mode 2/3 can preview and download the result/analysis report. Mode 2 cannot download the corrected answer paper. Mode 3 can download a corrected-paper ZIP containing the original WA plus the correction summary. The package is generated dynamically.

## 10. Mode 2 vs Mode 3 restrictions
Mode 2 is a single-teacher/tutor plan and cannot expose parent contact fields, parent communications, multiple teacher creation, teacher allocation or corrected-paper download. Mode 3 enables these functions.

## 11. Student management and bulk import
Student records are plan-controlled. Mode 2/3 users can upload a CSV student list. The CSV importer enforces the plan student limit. Parent columns are ignored unless the plan has `parent_details`. A downloadable CSV template is included.

## 12. Bulk WA upload
Mode 2/3 users can use one shared QP and optional MS with multiple WAs. Each WA is mapped to one student and generates a separate assessment. The plan paper-check limit is checked before the batch is created. The batch can optionally be queued immediately.

## 13. Analytics
Analytics are available when `graphs` is enabled. The controller calculates recent assessment performance and topic-level percentages from final teacher marks when present, otherwise AI marks.

## 14. Mode 3 parent management
Parent name, email, mobile and alternate contact are available only when `parent_details` is enabled. These values live on the student record and are hidden from Mode 2 forms.

## 15. Email/SMS/mobile report workflow
Mode 3 email sends through Laravel Mail. The mobile-link function is fully testable in demo mode and records the secure link in the communication log. A production SMS/WhatsApp provider can replace the demo delivery path.

## 16. Secure parent links
Finalised/reported assessments can have a random public token and expiry. The public report route checks the token, status and expiry before showing the parent view.

## 17. Mode 3 multiple-teacher system and paper allocation
Mode 3 coaching admins create real teacher login accounts under the coaching owner. Teacher limits are enforced. Teacher allocation is available on assessment creation. Assigned teachers can open, review and mark only authorised papers.

## 18. Billing and usage limits
Plans contain JSON feature flags and numeric limits. The demo billing page can change plans without a payment provider so the full application can be tested. Production must replace the demo plan switch with gateway-confirmed subscription events.

## 19. Settings, notifications and support tickets
Users can update profile data, password and notification preferences. Notification and support-ticket modules are included. Support history is visible to the user.

## 20. Platform Admin modules
Platform admins can view users, create/update accounts, assign plans and edit plan price/features/limits. Admin pages are separated by middleware.

## 21. Database-controlled plan permissions
Feature flags are not hard-coded into Blade pages alone. They are stored in `plans.features` and read by `User::hasFeature()`. Limits are stored in `plans.limits` and read by `User::limit()`.

## 22. Recommended database tables
Core tables included: users, plans, students, assessments, question_results, parent_communications, support_tickets, team legacy data, notifications, usage_records and audit_logs plus Laravel cache/jobs/session infrastructure as configured.

## 23. Processing status flow
`draft` (reserved) → `uploaded` → `queued` → `processing` → `review_required` → `finalised` → `reported`. Failures use `failed`. Processing timestamps and error text are stored in V5.

## 24. Security and tenant separation
Organisation access is based on the owner account ID. Controllers call `canAccessAssessment()` or compare the organisation owner. Private files are streamed only after authorisation. Parent reports use expiring random tokens. Account suspension is enforced at login.

## 25. Laravel queues and background jobs
The job table is included. For local synchronous testing set `EXAM_MARKER_ASYNC=false`. For production set it to true and run `php artisan queue:work` under a process supervisor.

## 26. Demo marking engine vs production AI/OCR
Demo mode makes the entire flow testable without external keys. It does not claim to read real handwriting. Production requires implementation of the OCR adapter and a production marking adapter/model with appropriate credentials and validation.

## 27. Step-by-step workflow for every user type
### Student
Register/login → student portal → view linked results. A standalone student may use the free paper-check flow if configured as an individual user, but cannot edit marks.
### Individual teacher / Mode 1
Login → add limited students if allowed → check paper → view marks/history.
### Mode 2 teacher
Login → students/import → single or bulk paper upload → processing → analytics → edit/recheck → finalise → preview/download result report.
### Mode 3 coaching admin
Login → manage students/parents → create teacher accounts → upload/bulk upload → assign teacher → review/finalise → corrected-paper package → email/mobile parent report → communication log.
### Mode 3 assigned teacher
Login → teacher workspace → assigned papers → review/edit/recheck/finalise as permitted by the plan and organisation.
### Platform admin
Login → user management → plan management → assign plans → inspect system-wide administration pages.

## 28. Functional acceptance checklist
- Guest cannot upload papers.
- Free plan permits one lifetime check.
- Exam uploads reject file types other than PDF/DOC/DOCX.
- QP and WA are required; MS is optional.
- Mode 1 has no bulk WA.
- Mode 2 has bulk WA and student import.
- Mode 2 has no parent contact/communication, no multiple teachers and no corrected-paper download.
- Mode 3 has those features.
- Assigned teachers cannot open another organisation's data.
- Low-confidence AI results can be reviewed.
- Selected-question recheck works without discarding teacher marks.
- Parent links expire.
- Plan features and limits are editable in the admin panel.

## 29. Recommended Laravel code architecture
- Controllers: HTTP orchestration and validation.
- Middleware: role/plan/admin gates.
- Models: plan permissions, organisation ownership and relationships.
- Services/contracts: OCR, paper marking and audit concerns.
- Jobs: long-running paper processing.
- Mail: parent result delivery.
- Blade: public pages, dashboards, forms, results, reports and portals.
- Database: plan-driven SaaS state and processing records.

## 30. Deployment and production checklist
1. Install PHP 8.2+, Composer, Node.js and database server.
2. Copy `.env.example` to `.env` and set APP_URL/database credentials.
3. Run `composer install` and `npm install`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate --seed`.
6. Run `npm run build`.
7. Configure web server document root to `/public`.
8. Use MySQL/PostgreSQL for production rather than SQLite if required by load.
9. Configure private object storage if desired.
10. Configure mail provider.
11. Implement/configure production OCR and AI adapters.
12. Configure payment gateway before enabling live plan purchases.
13. Configure SMS/WhatsApp provider before disabling demo SMS.
14. Set `EXAM_MARKER_ASYNC=true`, configure queue driver and run queue workers.
15. Schedule `php artisan schedule:run` if scheduled jobs are added.
16. Enable HTTPS, backups, monitoring and log rotation.
17. Test tenant separation, upload validation and parent-link expiry before launch.

## Demo credentials after seeding
All demo accounts use password `password`.
- Platform Admin: `admin@example.com`
- Free Teacher: `free.teacher@example.com`
- Mode 1: `mode1@example.com`
- Mode 2 Teacher: `mode2@example.com`
- Mode 3 Coaching Admin: `mode3@example.com`
- Mode 3 Assigned Teacher: `mode3.teacher@example.com`
- Student: `student@example.com`
