# Exam Marker V6 — Code Audit Report

পর্যালোচনার তারিখ: 10 November 2026  
প্যাকেজ: `exam_marker_v6(1).zip`  
রায়: **সব ঠিক নেই। Demo/workflow foundation আছে; production release-এর আগে গুরুত্বপূর্ণ সংশোধন প্রয়োজন।**

## কীভাবে পরীক্ষা করা হয়েছে

ZIP extract করে routes, controllers, models, middleware, services, jobs, migrations, seeders, Blade views, test source এবং V6 documentation পর্যালোচনা করা হয়েছে। সংযুক্ত SQLite database read-only অবস্থায় দেখে plan features ও limits seeders-এর সঙ্গে মেলানো হয়েছে। মূল ZIP বা application source পরিবর্তন করা হয়নি।

**সীমাবদ্ধতা:** এই environment-এ `php` নেই। `php -v` / `php artisan test` চালানোর প্রচেষ্টা `php: command not found`-এ থেমেছে। ফলে migration, PHPUnit, browser flow, real OCR accuracy, mail delivery, real payment এবং worker execution এখানে যাচাই হয়নি। নিচের “কোডে আছে” runtime pass বোঝায় না। পাওয়া সমস্যাগুলো source-based findings; live exploit পরীক্ষা করা হয়নি।

প্যাকেজে 4টি feature-test file ও 33টি test method আছে। V6 acceptance checklist-এর checkboxগুলো এখনও unchecked। README-এর মূল heading এখনও V5, যদিও নিচে V6 additions আছে।

## 1. আপনার গুরুত্বপূর্ণ product rules

| নিয়ম | ফল | পর্যবেক্ষণ |
|---|---|---|
| QP এবং WA compulsory | কোডে ঠিক | Single ও bulk upload validation-এ required। |
| MS optional | Upload-এ ঠিক; AI-তে অসম্পূর্ণ | Nullable validation আছে; external AI prompt শুধু supplied MS ব্যবহার করতে বলে, no-MS policy নেই। |
| PDF/DOC/DOCX only | Upload validation আছে | File types enforce করা হয়েছে; document extraction server dependencies আলাদা। |
| QP + MS একই wizard step | কোডে ঠিক | Step 2-তে দুটো upload field। |
| WA final wizard step | কোডে ঠিক | Step 3-তে WA & Confirm। |
| Free: এক lifetime check | আংশিক | Flag আছে; concurrent requests এবং delete/usage accounting-এর ফাঁক আছে। |
| Mode 1: no bulk WA | কোডে ঠিক | Seed/database flag false; route middleware checks it। |
| Mode 2: detailed analysis/edit/recheck/bulk/report | কোডে আছে | Marking/report correctness-এ নিচের ঘাটতি আছে। |
| Mode 2: no parent details/communications | স্বাভাবিক UI/route-এ ঠিক | Parent input/column ও communication route feature-gated। CSV template-এ parent headings এখনও থাকে। |
| Mode 2: no multiple teachers | কোডে ঠিক | Team management coaching + multiple_teachers feature দরকার। |
| Mode 2: no corrected-paper download | কোডে ঠিক | Feature false; route ও controller checks আছে। |
| Mode 3: parent communication | কোডে আছে | Email service আছে; mobile delivery explicitly demo। |
| Mode 3: corrected-paper package | বর্ণনা অনুযায়ী আছে | ZIP-এ original WA ও HTML correction summary; annotated answer PDF নয়। |
| Mode 3: multiple real teacher logins | কোডে আছে | Teacher User account, owner relation ও password তৈরি হয়; inactive/suspension enforcement অসম্পূর্ণ। |
| Mode 3: teacher-wise allocation | আংশিক | Single upload-এ allocation আছে; existing/bulk paper পরে reassign করার endpoint পাওয়া যায়নি। |
| No Mode 3 Plus / multiple branches | মিলে গেছে | Bundled plans এবং seeders-এ Free/Mode1/Mode2/Mode3; branch feature পাওয়া যায়নি। |

সংযুক্ত database-এর flags seeders-এর সঙ্গে মিলে: Free 1 lifetime paper; Mode1 10 papers/month, 5 students; Mode2 50 papers/month, 25 students; Mode3 200 papers/month, 100 students, 10 teachers। এগুলো configured limits; সব entry point দিয়ে enforcement ঠিক আছে এমন নয়।

