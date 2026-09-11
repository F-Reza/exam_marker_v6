# Exam Marker — Full System V5

This Laravel 12 package implements the requested Exam Marker flow for public visitors, students, individual teachers/tutors, Mode 3 coaching classes and the platform administrator.

## Main functional areas
1. Public homepage, pricing, registration and login.
2. Role-aware authentication and dashboards.
3. Free / Mode 1 / Mode 2 / Mode 3 database permissions.
4. Full sidebar views.
5. QP + optional MS on one wizard page; WA on the final wizard step.
6. Queue/synchronous paper processing.
7. OCR adapter contract and demo reader.
8. Question-wise AI-suggested marks and confidence.
9. Teacher edit, selected-question recheck and final approval.
10. Plan-aware reports/downloads.
11. Mode 2 vs Mode 3 restrictions.
12. Student records and CSV bulk import.
13. Bulk WA upload for Mode 2/3.
14. Analytics.
15. Mode 3 parent details and communications.
16. Email and demo mobile secure-link delivery.
17. Expiring parent report links.
18. Mode 3 real teacher login accounts and paper allocation.
19. Billing/usage limits.
20. Settings, notifications and support tickets.
21. Platform admin plans/users.
22. Database-controlled feature flags and limits.
23. Usage and audit tables.
24. Processing statuses and timestamps.
25. Tenant/organisation access checks and private file delivery.
26. Laravel background job architecture.
27. Demo paper marker with production adapter points.
28. Role-specific end-to-end flows.
29. Functional acceptance documentation.
30. Deployment checklist.

## Important product rules built into V5
- QP and WA are compulsory; MS is optional.
- Exam document formats are PDF, DOC and DOCX only.
- Free Mode has one lifetime check.
- Mode 1 has no bulk WA upload.
- Mode 2 has detailed analysis, editing, selected-question recheck, bulk WA/student import and result-report download.
- Mode 2 has NO parent details, NO parent communication, NO multiple teachers and NO corrected-paper download.
- Mode 3 adds parent communication, corrected-paper package, multiple teachers and teacher-wise allocation.
- No Mode 3 Plus and no multiple-branch feature.

## Installation
### Linux/macOS
```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

### Windows
Use the supplied `install-windows.bat`, or run the equivalent commands in PowerShell/Command Prompt.

## Queue mode
Local demo works with `EXAM_MARKER_ASYNC=false`. To test actual Laravel queue processing:
```env
EXAM_MARKER_ASYNC=true
QUEUE_CONNECTION=database
```
Then run:
```bash
php artisan queue:work
```

## Demo accounts
All seeded passwords: `password`

| Account | Email | Purpose |
|---|---|---|
| Platform Admin | admin@example.com | Admin plans and users |
| Free Teacher | free.teacher@example.com | Free Mode |
| Mode 1 User | mode1@example.com | Mode 1 |
| Mode 2 Teacher | mode2@example.com | Mode 2 workflow |
| Mode 3 Coaching Admin | mode3@example.com | Full coaching workflow |
| Mode 3 Teacher | mode3.teacher@example.com | Assigned teacher workspace |
| Student | student@example.com | Student portal / linked results |

## Production integrations
The included demo marker and OCR reader make the workflow testable without keys. For a real production deployment, implement/configure:
- handwriting/document OCR provider;
- production AI marking service;
- payment gateway;
- production SMS/WhatsApp provider;
- production email credentials.

See `docs/SYSTEM_FLOW_AND_MODULES.md` for the 30-section system specification and `docs/FEATURE_MATRIX.md` for plan permissions.


## V6 additions (26 Aug 2026)
- Separate optional Insert/Source Booklet uploads for English Checkpoint and other source-based papers.
- Subquestion parsing and cumulative parent-question scoring.
- Mode 2 bulk WA rows now capture/select student names.
- Mode 3 parent PDF attachment + secure report link + public PDF download.
- Adaptive local PDF/DOC/DOCX reader/marker replaces identical static demo scores.
- Favicon.
- Checkout and payment transaction flow.
- Platform Admin payment-gateway settings for PayPal, PayU, PhonePe and Paytm.

See `docs/V6_FIXES.md` and `docs/PAYMENTS.md`. Production-grade handwriting understanding still requires a real OCR/vision/AI provider. Paytm live payments require its current official checksum utility/SDK.
