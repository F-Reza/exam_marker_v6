# Exam Marker — Full System V6: Audit Report

তারিখ: ১০ নভেম্বর ২০২৬ · মানদণ্ড: ব্যবহারকারীর দেওয়া ৩০টি functional area এবং ৮টি product rule।

**রায়: সবকিছু ঠিক নেই।** Public pages, সাধারণ registration/upload, role/plan navigation, Support Center এবং বেশ কিছু report/access flow কাজ করছে। কিন্তু paid-plan activation, marking reliability, processing retries, quota enforcement ও কয়েকটি user flow-তে নিশ্চিত সমস্যা আছে। এই অবস্থায় সম্পূর্ণ V6 acceptance বা production readiness ঘোষণা করা যাবে না।

এই audit-এ application code বা বাস্তব database-এর business data পরিবর্তন করা হয়নি। শুধু এই রিপোর্ট এবং isolated verification probes তৈরি হয়েছে।

## কীভাবে যাচাই করা হয়েছে

| পরীক্ষা | ফলাফল / সীমা |
|---|---|
| বিদ্যমান regression suite | **৩৩টি test, ৫৯২টি assertion পাস**। প্রধানত role/plan access, upload permissions, result permissions, Support Center এবং notifications। |
| অতিরিক্ত audit probes | **২৪টি probe, ১২০টি assertion**। এর মধ্যে ১৭টি probe অনাকাঙ্ক্ষিত আচরণ reproduce করেছে; ৭টি normal flow যাচাই করেছে। Defect probe পাস করার অর্থ সমস্যা reproduce হয়েছে—feature সঠিক হয়েছে নয়। |
| PHP syntax | application/config/routes/database/bootstrap-এর **১০৭টি PHP file** lint পাস। |
| Blade syntax | **৫২টি Blade template** compile/parse পাস। |
| বর্তমান database-এর plan rules | Free, Mode 1, Mode 2, Mode 3-এর পরীক্ষা করা মূল feature flags ও Free check limit V6 নিয়মের সঙ্গে মিলে। শুধু read-only query করা হয়েছে। |
| বর্তমান runtime | Laravel **12.68.0**; OCR `local`; `async=false`; queue connection `database`; database queue `retry_after=90`। |
| Local document processing | Synthetic DOCX দিয়ে বাস্তব local extraction + synchronous adaptive job সফল; ভিন্ন উত্তরে ভিন্ন score পাওয়া গেছে। |
| External services | AI/payment/SMTP/SMS-এ বাস্তব request বা অর্থ লেনদেন করা হয়নি। প্রয়োজনমতো HTTP/Mail fake এবং synthetic data ব্যবহার করা হয়েছে। |

Test database ছিল SQLite `:memory:`; upload storage ছিল fake disk। `.env`, API keys, existing answer scripts বা SQL dump-এর গোপন তথ্য রিপোর্টে নেওয়া হয়নি। Live queue worker, handwriting accuracy, browser visual behaviour এবং external provider delivery এই audit-এর মাধ্যমে certified নয়।

চিহ্নের অর্থ: **যাচাইকৃত** = উল্লেখ করা সীমায় পরীক্ষা/code review সঠিক; **আংশিক** = implementation আছে, কিন্তু সমস্যা বা গুরুত্বপূর্ণ verification gap আছে; **ব্যর্থ** = প্রয়োজনীয় behaviour-এর বিপরীত ফল reproduce হয়েছে।

## ৩০টি functional area অনুযায়ী ফলাফল