## 2. প্রধান finding ও করণীয়

### F01 — Critical: payment ছাড়াই paid plan নেওয়া যায়

Evidence: `routes/web.php` → POST `/billing/change`; `app/Http/Controllers/BillingController.php::change()`।

যেকোনো authenticated organisation-owner account একটি existing `plan_id` পাঠালে সরাসরি তার plan বদলায়। Payment status লাগে না; method নিজেই “Demo subscription changed” বলে। Checkout থাকলেও এই বিকল্প route খোলা আছে।

**Fix:** Production-এ endpoint বন্ধ করুন অথবা শুধু free downgrade-এর মতো অনুমোদিত কাজ রাখুন। Paid entitlement কেবল verified successful transaction থেকে দিন।

### F02 — Critical: payment callback যথেষ্ট authenticated নয়

Evidence: `app/Services/Payments/GatewayManager.php::{payuVerify,phonePeVerify,paytmVerify}` এবং `PaymentController::callback()`।

PayU শুধু `status=success`, PhonePe success-like state, Paytm success-like STATUS বিশ্বাস করে। Signature/checksum বা provider status lookup নেই। Controller callback provider-কে stored transaction gateway-এর সঙ্গে মেলায় না; expected transaction/order, amount এবং currency-ও match করে না। PayPal capture করলেও stored order এবং expected amount/currency binding নেই। ফলে callback spoofing বা অন্য payment-এর সঙ্গে entitlement misbinding-এর পথ আছে।

**Fix:** Provider-authenticated verification, stored order/gateway/reference/amount/currency matching, atomic transaction এবং idempotency দিন। Paid callback পুনরায় এলে entitlement আবার পরিবর্তন করা যাবে না; failed callback দিয়ে paid record downgrade করা যাবে না।

### F03 — High: AI admin settings এবং actual AI call আলাদা configuration ব্যবহার করে

Evidence: `AdminAISettingController::update()`, `AppServiceProvider::register()`, `config/ai.php`, `AIPaperMarker::callAI()`।

Admin provider/model/key database-এ save করে। Binding database provider দেখে external AI class বেছে নেয়; কিন্তু সেই class আবার `config('ai.provider')` ও environment key/hardcoded model ব্যবহার করে। ফলে admin-এ Gemini/key/model দিলেও actual request অন্য configured provider-এ যেতে পারে বা key না পেয়ে fail করতে পারে। Azure binding-এ accepted হলেও `callAI()` switch-এ implementation নেই। Bundled SQLite-এ AI settings row নেই, তাই সেখানে default binding adaptive marker।

**Fix:** একটি resolved settings object দিয়ে provider, endpoint, model ও decrypted key দিন। প্রত্যেক advertised provider-এর mocked contract test যোগ করুন; অসমর্থিত provider UI থেকে সরান।

### F04 — High: default marker আসল question marks পড়ে না

Evidence: `AdaptivePaperMarker::parseQuestions()` এবং `score()`।

Detected question-এর `marks` null রাখা হয়, পরে `totalMarks / count(questions)` দেওয়া হয়। যেমন Q1 [2], Q2 [8], total 10 হলেও এই logic দুটোকে 5 করে দেবে। Question শনাক্ত না হলে নিজে কৃত্রিম question rows বানায়। Score মূলত keyword overlap এবং answer length থেকে আসে; physics synonyms-এর মধ্যে energy/power বা speed/acceleration-এর মতো পৃথক ধারণাও যুক্ত আছে।

**Fix:** QP/MS থেকে exact marks ও stable question structure extract করে teacher confirmation নিন। Parse ব্যর্থ হলে review/error দিন। এই heuristic-কে demo/provisional রাখুন। Demo marker থাকা আপনার scope-এর অংশ; সমস্যা হলো marks-কে reliable exam result ধরে নেওয়া।

### F05 — High: subquestion extraction ও answer matching অসম্পূর্ণ

Evidence: `AdaptivePaperMarker::parseQuestions()` / `splitByQuestions()`; `ProcessAssessment::handle()`।

QP `1(a)` থেকে label `1a` বানায়; answer splitting তখন `1a` খোঁজে, `1(a)` নয়। সাধারণ `(a)` continuation এবং `(i)/(ii)` nesting-ও parser সমর্থন করে না। Default payload আলাদা `question_part` দেয় না। View numeric prefix দিয়ে main totals group করলেও ভুল/missing extraction ঠিক করতে পারে না।

**Fix:** QP/MS/WA-তে একই canonical question ID দিন; printed `1(a)`, `1 a`, `1a`, continuation এবং nested parts-এর fixture পরীক্ষা করুন।

### F06 — High: Insert/source document extraction হয়, marking context-এ ব্যবহার হয় না

Evidence: `LocalDocumentReader::read()` returns `insert_text`; `AdaptivePaperMarker::mark()`; `AIPaperMarker::buildPrompt()`।

Adaptive marker শুধু QP/WA/MS score করে; `insert_used=true` summary দিতে পারে যদিও insert scoring-এ যায় না। External AI prompt-এও insert text নেই। Source-based English/Humanities প্রশ্নের জন্য এটি গুরুত্বপূর্ণ।

**Fix:** Actual marking prompt/reference-এ inserts যুক্ত করুন এবং “used” flag বাস্তব ব্যবহারের ভিত্তিতে দিন।

### F07 — High: bulk WA upload দিয়ে student limit পার হওয়া যায়

Evidence: `BulkAssessmentController::store()` বনাম `StudentController::checkLimit()`।

Bulk flow-তে নতুন নাম দিলে `Student::firstOrCreate()` চলে, কিন্তু student_limit check হয় না। Mode2-তে 25 students থাকলেও paper quota অবশিষ্ট থাকলে আরও student বানানোর পথ আছে।

**Fix:** New unique students আগে হিসাব করে একই tenant quota enforce করুন; all-or-nothing transaction ব্যবহার করুন।

### F08 — High: paper quota accounting delete ও concurrency-safe নয়

Evidence: `AssessmentController::{enforceCheckLimit,store,start,destroy}`, `BulkAssessmentController::store()`।

Paid quota existing assessment count করে; paper delete করলে count কমে, অথচ check ইতিমধ্যে ব্যবহৃত। Upload limit check এবং create আলাদা; concurrent requests quota পার করতে পারে। Free check flag start-এর পরে set হয়; sync processing exception হলে flag update হয় না। Failed record delete করে আবার upload সম্ভব—failed attempt charge হবে কি না policy স্পষ্ট করতে হবে। Concurrent free uploads/starts-ও atomic reservation নেই।

**Fix:** Durable usage/reservation ledger, owner-level lock/atomic counter এবং স্পষ্ট success/failure charging policy দিন। Completed usage delete-এ ফেরত দেবেন না।

### F09 — High: inactive account login ও active-session suspension বন্ধ হয় না

Evidence: `AuthController::login()`, `TeamController::update()`, `bootstrap/app.php`।

Team UI `inactive` status save করতে পারে; login শুধু `suspended` reject করে। Authenticated routes-এ প্রতি request account_status check নেই। আগে login করা account suspend করলেও session দিয়ে কাজ চলার পথ থাকে; owner suspended হলে তার staff access-এর জন্যও owner-status check পাওয়া যায়নি।

**Fix:** Global active-account/active-owner middleware দিন; disable/suspend-এ session revoke করুন।

### F10 — High: queue processing locking ও recovery অসম্পূর্ণ

Evidence: `ProcessAssessment::handle()` এবং `AssessmentController::start()`।

Status read → update atomic নয়। Duplicate guard শুধু `processing/completed` দেখে, অথচ সফল job `review_required` হয়। Worker crash/timeout-এ processing অবস্থায় থেকে গেলে retry guard কাজ না করে return করতে পারে; `failed()` recovery hook নেই। Full processing old results delete করে; delayed duplicate job teacher-reviewed rows মুছে দিতে পারে।

**Fix:** Atomic claim/unique job, processing attempt ID, finalised-result protection, timeout recovery ও idempotent writes দিন।

### F11 — High: AI response-এর marks/schema server-side validate হয় না

Evidence: `AIPaperMarker::parseResponse()`; `ProcessAssessment::handle()`।

JSON parse এবং questions array ছাড়া per-question range, unique IDs, max_marks, total sum, confidence বা percentage consistency validation নেই। Negative/over-maximum marks বা inconsistent totals persist হতে পারে। Optional MS-এর ক্ষেত্রে external prompt-এর explicit fallback নেই।

**Fix:** Strict schema ও domain validation দিন; authoritative question maxima ব্যবহার করুন; percentage/grade server-side calculate করুন; no-MS provisional workflow স্পষ্ট করুন।