| # | Functional area | অবস্থা | পর্যবেক্ষণ |
|---|---|---|---|
| 1 | Public homepage, pricing, registration, login | যাচাইকৃত | পাঁচটি public page খোলে। Registration Free plan দেয়, password hash হয়, submitted `is_admin`/paid plan গ্রহণ করে না। Account-state সমস্যা #2/F14-এ। |
| 2 | Role-aware authentication ও dashboards | আংশিক | Teacher/student/coaching/admin routing এবং permissions কাজ করে। কিন্তু inactive teacher login করতে পারে; email-verification route-ও অসম্পূর্ণ। F14, F16। |
| 3 | Free / Mode 1 / Mode 2 / Mode 3 database permissions | যাচাইকৃত | মূল flags ও effective owner-plan inheritance কাজ করে। Runtime-এ পরীক্ষা করা plan rules-ও মেলে। Quota/payment bypass আলাদা সমস্যা। |
| 4 | Full sidebar views | যাচাইকৃত | সাত ধরনের seeded account-এর দৃশ্যমান menu links regression tests-এ খোলা হয়েছে; unavailable features লুকানো থাকে। |
| 5 | QP + optional MS একই wizard step, WA শেষ step | যাচাইকৃত | View structure সঠিক। QP/WA required, MS ছাড়া upload সফল। ভুল file selection সরানোর controls আছে। |
| 6 | Queue/synchronous paper processing | আংশিক | বাস্তব local DOCX দিয়ে sync flow সফল; async dispatch যাচাইকৃত। Duplicate job এবং retry configuration নিরাপদ নয়; worker নিজে চালিয়ে দেখা হয়নি। F04। |
| 7 | OCR contract ও demo reader | যাচাইকৃত | Contract, demo/local reader এবং production adapter extension point আছে। Production OCR adapter এখনও placeholder—live handwriting integration সম্পন্ন নয়। |
| 8 | Question-wise AI marks ও confidence | ব্যর্থ | Rows/confidence তৈরি হয়; কিন্তু `1(a)`/`1(b)` answer matching-এ সঠিক উত্তরও 0 পেয়েছে। Invalid AI marks/percentage-ও সংরক্ষিত হয়। F06, F07। |
| 9 | Teacher edit, selected recheck, final approval | আংশিক | Permission, field-specific editing, recheck, finalise/reopen ও teacher-mark preservation-এর সাধারণ controller flow পাস। কিন্তু duplicate background job approved teacher marks মুছতে পারে। Recheck পুরো paper marker চালিয়ে selected row-টি update করে। |
| 10 | Plan-aware reports/downloads | যাচাইকৃত | অনুমতি অনুযায়ী HTML/PDF report ও Mode 3 corrected ZIP কাজ করে। ZIP-এ original WA + correction-summary HTML থাকে; answer PDF-তে সরাসরি annotation করা হয় না। |
| 11 | Mode 2 বনাম Mode 3 restrictions | যাচাইকৃত | Default/current checked flags-এ Mode 2 parent communication/details, team এবং corrected-paper access পায় না। Unauthorized mutation/JSON requests আটকায়। |
| 12 | Student records ও CSV import | আংশিক | সাধারণ CSV import সফল; Mode 2-তে parent columns উপেক্ষা করা হয়। Extra-column CSV 500 দেয়; student login-linking-এর UI/API নেই। F12 এবং অতিরিক্ত gap। |
| 13 | Mode 2/3 Bulk WA upload | আংশিক | Shared QP/MS এবং একাধিক WA-এর মূল flow আছে। Student limit bypass এবং ব্যর্থ batch-এর partial save রয়েছে। F10, F11। |
| 14 | Analytics | যাচাইকৃত | Plan gate, accessible-paper scope, graph/topic/grade visibility এবং basic score fallback পরীক্ষা করা হয়েছে। Real exam marking accuracy এই verdict-এর অন্তর্ভুক্ত নয়। |
| 15 | Mode 3 parent details ও communications | আংশিক | Parent fields ও protected backend endpoints আছে। কিন্তু UI থেকে report পাঠানোর button/form নেই। F09। |
| 16 | Email ও demo mobile secure-link delivery | আংশিক | Mock email path, PDF generation এবং demo mobile log সফল। Normal UI entry নেই; live SMTP/SMS delivery যাচাই হয়নি। |
| 17 | Expiring parent report links | যাচাইকৃত | Future token-এ PDF খোলে; expired token-এ HTML/PDF উভয় route 410 দেয়। Reopen flow পুরোনো token বাতিল করে। |
| 18 | Real teacher login ও paper allocation | আংশিক | Mobile দিলে teacher account তৈরি, login ও allocated paper দেখা সফল। Optional mobile বাদ দিলে 500 হয়; inactive account-ও login করতে পারে। Bulk coaching upload-এর allocation UI অসম্পূর্ণ। |
| 19 | Billing/usage limits | ব্যর্থ | Payment ছাড়া direct plan change সম্ভব; unsigned callback paid করতে পারে; processed paper মুছে paid quota পুনরায় পাওয়া যায়; bulk student quota-ও ভাঙে। F01, F02, F10। |
| 20 | Settings, notifications ও support tickets | আংশিক | Support Center-এর owner/admin visibility, thread, close/reopen, feedback/solutions ও notifications শক্তভাবে tested। অন্য notification preferences ও কয়েকটি system setting শুধু save হয়, বাস্তব effect নেই। F15। |
| 21 | Platform admin plans/users | আংশিক | Admin gate, page access, plan assignment ও linked staff update সফল। User creation-এও optional mobile বনাম database constraint-এর অসামঞ্জস্য আছে; সব CRUD edge case certified নয়। |
| 22 | Database-controlled feature flags/limits | আংশিক | Navigation/action flags ও staff inheritance কাজ করে। কয়েকটি quota path এবং কিছু dormant feature flag-এর behaviour অসম্পূর্ণ। F10 ও অতিরিক্ত gap। |
| 23 | Usage ও audit tables | যাচাইকৃত | Tables/records আছে; processing, result, upload ও support operations-এ records তৈরি হয়। Paid quota অবশ্য consumed usage records ব্যবহার করছে না। |
| 24 | Processing statuses ও timestamps | আংশিক | Start/completion/error timestamps আছে। কিন্তু `completed` বনাম `review_required` অসামঞ্জস্য এবং simulated progress রয়েছে। F04। |
| 25 | Tenant access checks ও private files | যাচাইকৃত | Own/assigned/published-student scopes, support privacy ও cross-owner private-file denial পরীক্ষায় পাস। এটি পূর্ণ penetration-test certification নয়। |
| 26 | Laravel background job architecture | আংশিক | `ShouldQueue`, database queue ও dispatch আছে। Idempotency/retry/actual progress সমস্যা রয়েছে। F04। |
| 27 | Demo marker ও production adapter points | যাচাইকৃত | Demo/adaptive path এবং service boundaries আছে; local synthetic text-এ ফল আলাদা হয়। এটি production OCR/LLM integration বা examiner accuracy-এর প্রমাণ নয়। |
| 28 | Role-specific end-to-end flows | আংশিক | Basic customer/admin/support flows চলে। Parent send UI, student login linking, team optional-phone ও marking/queue issues-এর জন্য পুরো flow সম্পূর্ণ নয়। |
| 29 | Acceptance documentation | আংশিক | Documentation আছে; README এখনও V5 শিরোনামে, V6 checklist-এর checkbox-গুলো unchecked। নতুন পরীক্ষার ফলের সঙ্গে acceptance docs reconcile করা হয়নি। |
| 30 | Deployment checklist | আংশিক | Checklist আছে। Live provider/worker/hosting verification বাকি এবং নিচের release blockers আছে। Install scripts fresh-install oriented; safe upgrade procedure আলাদা দরকার। |