### F12 — High: monthly paid subscription expiry/renewal lifecycle নেই

Evidence: plan `billing_period`; `PaymentController::callback()`; `User::effectivePlan()`; migrations ও console routes।

Payment success-এ শুধু plan_id update হয়। Subscription start/end, expiry বা renewal state এবং expired entitlement revoke করার mechanism পাওয়া যায়নি। Quota calendar month শুরুতে reset হয়; purchase-date billing cycle নয়।

**Fix:** Subscription entitlement period, renewal/cancel/expiry handling তৈরি করুন। Calendar-month quota intended হলে pricing-এ স্পষ্ট করুন।

### F13 — Medium: report PDF teacher feedback ও subquestion detail হারায়

Evidence: `PdfReportService::make()` / `simplePdf()`; `reports/result.blade.php`।

HTML report teacher_comment দেখায়, downloadable PDF শুধু AI feedback নেয়। PDF label-এ `question_part` নেই; AI output Q1(a)/Q1(b) হলে দুটোই “1” হতে পারে। প্রতি line 110 characters কেটে দেয়, wrap করে না; Helvetica plain-text writer Bengali/Unicode support দেয় না। PDF ও HTML percentage/grade feature flags consistently মানে না।

**Fix:** একই report data presenter ব্যবহার করুন; teacher feedback precedence, complete question label, wrapping, Unicode font ও feature gates দিন।

### F14 — Medium: report finalisation-এ grade scale বদলে যায়

Evidence: `AdaptivePaperMarker::grade()` বনাম `ResultController::recalculate()`।

Default marker 90%→9, 80%→8 ইত্যাদি দেয়; edit/finalise recalculation 90%→A+, 80%→A দেয়। একই marks রেখে finalise করলেও grade label বদলাতে পারে।

**Fix:** Central grading policy রাখুন, assessment/board-specific scale configure করুন।

### F15 — Medium: selected recheck আসলে পুরো paper আবার mark করে

Evidence: `ResultController::recheckQuestion()` calls `$marker->mark($result->assessment)`।

শুধু selected row update হয়, কিন্তু marker পুরো assessment চালায়। External AI cost ও latency পুরো paper-এর হতে পারে। এটি selected-update flow; targeted question-only AI call নয়।

**Fix:** Marker contract-এ question IDs/context দিন এবং selected প্রশ্নগুলোই process করুন।

### F16 — Medium: bulk upload partial writes এবং CSV error handling

Evidence: `BulkAssessmentController::store()`; `StudentController::import()`।

Bulk loop-এ প্রথম paper/student create হওয়ার পরে পরের invalid student row-এ request abort হতে পারে; rollback নেই। CSV row-এ header-এর চেয়ে বেশি cells থাকলে `array_combine()` error-এর পথ আছে; quoted multiline CSV `file()` দিয়ে নির্ভরযোগ্যভাবে parse হবে না।

**Fix:** সব rows prevalidate করুন, row-numbered errors দিন, `fgetcsv()` ব্যবহার করুন, DB transaction ও file cleanup যোগ করুন।

### F17 — Medium: teacher allocation পরিচালনার flow অসম্পূর্ণ

Evidence: `routes/web.php` resource excludes update/edit; `AssessmentController::store()`; `BulkAssessmentController::store()`।

Single paper creation-এ coach teacher select করতে পারে। Bulk form/controller coach allocation নেয় না; পরে existing paper assign/reassign করার action পাওয়া যায়নি। Inactive teacher-কে crafted single request দিয়ে assign করাও ঠেকানো হয়নি, যদিও dropdown active-only।

**Fix:** Owner-authorized assign/reassign endpoint, bulk assignment এবং active teacher validation দিন।

### F18 — Medium: authentication verification ও throttling অসম্পূর্ণ

Evidence: `User` model, `AuthController`, `routes/web.php`।

Routes-এ `verified` আছে, কিন্তু User `MustVerifyEmail` implement করে না। Registration verification notification পাঠায় না। Login/register routes-এ dedicated throttling দেখা যায়নি। তাই verified middleware দেখে verified-email enforcement সম্পূর্ণ ধরে নেওয়া যাবে না।

**Fix:** Email verification intended হলে contract/event/notification flow পূর্ণ করুন; login/register rate limits দিন।

### F19 — Medium: payment integration-এ missing cancel route ও stub

Evidence: `GatewayManager::paypalBegin()` references `payments.cancel`; `routes/web.php`-তে ওই নামের route নেই। `PaymentController::cancel()` আছে, কিন্তু routed নয়। ফলে PayPal checkout URL তৈরিতেই missing-route exception হওয়ার কথা। Paytm begin explicitly exception দেয়।

**Fix:** Cancel route দিন ও owner access enforce করুন। Live-ready না হলে incomplete gateways enable করতে দেবেন না।

### F20 — Medium: parent link resend/status handling অসম্পূর্ণ

Evidence: `ResultController::finalise()` token তৈরি করে; `ParentCommunicationController::ensureLink()` শুধু token অনুপস্থিত হলে renew/status update করে।

Finalise-এ token আগে থেকেই থাকার কারণে send-এর সময় `reported` status update সাধারণত হয় না। Expired token থেকে গেলে resend একই expired link পাঠাতে পারে। Public controller expiry reject করে—expiry protection আছে, renewal handling অসম্পূর্ণ।

**Fix:** Expiry check করে rotate/renew করুন; delivery success-এ status আলাদাভাবে update করুন।

### F21 — Low/Medium: notifications এবং student linking পুরো user flow নয়

Evidence: `SettingsController::updateNotifications()`, `SupportTicketNotifier`, `ProcessAssessment`, `PortalController`, `StudentController`।

Support notifications বাস্তব implementation আছে। কিন্তু processing/reports/billing preferences save হলেও সেই events-এর notifier wiring পাওয়া যায়নি। Linked student portal code আছে, কিন্তু সাধারণ student form/import-এ `login_user_id` linking action নেই; seeded student account দিয়ে দেখানো flow-এর বাইরে user onboarding অসম্পূর্ণ।

**Fix:** Promised event notifications এবং authorized account-link/invitation flow যুক্ত করুন।

## 3. 30টি main functional area-এর যাচাই

| # | Area | Source-level verdict |
|---|---|---|
| 1 | Homepage/pricing/register/login | Present; auth gaps F18 |
| 2 | Role-aware dashboards | Present; status gaps F09 |
| 3 | Plan database permissions | Present; supplied DB matches seeders |
| 4 | Sidebar | Role/feature checks and test source present; browser untested |
| 5 | Upload wizard | Present; stated step placement matches |
| 6 | Queue/sync processing | Present; reliability gaps F10 |
| 7 | OCR contract/demo reader | Present; production adapter is explicit stub |
| 8 | Question marks/confidence | Present; marking/parser gaps F04–F05/F11 |
| 9 | Edit/recheck/approval | Present; F14–F15 |
| 10 | Plan-aware reports | Route checks present; PDF gaps F13 |
| 11 | Mode2/Mode3 separation | Default DB and module guards match |
| 12 | Students/CSV import | Present; F07/F16/F21 |
| 13 | Bulk WA | Present; F07–F08/F16 |
| 14 | Analytics | Present; depends on marking correctness |
| 15 | Parent details/communication | Mode3 gating present |
| 16 | Email/demo mobile delivery | Email implementation present; mobile deliberately demo; real delivery untested |
| 17 | Expiring parent links | Expiry check present; renewal gap F20 |
| 18 | Teacher logins/allocation | Accounts present; F09/F17 |
| 19 | Billing/usage | Incomplete; F01–F02/F08/F12/F19 |
| 20 | Settings/notifications/support | Settings/support present; notification gap F21 |
| 21 | Admin plans/users | Present; status enforcement F09 |
| 22 | Database flags/limits | Present; not enforced consistently on every path |
| 23 | Usage/audit tables | Present; quota does not consistently use ledger |
| 24 | Status/timestamps | Present; F10/F20 |
| 25 | Tenant checks/private files | Assessment, file and attachment ownership checks present; no runtime penetration test |
| 26 | Background job architecture | Present; production recovery needs work |
| 27 | Demo marker/production points | Present; valid demo scope, not validated exam marking |
| 28 | Role end-to-end flows | Code/tests present; execution not verified |
| 29 | Acceptance documentation | Present; checklist unchecked |
| 30 | Deployment checklist | README/handoff guidance present; release preparation incomplete |

## 4. Production packaging ও dependency observations