## নিশ্চিত সমস্যা ও প্রমাণ

### F01 — Payment ছাড়া paid plan activation · Critical

`BillingController::change()` authenticated owner-এর submitted `plan_id` সরাসরি save করে। কোনো successful transaction বা demo-only environment gate নেই। Probe-এ Free account Mode 3 হয়েছে এবং payment transaction count ছিল 0।

এটি controller-এর message-এ demo behaviour হিসেবে বর্ণিত; সমস্যা হলো route-টি সাধারণ account-এর জন্য খোলা এবং production guard নেই। Paid release-এর আগে বন্ধ বা verified payment flow-এর অধীনে নিতে হবে।

প্রমাণ: `app/Http/Controllers/BillingController.php:15`, `routes/web.php:280`।

### F02 — Payment callback success payload বিশ্বাস করে · Critical

PayU/PhonePe/Paytm verifier request-এর status string থেকে paid নির্ধারণ করে। Callback controller transaction-এর stored gateway-এর সঙ্গে callback provider, expected amount/currency বা trusted verification binding মিলিয়ে দেখে না।

Isolated probe-এ unauthenticated, unsigned PayU success callback দিয়ে একটি unpaid **PayPal** transaction paid হয়েছে এবং owner-এর plan upgrade হয়েছে; gateway-তে কোনো request যায়নি। PayU path runtime-proven; অন্য দুই verifier একই ধরনের trust pattern অনুসরণ করে।

প্রমাণ: `app/Services/Payments/GatewayManager.php:40`, `:47`, `:52`; `app/Http/Controllers/PaymentController.php:302`।

### F03 — PayPal checkout-এ missing cancel route · High

`paypalBegin()` `payments.cancel` route তৈরি করতে চায়, কিন্তু route registration নেই। Mock token response দিয়েও checkout failed হয়েছে; transaction error ছিল `Route [payments.cancel] not defined`।

প্রমাণ: `app/Services/Payments/GatewayManager.php:32`, `routes/web.php`।

### F04 — Duplicate job approved marks overwrite করতে পারে · High

Job duplicate guard শুধু `processing`/`completed` দেখে; সফল job status হয় `review_required`। Finalised paper-ও guard পার হয়। Probe-এ teacher mark **9** মুছে নতুন AI mark **2** হয়েছে এবং status `review_required` হয়েছে।

একই অংশে আরও দুটি gap আছে: database queue retry interval 90 seconds, job timeout 180 seconds; processing screen real status polling না করে random timer শেষে result page-এ পাঠায়। বর্তমান local runtime sync, তাই live worker failure/retry পরীক্ষা করা হয়নি।

প্রমাণ: `app/Jobs/ProcessAssessment.php:34`, `:37`, `:82`, `:209`, `:283`, `:349`; `config/queue.php:2`; `resources/views/assessments/processing.blade.php:10`।

### F05 — Admin AI provider/model/key এবং actual dispatch আলাদা · High

Admin settings database-এ save হয়; service binding সেই provider/status দেখে AI engine নির্বাচন করে। কিন্তু `AIPaperMarker` actual provider/model/key নেয় `config/ai.php` থেকে। Probe-এ admin-selected Gemini/model/key থাকা সত্ত্বেও config-selected OpenAI/model ব্যবহৃত হয়েছে। Azure UI/binding-এ আছে, কিন্তু `callAI()`-তে Azure case নেই।

প্রমাণ: `app/Providers/AppServiceProvider.php:151`, `app/Services/AIPaperMarker.php:299`, `app/Http/Controllers/AdminAISettingController.php:48`।

### F06 — Local adaptive subquestion matching ভুল · High

Question parser `1(a)`-কে `1a` label বানায়; answer splitter সেই label সরাসরি খোঁজে। Probe-এ mark scheme ও student answer একই থাকা সত্ত্বেও `1(a)`/`1(b)`-এর দুই row-তে score 0 হয়েছে। এছাড়া local parser detected questions-এর মধ্যে total marks সমানভাবে ভাগ করে—official per-question weighting পড়ছে না। শেষটি demo heuristic-এর সীমা, examiner-grade marking নয়।

প্রমাণ: `app/Services/AdaptivePaperMarker.php:649`, `:726`, `:771`।

### F07 — AI payload validation অপর্যাপ্ত · High

Job শুধু `questions` একটি array কি না দেখে। Probe-এ empty questions success হিসেবে `review_required` হয়েছে; আবার **20/10 marks এবং 200%** অপরিবর্তিত save হয়েছে। Bounds, totals, minimum question count ও payload consistency validation দরকার।