- ZIP-এ actual `.env`, `.git`, existing SQLite database, logs/compiled views এবং vendor tree আছে। Source release-এ sanitized `.env.example` ও reproducible install রাখুন। কোনো actual secret leaked হয়েছে কিনা এই report তা দাবি করছে না; প্রয়োজন হলে privately check/rotate করুন।
- Seeder known demo admin/customer passwords তৈরি করে এবং `updateOrCreate()` দিয়ে password reset করতে পারে। Production seeding থেকে demo users আলাদা করুন।
- Local DOC পড়তে `antiword`; scanned PDF-তে `pdftoppm`, `tesseract`, permitted shell execution; DOCX/corrected ZIP-এর জন্য PHP ZipArchive দরকার। Runtime preflight-এ এগুলো verify করুন। Handwriting accuracy এখানে যাচাই হয়নি।
- `ProductionDocumentReader` সর্বদা exception দেয়; local reader-এর scanned-PDF fallback আলাদা implementation।
- Corrected-paper ZIP আপনার “package” requirement পূরণ করার কাঠামো আছে; PDF-এর ওপর annotated ticks/comments এই implementation তৈরি করে না।

## 5. Developer-কে দেওয়া কাজের অগ্রাধিকার

1. **Release blocker:** F01/F02 payment bypass ও callback verification; F19 missing route/stubs।
2. **Marking correctness:** F03–F06/F11 AI settings, real mark allocation, subquestions, inserts ও strict result validation।
3. **Access/limits/reliability:** F07–F10/F12 quota, active accounts, queue and subscription lifecycle।
4. **Report/teacher workflow:** F13–F17/F20 feedback, grades, targeted recheck, bulk transactions, allocation, link renewal।
5. **Completion:** F18/F21 verification, notifications, student linking; sanitized release and documentation refresh।

## 6. সংশোধনের পরে minimum acceptance tests

- Fresh test database-এ migrations/seeding এবং existing included 33 tests চালান; result ও failures সংরক্ষণ করুন।
- Verified/unverified, inactive/suspended এবং already logged-in suspended user পরীক্ষা করুন।
- Single এবং bulk-এ missing QP/WA reject, optional MS accept, unsupported formats reject পরীক্ষা করুন।
- Free concurrent uploads/starts এবং paid processed-paper deletion quota ফেরত না দেওয়ার test করুন।
- Mode2-তে student cap পূর্ণ করে bulk new-student upload reject হচ্ছে দেখুন।
- Q1(a)[2], Q1(b)[3], Q2[5] sample-এর exact marks, parent totals, answer matching, inserts এবং no-MS provisional flow মিলান।
- AI malformed JSON, duplicate question, negative/over-max marks এবং total mismatch reject হচ্ছে দেখুন।
- Selected-question recheck unrelated question/context/cost-এ প্রভাব ফেলছে কিনা পরীক্ষা করুন।
- Finalise-এর আগে/পরে same marks-এ same grade এবং downloadable report-এ latest teacher feedback/parts মিলান।
- Mode2 forbidden parent/team/corrected routes ও cross-tenant file/result URLs block হচ্ছে দেখুন।
- Mode3 coach single/bulk paper allocate/reassign করতে পারে, inactive teacher পারে না—দেখুন।
- Parent real mail attachment, secure public PDF, expiry, renewal ও reopen-এ পুরোনো token invalidation পরীক্ষা করুন।
- Duplicate job, worker kill/timeout, retry এবং finalised paper-এ stale job আসার recovery পরীক্ষা করুন।
- Mocked + provider sandbox payment-এ signature, order, amount, currency, duplicate callback, wrong gateway এবং cancellation পরীক্ষা করুন।
- Expired subscription revoke ও renewal restore test করুন।

## সিদ্ধান্ত

**UI ও workflow-এর বড় অংশ তৈরি আছে, এবং Mode2/Mode3-এর default permission matrix আপনার নিয়মের সঙ্গে মেলে। তবে billing নিরাপত্তা, marking correctness, quota এবং account/queue handling-এর কারণে এখনই “Full System V6 fully correct/production ready” বলা যাবে না।** Demo review করা যেতে পারে; paid public launch এবং বাস্তব পরীক্ষার নির্ভরযোগ্য marking-এর আগে উপরোক্ত release blockers ও acceptance tests শেষ করা প্রয়োজন।

Audit fingerprint: `SHA256 cbec558e1962b69ddc9b97bbbaad34024cc925bb2c62cf06dddb7bbd6c464022`.