অতিরিক্ত বর্তমান feature gap: returned `writing_rubric` database-এ রাখা হচ্ছে না; probe-এ field null ছিল। Rubric field migration আছে, কিন্তু persistence/fillable path-এ অনুপস্থিত।

প্রমাণ: `app/Jobs/ProcessAssessment.php:173`, `:283`, `:353`; `app/Models/QuestionResult.php`।

### F08 — Insert/Source Booklet scoring context-এ ব্যবহার হচ্ছে না · High

Local reader insert text extract করে। কিন্তু AI prompt-এ তা যোগ হয় না; adaptive engine শুধু `insert_used` summary flag দেখায়। Probe-এর unique insert marker outgoing prompt-এ ছিল না। Source-based English/Humanities paper-এর পূর্ণ context তাই বাদ যাচ্ছে।

অন্য prompt inconsistency: MS optional হলেও prompt বলে “Use ONLY the supplied mark scheme”; no-MS branch-এর পরিষ্কার marking instruction নেই। এর actual live-model ফল পরীক্ষা করা হয়নি।

প্রমাণ: `app/Services/Ocr/LocalDocumentReader.php:82`, `app/Services/AIPaperMarker.php:98`, `:115`; `app/Services/AdaptivePaperMarker.php:325`।

### F09 — Parent delivery backend আছে, UI entry নেই · High

Email/mobile endpoints কাজ করে এবং isolated tests-এ email/PDF/demo log সফল। কিন্তু কোনো Blade view `parent-report.send` বা `parent-report.mobile` route ব্যবহার করে না। Communications page শুধু delivery log দেখায়। ফলে normal user flow দিয়ে report পাঠানো যায় না।

প্রমাণ: `app/Http/Controllers/ParentCommunicationController.php:9`, `:15`; `resources/views/communications/index.blade.php` এবং সম্পূর্ণ Blade route-reference search।

### F10 — Usage/student quota bypass · High

- **Paid paper quota:** surviving assessment count থেকে quota গণনা করা হয়। Limit 1-এ নতুন upload আটকেছিল; processed paper delete করার পর আরেকটি upload সফল হয়েছে, অথচ consumed usage record ছিল।
- **Bulk student quota:** bulk flow `Student::firstOrCreate()` করে, student limit দেখে না। Limit 1 থাকা অবস্থায় দ্বিতীয় student তৈরি হয়েছে।

প্রমাণ: `app/Http/Controllers/AssessmentController.php:77`, `app/Http/Controllers/BillingController.php:9`, `app/Http/Controllers/BulkAssessmentController.php:27`। Free lifetime flag-এর সাধারণ serial behaviour আলাদাভাবে পাস করেছে।

### F11 — ব্যর্থ Bulk batch partial data রেখে দেয় · Medium

Batch-এর দ্বিতীয় row-তে unauthorized student ID দিলে request 403 হয়েছে; প্রথম row-এর assessment ইতিমধ্যে তৈরি ছিল। Batch-wide validation/transaction নেই। পরে retry করলে duplicate paper, quota consumption বা orphaned file হওয়ার ঝুঁকি থাকে।

প্রমাণ: `app/Http/Controllers/BulkAssessmentController.php:25`–`:31`।

### F12 — Malformed CSV-তে 500 · Medium

Header-এর চেয়ে বেশি cell থাকা row `array_combine()`-এ error দেয়। Probe CSV `name` header এবং extra-column row দিয়ে HTTP 500 reproduce হয়েছে। Row-level shape/type/email validation ও পরিষ্কার user error দরকার। সাধারণ valid CSV import কাজ করেছে।

প্রমাণ: `app/Http/Controllers/StudentController.php:17`।

### F13 — Optional mobile field এবং database constraint মেলে না · Medium

Team form/controller mobile optional রাখে, কিন্তু users table-এ mobile non-nullable। Mobile বাদ দিয়ে teacher তৈরিতে HTTP 500 হয়েছে। Mobile দিলে real teacher creation/login/allocation সফল। Admin user-creation validation-এও একই nullable-mobile pattern আছে; ওই দ্বিতীয় path code-review finding।

প্রমাণ: `app/Http/Controllers/TeamController.php:33`, `resources/views/team/index.blade.php:91`, `database/migrations/0001_01_01_000000_create_users_table.php:3`, `app/Http/Controllers/AdminUserController.php:204`।

### F14 — Inactive account login করতে পারে · High

Team management-এ `inactive` status আছে। Login শুধু `suspended` আটকায়। Probe-এ inactive teacher সঠিক password দিয়ে login করে workspace খুলেছে। Existing authenticated session-এর account-status enforcement-ও এই audit-এ certified নয়।

প্রমাণ: `app/Http/Controllers/AuthController.php:16`, `app/Http/Controllers/TeamController.php:63`।

### F15 — কিছু system settings ও notification preferences-এর effect নেই · Medium

Probe-এ `maintenance_mode=1` রেখেও public homepage 200 হয়েছে; `ai_enabled=0` রেখেও AIPaperMarker resolve হয়েছে। Code search-এ email/SMS global switches ও processing/report/billing notification preferences-এর কার্যকর consumer পাওয়া যায়নি। Support notifications অবশ্য কাজ করে।

প্রমাণ: `app/Http/Controllers/Admin/SystemConfigurationController.php:352`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/SettingsController.php:16`; application-wide usage search।

### F16 — Email verification flow অসম্পূর্ণ · Medium

User model `MustVerifyEmail` contract implement করে না, তাই `verified` middleware unverified account-কে আটকাচ্ছে না। `/email/verify` missing `auth.verify-email` view-এর জন্য 500 দেয়। Product policy অনুযায়ী verification সম্পূর্ণ করতে হবে, অথবা misleading dead flow সরাতে হবে।

প্রমাণ: `app/Models/User.php:7`, `routes/web.php:94`, `:114`।

## Code review-তে পাওয়া অতিরিক্ত flow/documentation gap

- **Student login linking:** `students.login_user_id` আছে এবং demo seeder তা পূরণ করে। কিন্তু নতুন customer student record-কে real login account-এর সঙ্গে link করার controller/form পাওয়া যায়নি; portal বিদ্যমান link শুধু পড়ে। Seeded demo ছাড়া linked-student flow অসম্পূর্ণ।
- **Bulk teacher allocation:** single-paper create-এ teacher assignment আছে; coaching bulk upload-এ teacher selector নেই এবং পরে assessment reassign করার update route নেই। Staff নিজের bulk upload করলে self-assignment হয়।
- **কিছু dormant feature flags:** `student_portal`, `parent_portal`, `custom_branding` seed/admin flags হিসেবে আছে; সংশ্লিষ্ট business behaviour-এর সঙ্গে সম্পূর্ণ binding পাওয়া যায়নি।
- **Core lifecycle notifications:** support ছাড়া processing/report/billing notifications dispatch হওয়ার code পাওয়া যায়নি। Settings-এর preference save হওয়া notification delivery-এর প্রমাণ নয়।
- **Documentation:** README-এর main title/product-rule heading V5; আলাদা V6 additions আছে। V6 acceptance checklist সব unchecked। Demo বনাম production capability এবং বর্তমান test coverage পরিষ্কার করে হালনাগাদ দরকার।

## Live integration ও deployment সীমা

- Real AI model accuracy, real handwriting/scanned scripts, examiner calibration এবং subject-specific marking এই audit-এ যাচাই হয়নি। Local adaptive scoring একটি heuristic।
- Production OCR adapter এখনও explicit placeholder। এই workstation-এর PATH lookup-এ `pdftoppm` পাওয়া গেছে; `tesseract` ও `antiword` পাওয়া যায়নি। Local reader এগুলো সরাসরি shell command হিসেবে চালায়; DOC helper-এ Unix `/dev/null` redirection আছে। ফলে Windows DOC/scanned-PDF processing প্রস্তুত বলে ধরা যাবে না।
- Live SMTP delivery বা real SMS/WhatsApp পাঠানো হয়নি। Demo mobile path শুধু secure link log করে—এটি ঘোষিত demo behaviour।
- Paytm checkout ইচ্ছাকৃতভাবে unsupported/checksum integration required বলে থামে। bKash/card settings UI আছে, কিন্তু GatewayManager-এ begin adapter নেই।
- Actual queue worker/crash recovery, server hosting, HTTPS/backup/monitoring এবং production load চালিয়ে দেখা হয়নি। Current local runtime sync।
- Install scripts-এ `migrate:fresh --seed` আছে; এগুলো fresh installation-এর জন্য। Existing deployment upgrade-এর জন্য incremental migration procedure আলাদা করতে হবে। PostgreSQL সুপারিশ docs-এ আছে, কিন্তু বর্তমান database config-এ SQLite/MySQL connection দেওয়া আছে।
- Configured database-এ read-only `CAST(... AS INTEGER)` query গ্রহণ করা হয়েছে; তাই ওই suspected portability issue-কে এই environment-এর confirmed defect ধরা হয়নি।

## Product rules-এর সরাসরি verdict

| Rule | Verdict |
|---|---|
| QP/WA compulsory, MS optional | পাস: validation এবং MS ছাড়া upload পরীক্ষা করা হয়েছে। |
| PDF/DOC/DOCX only | Upload allow-list আছে; PNG reject হয়েছে। DOCX extraction চলে। DOC/scanned processing-এর environment/integration সীমা উপরে দেওয়া আছে। |
| Free: one lifetime check | সাধারণ serial flow ও পূর্বের regression tests-এ পাস; concurrency/load test হয়নি। |
| Mode 1: no bulk upload | পাস: menu/HTML/JSON/mutation restrictions tested। |
| Mode 2: analysis/edit/recheck/bulk/import/report | মূল implementation আছে; marking, quota এবং CSV issues-এর জন্য পুরোপুরি সঠিক নয়। |
| Mode 2: no parent details/communication/team/corrected paper | Checked current/default flags ও authorization tests-এ পাস। |
| Mode 3: parent communication/corrected package/teachers/allocation | আংশিক: backend/package/normal teacher allocation চলে; UI ও edge-case gaps বাকি। |
| No Mode 3 Plus / multiple branches | পাস: চারটি default mode; application/schema/view search-এ branch feature পাওয়া যায়নি। |

## কী আগে ঠিক করা উচিত

1. **Paid/public release-এর আগে:** F01/F02 বন্ধ করা এবং payment verification binding সঠিক করা; PayPal missing route ঠিক করা।
2. **Reliable paper checking-এর আগে:** replay-safe job/status transitions, valid marking payloads, subquestion matching, Insert context এবং একক AI configuration source ঠিক করা।
3. **Complete customer flow-এর জন্য:** parent send UI, quota enforcement, CSV validation, optional-mobile error, inactive-account enforcement এবং student linking সম্পূর্ণ করা।
4. **Acceptance sign-off-এর আগে:** system switches/notifications কার্যকর করা, documentation reconcile করা, real queue worker/OCR/AI/mail/payment sandbox integration পরীক্ষা করা।

**শেষ সিদ্ধান্ত:** Access/navigation ও Support Center এখন ভালোভাবে verified। কিন্তু পুরো Exam Marker V6-কে “সব ঠিক” বলা যাবে না। বিশেষ করে payment এবং marking/data-integrity সমস্যাগুলো release blocker।

## Verification artifacts

- Existing suite: `php vendor/phpunit/phpunit/phpunit --no-progress` → 33 tests / 592 assertions।
- Audit probes: `storage/framework/testing/audit/V6AuditProbeTest.php`। এগুলো observed behaviour-এর যাচাই, normative success tests নয়।
- JUnit outputs: `storage/framework/testing/audit/v6-audit-probes.xml`, `v6-audit-positive-probes.xml`, `v6-audit-quota-probe.xml`, `v6-audit-teacher-probe.xml`। চার run মিলিয়ে 24 probes / 120 assertions।
