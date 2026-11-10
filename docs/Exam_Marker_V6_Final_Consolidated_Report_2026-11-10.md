# Exam Marker V6 Final Consolidated Audit Report

> Historical audit snapshot. For the subsequent code changes, tests and deployment boundaries see [V6 remediation status](V6_REMEDIATION_STATUS_2026-11-10.md).

তারিখ: ১০ নভেম্বর ২০২৬  
পরিধি: চারটি audit document-এর findings, evidence, V6 product rules, ৩০টি functional area এবং developer remediation plan।

**চূড়ান্ত রায়: মূল workflow তৈরি আছে, কিন্তু পুরো V6 সঠিক বা production ready নয়।** Role/plan navigation, Support Center, সাধারণ upload এবং কয়েকটি report/access flow-এর ভালো runtime evidence আছে। Payment entitlement, marking correctness, queue integrity, quota enforcement ও কিছু customer flow-তে গুরুত্বপূর্ণ সমস্যা বাকি। Paid public launch এবং নির্ভরযোগ্য real-exam marking-এর আগে এগুলো সমাধান ও পুনরায় পরীক্ষা প্রয়োজন।

এখানে **২৭টি বিষয়ভিত্তিক finding** রাখা হয়েছে। এটি চার রিপোর্টের finding সংখ্যা যোগ করে পাওয়া মোট নয়। একই সমস্যার পুনরাবৃত্তি একত্র করা হয়েছে; সংশ্লিষ্ট sub-issues হারানো হয়নি। Application code, plan configuration বা বাস্তব business data এই consolidation-এর জন্য পরিবর্তন করা হয়নি।

## 1 Source register and evidence

| ID | সরবরাহ করা document | ভূমিকা |
|---|---|---|
| S1 | `Exam_Marker_V6_Audit_Report.md` | বিস্তারিত source-code audit; ২১টি numbered finding। ওই review environment-এ PHP ছিল না, তাই findings প্রধানত static inspection। |
| S2 | `Exam-Marker-V6-Audit-Report.docx` | সংক্ষিপ্ত repository audit; test suite-এ 592 assertions ও 33 warnings এবং failure না থাকার reported result। |
| S3 | `Exam_Marker_V6_Final_Consolidated_Audit.docx` | S1 ও S2-এর আগের consolidation। S1-এর findings ধরে test-warning/evidence topic F22 যোগ করেছে। এটি স্বাধীন তৃতীয় runtime audit নয়। |
| S4 | `V6_SYSTEM_AUDIT_2026-11-10.md` | Workspace audit; direct PHPUnit run, isolated diagnostic probes, syntax checks এবং কয়েকটি read-only runtime check-এর evidence। |

S4-এর supplied copy ও workspace copy line-ending normalization-এর পরে একই। Word documents-এর paragraphs ও tables-এর text পড়া হয়েছে; tracked changes পাওয়া যায়নি। চারটি input file অক্ষত রাখা হয়েছে।

### Evidence levels

- **R — Runtime observed:** S4-এর isolated execution/probes এবং এই consolidation-এর নির্দিষ্ট test checks-এ আচরণ পাওয়া গেছে। কোন run-এর evidence তা সংশ্লিষ্ট অংশে বলা আছে। Real payment settlement, live SMTP/SMS বা real handwriting accuracy বোঝায় না।
- **C — Code confirmed:** supplied report-এর finding বর্তমান code inspection-এর সঙ্গে মেলে; সংশ্লিষ্ট সব real-world scenario চালিয়ে দেখা হয়নি।
- **H — Historical reported result:** source report-এ run/result বর্ণিত আছে; সেই result নিজে আরেকটি স্বাধীন test run হিসেবে গণনা করা হয়নি।
- **L — Live বা policy verification বাকি:** provider, worker, deployment, performance বা product-policy decision প্রয়োজন।

### Test evidence reconciliation

| Evidence | সমন্বিত ব্যাখ্যা |
|---|---|
| S1 ও S3-তে PHP unavailable | ওই review environment-এর সীমা। অন্য environment-এ সফল PHP execution-এর সঙ্গে বিরোধ নয়। |
| S2-তে 592 assertions ও 33 warnings | Warning count এবং test count আলাদা। এটি 33টি failed test বোঝায় না। |
| S4-তে 33 regression tests ও 592 assertions pass | নির্দিষ্ট command ছিল direct PHPUnit। এই passing suite সব business rule বা production integration cover করে না। |
| S4-এর 24 diagnostic probes ও 120 assertions | 17টি probe অনাকাঙ্ক্ষিত behaviour reproduce করেছে; 7টি normal path যাচাই করেছে। এগুলোকে 24টি successful acceptance scenario বলা যাবে না। |
| এই consolidation-এর targeted Artisan check | `php artisan test --filter PlanRulesTest --compact`-এ **2 warnings, 6 assertions** পাওয়া গেছে; message-এ missing `tests/.env.testing` ছিল। Warning discrepancy তাই বাস্তব command-dependent evidence। |
| একই targeted tests direct PHPUnit-এ | `php vendor/phpunit/phpunit/phpunit --filter PlanRulesTest --no-progress` → **2 tests, 6 assertions, exit 0**; console-এ warning reported হয়নি। |
| এই consolidation-এর পূর্ণ Artisan run | `php artisan test --compact` → **33 warnings, 592 assertions, exit 0**, duration 65.81 seconds; কোনো test failure reported নয়। Compact log-এ প্রতিটি warning-এর পৃথক message নেই; targeted run-এ missing `.env.testing` message যাচাই হয়েছে। |
| Syntax evidence S4 | 107টি PHP file lint এবং 52টি Blade template parse/compile pass। Syntax pass runtime correctness নয়। |

S4-এর diagnostic XML গণনা: 17/56 + 5/47 + 1/5 + 1/12 = **24 probes / 120 assertions**। এগুলো আগের regression count-এর সঙ্গে যোগ করে “57টি feature পুরোপুরি pass” ঘোষণা করা যাবে না।

## 2 Reconciled conclusions

কয়েকটি source verdict ভিন্ন scope-এর কারণে একসঙ্গে সত্য। Final report-এ সেগুলো এভাবে নির্ধারণ করা হয়েছে:

| বিষয় | সমন্বিত verdict |
|---|---|
| Free one lifetime check | সাধারণ serial flow পাস। Paid processed-paper deletion দিয়ে quota ফিরে পাওয়ার defect প্রমাণিত। Free concurrency ও failed-attempt charging আলাদা static risk/policy question; confirmed Free deletion bypass বলা হচ্ছে না। |
| PDF report | Download/generation সফল হওয়ার evidence আছে। কিন্তু latest teacher feedback, question parts ও full text ঠিকভাবে export হচ্ছে না—তাই পুরো report feature আংশিক। |
| Parent-link expiry | Expired HTML/PDF route 410 দেয়। Expired token resend/renewal এবং successful-delivery status handling অসম্পূর্ণ—expiry protection pass মানে renewal flow pass নয়। |
| Selected recheck | Selected result row-টিই update হয় এবং existing teacher mark রাখা হয়। কিন্তু marker পুরো paper চালায়; এটি targeted question-only AI call নয়। |
| OCR ও mobile | Production OCR adapter stub এবং demo mobile logging ঘোষিত demo/adapter scope-এর অংশ। এগুলোকে live handwriting/SMS integration বলে উপস্থাপন করা যাবে না; আবার demo scope থাকাটিকেও স্বয়ংক্রিয় defect বলা হচ্ছে না। |
| Corrected paper | Original WA + HTML correction summary-র ZIP আছে। Annotated PDF requirement আলাদাভাবে নির্ধারিত না হলে annotation না থাকাকে missing promised feature গণনা করা হবে না। |
| Tenant access ও Support Center | Useful runtime coverage পাস করেছে। প্রমাণ ছাড়া নতুন cross-tenant vulnerability বা support-module failure যোগ করা হয়নি। |
| Release archive | S1/S3-এর ZIP-সংক্রান্ত packaging observation রাখা হয়েছে। এতে actual secret leak বা current public exposure প্রমাণিত হয়েছে—এমন দাবি করা হচ্ছে না। |

## 3 Consolidated findings

Severity: **Critical** = entitlement/payment compromise-এর সরাসরি পথ; **High** = marking/data/access/quota বা paid-service reliability-এর বড় ঘাটতি; **Medium** = workflow, fidelity, scope বা acceptance completion প্রয়োজন।

### C01 Direct paid plan activation without payment

**Critical · R · Sources S1 F01, S3 F01, S4 F01**

Authenticated owner `billing.change` দিয়ে existing paid `plan_id` সরাসরি save করতে পারে। Route-টির demo behaviour-এর production/environment guard নেই। S4 probe-এ Free থেকে Mode 3 হয়েছে, payment transaction count ছিল 0।

**Evidence:** `BillingController::change()`, `routes/web.php`। **করণীয়:** paid entitlement কেবল verified transaction থেকে দিন; প্রয়োজনীয় free downgrade আলাদা policy-তে রাখুন। **Acceptance:** unpaid/forged plan change-এ plan অপরিবর্তিত থাকবে।

### C02 Untrusted payment callbacks and transaction binding

**Critical · R and C · Sources S1 F02, S3 F02, S4 F02**

PayU/PhonePe/Paytm verifier success-like request status বিশ্বাস করে। Stored gateway/order/reference/amount/currency binding এবং safe replay handling অসম্পূর্ণ। S4-তে unsigned PayU callback দিয়ে unpaid PayPal transaction paid হয়েছে; কোনো gateway request যায়নি। অন্য provider/replay/amount variants সব runtime-tested নয়।

**Evidence:** `GatewayManager::{payuVerify,phonePeVerify,paytmVerify,paypalVerify}`, `PaymentController::callback()`। **করণীয়:** authenticated provider verification, strict transaction matching, atomic/idempotent state transition দিন। **Acceptance:** wrong gateway/order/amount/currency/signature reject; duplicate success no-op; late failure paid record downgrade করবে না।

### C03 Payment checkout route and advertised gateway completeness

**High · R and C · Sources S1 F19, S2 billing, S3 F19, S4 F03 and integration notes**

PayPal `payments.cancel` route ব্যবহার করে, কিন্তু route registration নেই; S4 mock checkout-এ exception reproduce হয়েছে। Paytm checkout explicit stub। bKash/card settings UI থাকলেও `GatewayManager::begin()`-এ adapter নেই।

**Evidence:** `GatewayManager::paypalBegin()`, `paytmBegin()`, gateway match এবং `routes/web.php`। **করণীয়:** cancel route ও authorization সম্পূর্ণ করুন; অসম্পূর্ণ gateway payment choice হিসেবে enable হতে দেবেন না। **Acceptance:** প্রত্যেক enabled gateway-এর begin/cancel/success/failure sandbox path সম্পন্ন হবে।

### C04 AI settings do not control the actual provider consistently

**High · R and C · Sources S1 F03, S3 F03, S4 F05**

Admin settings database-এ provider/model/key রাখে। Binding database দেখে AI engine নেয়; actual request আবার `config/ai.php`-এর provider/model/key নেয়। S4-তে admin-selected Gemini থাকা সত্ত্বেও configured OpenAI/model ব্যবহৃত হয়েছে। Azure advertised হলেও dispatch case অনুপস্থিত।

**Evidence:** `AdminAISettingController`, `AppServiceProvider`, `AIPaperMarker::callAI()`। **করণীয়:** একটি resolved configuration object ব্যবহার করুন। **Acceptance:** admin-selected provider/model/key mock request-এ হুবহু কার্যকর হবে; unsupported choice reject হবে।

### C05 Adaptive marking does not preserve authoritative question maxima

**High for reliable exam marking · C · Sources S1 F04, S3 F04, S4 F06 context**

Detected question-এর marks পরে সমানভাবে ভাগ করা হয়। Printed Q1 [2], Q2 [8] হলে total 10 থেকে 5/5 হওয়ার code path আছে। Parsing ব্যর্থ হলে synthetic question rows তৈরি হয়। Keyword overlap/length ও broad synonym mapping examiner-grade equivalence প্রমাণ করে না।

**Evidence:** `AdaptivePaperMarker::{parseQuestions,score}`। **করণীয়:** authoritative question structure/maxima extract ও confirm করুন; parse failure provisional review/error করুন। **Acceptance:** 2/8 maxima অপরিবর্তিত থাকবে; অচেনা structure-এ কৃত্রিম final question তৈরি হবে না। Demo heuristic-কে provisional হিসেবে রাখুন।

### C06 Subquestion IDs and answer matching are inconsistent

**High · R and C · Sources S1 F05, S3 F05, S4 F06**

QP-তে `1(a)` থেকে `1a` হয়, কিন্তু WA splitter canonical normalization করে না। S4-এর একই scheme/answer থাকা `1(a)` ও `1(b)` দুই row-তেই 0 পেয়েছে। Continuation/nested parts ও distinct `question_part` representation-ও সীমিত।

**Evidence:** `AdaptivePaperMarker::{parseQuestions,splitByQuestions}`, `ProcessAssessment`। **করণীয়:** QP/MS/WA-এর জন্য একই canonical question ID দিন। **Acceptance:** `1(a)`, `1 a`, `1a`, `(a)` continuation ও nested parts-এর fixtures-এ একই question সঠিকভাবে match হবে।

### C07 Insert context and optional mark scheme policy are incomplete

**High · R and C · Sources S1 F06 and F11, S2 inserts, S3 F06 and F11, S4 F08**

Reader insert text extract করে; adaptive scoring-এ এটি শুধু summary flag এবং external AI prompt-এ অনুপস্থিত। S4 unique insert marker outgoing prompt-এ পায়নি। MS upload optional হলেও external prompt বলে supplied MS-ই শুধু ব্যবহার করতে; no-MS fallback পরিষ্কার নয়। No-MS live LLM ফল independently tested নয়।

**Evidence:** `LocalDocumentReader::read()`, `AdaptivePaperMarker::mark()`, `AIPaperMarker::buildPrompt()`। **করণীয়:** real marking context-এ inserts দিন এবং no-MS provisional policy নির্ধারণ করুন। **Acceptance:** source booklet বদলালে relevant marking context বদলাবে; no-MS result স্পষ্টভাবে provisional হবে।

### C08 AI result validation and rubric persistence are insufficient

**High · R and C · Sources S1 F11, S3 F11, S4 F07**

Questions array থাকলেই যথেষ্ট ধরে নেওয়া হয়। S4 empty questions-কে successful `review_required`, 20/10 marks এবং 200% save হওয়া reproduce করেছে। Uniqueness, bounds, totals ও confidence consistency validation প্রয়োজন। Returned `writing_rubric` field-ও persist হয়নি।

**Evidence:** `AIPaperMarker::parseResponse()`, `ProcessAssessment::handle()`, `QuestionResult`। **করণীয়:** schema/domain validation, authoritative maxima, server-calculated total/percentage/grade এবং rubric persistence দিন। **Acceptance:** empty/duplicate/negative/over-max/inconsistent payload reject; valid rubric save/display হবে।

### C09 Queue claims recovery and processing progress are unreliable

**High · R and C · Sources S1 F10, S2 processing, S3 F10, S4 F04**

Duplicate guard `processing/completed` দেখে; success হয় `review_required`। S4-তে finalised teacher mark 9 মুছে AI mark 2 হয়েছে। Atomic claim/attempt identity/recovery নেই। Default queue retry 90 seconds, job timeout 180 seconds। View random timer-এ progress ও redirect দেখায়; real status poll করে না। Crash/concurrency variants live worker দিয়ে পরীক্ষা হয়নি।

**Evidence:** `ProcessAssessment`, `AssessmentController::start()`, `config/queue.php`, processing view। **করণীয়:** replay-safe processing attempt, stale/finalised guards, timeout recovery, compatible retry configuration ও real status UI দিন। **Acceptance:** duplicate/stale job approved result বদলাবে না; crash/retry recover হবে; UI actual state দেখাবে।

### C10 Paid paper quota accounting and concurrent reservations are incomplete

**High · R and C · Sources S1 F08, S3 F08, S4 F10**

Paid quota surviving assessment count করে। S4-তে limit 1-এ blocked upload processed paper delete করার পরে সফল হয়েছে, consumed usage record থাকা সত্ত্বেও। Check/create atomic নয়; concurrency risk static finding। Free normal serial lifetime rule পাস; Free parallel behaviour ও failed-attempt charging policy unverified।

**Evidence:** `AssessmentController::{enforceCheckLimit,store,destroy}`, `BulkAssessmentController`, `BillingController`। **করণীয়:** durable usage/reservation ledger ও atomic owner-level quota ব্যবহার করুন। **Acceptance:** processed deletion quota ফেরত দেবে না; concurrent requests cap অতিক্রম করবে না; failure/refund policy লিখিত ও tested হবে।

### C11 Bulk uploads bypass student limits

**High · R · Sources S1 F07, S3 F07, S4 F10**

Bulk new-student path `firstOrCreate()` করে, student cap দেখে না। S4-তে configured limit 1 হলেও দ্বিতীয় student তৈরি হয়েছে।

**Evidence:** `BulkAssessmentController::store()` বনাম `StudentController::checkLimit()`। **করণীয়:** unique new students আগে গণনা করে quota reserve করুন। **Acceptance:** cap পূর্ণ হলে single, CSV ও bulk—সব path একই নিয়মে reject করবে।

### C12 Failed bulk batches retain partial writes

**Medium · R · Sources S1 F16, S3 F16, S4 F11**

S4 batch-এর দ্বিতীয় unauthorized row-তে 403 হলেও প্রথম assessment তৈরি ছিল। Batch-wide validation, transaction ও file rollback নেই।

**Evidence:** `BulkAssessmentController::store()`। **করণীয়:** সব rows prevalidate, atomic DB write এবং failure cleanup দিন। **Acceptance:** later-row failure-এ partial paper/student/files বা duplicate retry residue থাকবে না।

### C13 CSV parsing and row validation fail on common malformed input

**Medium · R and C · Sources S1 F16, S2 CSV, S3 F16, S4 F12**

Extra-cell row-তে `array_combine()` HTTP 500 দিয়েছে। `file()`-ভিত্তিক parsing quoted multiline CSV-এর জন্যও উপযুক্ত নয়; সেটি source-level concern। CSV fields-এর row/type/email validation সীমিত। Normal CSV এবং Mode 2 parent-column ignoring পাস করেছে।

**Evidence:** `StudentController::import()`। **করণীয়:** `fgetcsv()`/stream parsing, row-numbered validation ও clear errors দিন। **Acceptance:** uneven/BOM/quoted/multiline/invalid field fixtures 500 ছাড়াই handle হবে।

### C14 Inactive accounts and existing sessions lack complete enforcement

**High · R and C · Sources S1 F09, S3 F09, S4 F14**

Inactive teacher login ও workspace access S4-তে reproduce হয়েছে; login শুধু suspended আটকায়। Already authenticated suspended account এবং suspended-owner staff access-এর per-request enforcement/session revocation source-এ পাওয়া যায়নি; এই variants নতুন runtime test নয়।

**Evidence:** `AuthController::login()`, Team/Admin status updates, custom middleware registration। **করণীয়:** active-account/owner policy এবং session invalidation implement করুন। **Acceptance:** inactive/suspended account ও প্রয়োজন অনুযায়ী owner-dependent staff access নতুন ও পুরোনো session উভয়েই বন্ধ হবে।

### C15 Email verification and authentication rate limiting are incomplete

**Medium · R and C · Sources S1 F18, S3 F18, S4 F16**

User `MustVerifyEmail` contract implement করে না; verified routes unverified user-কে ঢুকতে দেয়। Missing verification notice view S4-তে 500 দিয়েছে। Login/register-এ dedicated throttling দেখা যায়নি; verification notification route-এ throttle আছে—তাই “কোথাও throttle নেই” বলা ঠিক নয়।

**Evidence:** `User`, `AuthController`, verification/auth routes। **করণীয়:** intended verification policy সম্পূর্ণ করুন বা dead flow সরান; appropriate login/register rate limit দিন। **Acceptance:** verified/unverified policy, resend, signed verification, reset এবং abuse limits tested হবে।

### C16 Optional mobile input conflicts with user storage constraints

**Medium · R and C · Source S4 F13**

Teacher form/controller mobile optional রাখে; users table non-nullable। Mobile বাদ দিলে S4-তে 500 হয়েছে; mobile দিলে teacher creation/login/allocation সফল। Admin user creation-এ একই nullable pattern source-confirmed।

**Evidence:** `TeamController`, team view, users migration, `AdminUserController::store()`। **করণীয়:** product policy অনুযায়ী form/validation/schema/uniqueness এক করুন। **Acceptance:** optional empty mobile কাজ করবে অথবা request পরিষ্কার validation error দেবে; কোনো 500 নয়।

### C17 Teacher allocation cannot complete every intended workflow

**Medium · C · Sources S1 F17, S3 F17, S4 flow notes**

Single create-এ coaching admin allocation আছে। Coaching bulk upload এবং পরে assign/reassign action নেই। Staff নিজের bulk upload করলে self-assignment হয়; সব bulk paper unassigned থাকে—এমন নয়। Dropdown active-only হলেও server query crafted inactive-teacher assignment আটকায় না। Normal supplied-mobile account/login/single allocation S4-তে পাস করেছে।

**Evidence:** assessment resource exclusions, create/store validation ও bulk controller। **করণীয়:** owner-authorized active-teacher assign/reassign ও bulk allocation দিন। **Acceptance:** only same-owner active teacher; staff/foreign/inactive allocation denied; existing/bulk paper allocate করা যাবে।

### C18 New student records cannot be linked through a complete onboarding flow

**Medium · C · Sources S1 F21, S3 F21, S4 flow notes**

`login_user_id` relation এবং seeded demo link আছে। New customer student record-কে real login-এর সঙ্গে link/invite করার controller/form নেই; portal শুধু existing link পড়ে।

**Evidence:** `Student`, `PortalController`, `StudentController`, seeder। **করণীয়:** authorized account-link/invitation workflow দিন। **Acceptance:** নতুন student login শুধু নিজের published linked results দেখবে; cross-user/tenant linking reject হবে।

### C19 Parent delivery endpoints have no normal UI entry

**High · C with backend R evidence · Source S4 F09**

Email/mobile endpoint ও mocked delivery path আছে, কিন্তু Blade views-এ send route ব্যবহার করা button/form নেই। Communications page শুধু log। Landing-page marketing text delivery control নয়।

**Evidence:** parent communication routes/controller; result ও communication views। **করণীয়:** authorized finalised-result send controls, missing-contact/limit/error feedback যোগ করুন। **Acceptance:** Mode 3 user UI দিয়েই email/demo link পাঠাতে পারবে; Mode 2/student তা পারবে না।

### C20 Parent link renewal and delivery status are incomplete

**Medium · C · Sources S1 F20, S3 F20; complementary S4 expiry test**

Finalise আগে token তৈরি করে। `ensureLink()` শুধু token না থাকলে renew/status update করে; expired existing token resend হতে পারে, successful delivery-ও সাধারণত `finalised` status-এ থাকে। Public expiry 410 protection কাজ করে—এখানে expiry bypass দাবি করা হচ্ছে না।

**Evidence:** `ResultController::finalise()`, `ParentCommunicationController::ensureLink()`, `PublicReportController`। **করণীয়:** expiry-aware rotate/renew ও delivery-success state আলাদা করুন। **Acceptance:** resend fresh valid link দেবে; old token/reopen policy সঠিক হবে; delivered status/history নির্ভুল হবে।

### C21 Downloaded reports lose important result detail

**Medium · C · Sources S1 F13, S3 F13; complementary S4 generation tests**

PDF teacher marks নেয়, কিন্তু `teacher_comment` ও `question_part` নেয় না; HTML report সেগুলো দেখায়। Writer প্রতি line 110 characters কেটে দেয়; wrapping নেই। Plain Helvetica/no embedded Unicode font-এর জন্য Bengali fidelity risk আছে, এই merge-এ visual PDF test হয়নি। Export-এর percentage/grade flags-ও consistently প্রয়োগ করা হয় না।

**Evidence:** `PdfReportService::make()/simplePdf()`, report HTML view। **করণীয়:** shared report presenter, teacher-feedback precedence, canonical labels, full text wrapping, Unicode font ও feature gating দিন। **Acceptance:** HTML/PDF-তে latest marks/comment/parts সমান থাকবে; long/Bengali content হারাবে না।

### C22 Grade labels change during edit or finalisation

**Medium · C · Sources S1 F14, S3 F14**

Adaptive marker 90% কে `9`, 80% কে `8` দেয়; result recalculation 90% কে `A+`, 80% কে `A` দেয়। Marks অপরিবর্তিত রেখেও finalise-এ grade বদলাতে পারে।

**Evidence:** `AdaptivePaperMarker::grade()`, `ResultController::recalculate()`। **করণীয়:** assessment/board-specific একক grading policy ব্যবহার করুন। **Acceptance:** একই score/scale-এ process, edit, recheck, finalise এবং report একই grade দেবে।

### C23 Selected recheck is not a targeted question only marking call

**Medium scope and efficiency gap · C · Sources S1 F15, S3 F15, S4 area 9**

Controller পুরো assessment marker চালিয়ে শুধু matched selected row update করে। Selected-update behaviour আছে; targeted-only AI processing নেই। তাই whole-paper cost/latency হতে পারে।

**Evidence:** `ResultController::recheckQuestion()`, `PaperMarker` contract। **করণীয়:** প্রয়োজন হলে question-scoped marking contract দিন অথবা UI/product description পরিষ্কার করুন। **Acceptance:** targeted recheck-এর payload/cost scope সীমিত থাকবে এবং unrelated results/teacher marks বদলাবে না।

### C24 Monthly subscription entitlement lifecycle is missing

**High if monthly subscription is promised · C and L · Sources S1 F12, S3 F12**

Pricing-এ monthly period আছে; payment success শুধু `plan_id` update করে। Subscription start/end/expiry/renewal/cancellation entitlement enforcement নেই। Quota calendar month boundary অনুসরণ করে, purchase-date cycle নয়। এটি expired-subscription live exploit test নয়; policy/implementation gap।

**Evidence:** `PaymentController::callback()`, `User::effectivePlan()`, plan/schema, console routes ও quota query। **করণীয়:** subscription lifecycle দিন, অথবা one-time entitlement/calendar-month policy pricing-এ স্পষ্ট করুন। **Acceptance:** agreed expiry/renewal/cancellation অনুযায়ী access ও quota পরিবর্তিত হবে।

### C25 Global switches and some feature flags do not control behaviour

**Medium · R and C · Source S4 F15 and flag notes**

S4-তে maintenance enabled থাকলেও home 200; global AI disabled থাকলেও AIPaperMarker resolve হয়েছে। Email/SMS switches এবং কিছু portal/branding flags-এর পূর্ণ consumer পাওয়া যায়নি। Basic role-driven student portal কাজ করা এবং unused checkbox থাকা আলাদা বিষয়।

**Evidence:** `SystemConfigurationController`, `AppServiceProvider`, `SystemSetting`, plan flags ও consumers। **করণীয়:** retained setting কার্যকর করুন; obsolete/role-driven flags সরান বা ব্যাখ্যা দিন। **Acceptance:** প্রত্যেক displayed control-এর documented observable effect থাকবে।

### C26 Non support lifecycle notification preferences are not wired

**Medium · C · Sources S1 F21, S3 F21, S4 F15**

Processing/report/billing/parent-delivery preferences save হয়, কিন্তু promised lifecycle events-এর notification dispatch পাওয়া যায়নি। Support notifications, recipient privacy ও per-ticket read tracking আলাদাভাবে tested এবং কাজ করে।

**Evidence:** `SettingsController::updateNotifications()`, `ProcessAssessment`, report/billing actions, `SupportTicketNotifier`। **করণীয়:** required lifecycle events ও preference enforcement যোগ করুন। **Acceptance:** enabled event ঠিক recipient-কে যায়; disabled preference মানে; Support Center regression অক্ষত থাকে।

### C27 Acceptance evidence documentation and release hygiene need completion

**Medium assurance topic · R C H and L · Sources S1 notes, S2 acceptance/tests, S3 F22 and notes, S4 verification/deployment notes**

Test warnings সত্যিকারের warning হিসেবে রাখতে হবে, failure হিসেবে নয়। README এখনও V5 heading; V6 checklist unchecked। Passing regression/probe সংখ্যা production acceptance-এর বিকল্প নয়।

S1/S3 নির্দিষ্ট audited ZIP-এ `.env`, `.git`, SQLite, logs/compiled views ও vendor থাকার কথা জানিয়েছে; archive এখানে পুনরায় supplied হয়নি। এটি reported packaging exposure risk, confirmed secret leak নয়। Seeder-এর known demo accounts/password reset behaviour production seeding থেকে আলাদা করা দরকার। Install scripts-এর `migrate:fresh --seed` fresh install-এর জন্য; existing deployment-এর upgrade procedure নয়।

**করণীয়:** safe warning-free test fixture, exact command/runtime/logs/fingerprint, updated acceptance records, sanitized release package এবং incremental upgrade/preflight procedure দিন। **Acceptance:** real provider/worker/hosting checks-এর evidence ও approved release checklist সংরক্ষিত হবে।

## 4 Important product rules

| Product rule | Final verdict |
|---|---|
| QP ও WA compulsory এবং MS optional | Upload validation ও normal no-MS upload tested। External AI no-MS policy C07-এ অসম্পূর্ণ। |
| Exam format PDF DOC DOCX only | Upload allow-list আছে, PNG reject হয়েছে; DOCX local extraction tested। DOC/scanned extraction environment-dependent। |
| Free এক lifetime check | Normal serial default flow পাস। Parallel reservation/failure charging C10-এ unverified risk/policy question। |
| Mode 1 no bulk WA | Default/current checked flags এবং access tests-এ ঠিক। |
| Mode 2 analysis edit recheck bulk import report | Core paths আছে; marking, PDF fidelity, bulk quota/atomicity এবং CSV issues-এর জন্য সম্পূর্ণ সঠিক নয়। |
| Mode 2 no parent details communication team corrected paper | Default/current checked flags ও forbidden-route tests-এ ঠিক। CSV template parent headings থাকা parent-data permission bypass নয়। |
| Mode 3 parent communication corrected package teachers allocation | Package ও normal teacher creation/login/allocation চলে। Parent send UI, renewals এবং some team/bulk flows আংশিক। |
| No Mode 3 Plus or multiple branches | চারটি default mode পাওয়া গেছে; branch feature পাওয়া যায়নি। |

S1/S3-এর bundled snapshot-এ limits ছিল: Free 1 lifetime; Mode 1 10 papers/5 students; Mode 2 50 papers/25 students; Mode 3 200 papers/100 students/10 teachers। এগুলো configured snapshot values; সব entry point-এ cap enforcement-এর প্রমাণ নয়।

## 5 Functional area matrix

**যাচাইকৃত সীমায় ঠিক** full security/production certification নয়। **আংশিক** মানে implementation আছে কিন্তু listed gap বা গুরুত্বপূর্ণ verification বাকি।

| # | Functional area | Final status | Evidence and remaining scope |
|---|---|---|---|
| 1 | Public homepage pricing registration login | Normal path যাচাইকৃত | Public pages/Free registration/password hashing পাস; auth edge C14/C15। |
| 2 | Role-aware authentication dashboards | আংশিক | Role redirects ভালো; inactive/session/verification gap C14/C15। |
| 3 | Four database plan modes | যাচাইকৃত সীমায় ঠিক | Default/checked flags এবং owner inheritance মেলে; billing/quota loopholes আলাদা। |
| 4 | Sidebar views | যাচাইকৃত সীমায় ঠিক | Seven account types-এর visible links tested; visual browser acceptance সম্পূর্ণ নয়। |
| 5 | QP MS same step and WA final step | যাচাইকৃত সীমায় ঠিক | Wizard structure ও required/optional validation মেলে। |
| 6 | Queue and synchronous processing | আংশিক | Local DOCX sync/async dispatch আছে; C09 recovery/progress। |
| 7 | OCR adapter contract and demo reader | Demo scope-এ আছে | Production cloud OCR/live handwriting certified নয়। |
| 8 | Question-wise AI marks and confidence | Correctness ব্যর্থ | Canonical IDs, maxima, validation, context C04–C08। |
| 9 | Teacher edit selected recheck final approval | আংশিক | Normal controller flow tested; stale job/grade/recheck scope C09/C22/C23। |
| 10 | Plan-aware report downloads | আংশিক | Permission/download পাস; report fidelity C21। |
| 11 | Mode 2 versus Mode 3 restrictions | যাচাইকৃত সীমায় ঠিক | Default gates ও forbidden routes tested। |
| 12 | Student records CSV import | আংশিক | Normal CSV works; C11/C13/C18। |
| 13 | Bulk WA Mode 2 and Mode 3 | আংশিক | Basic path আছে; C10–C12/C17। |
| 14 | Analytics | যাচাইকৃত সীমায় ঠিক | Scope/visibility/formulas কাজ করে; source marks accuracy certified নয়। |
| 15 | Mode 3 parent details communications | আংশিক | Contacts/gates আছে; send UI C19। |
| 16 | Email and demo mobile delivery | আংশিক | Mock email/PDF/demo log পাস; C19/C20 এবং live delivery বাকি। |
| 17 | Expiring parent report links | আংশিক | Expiry 410 protection পাস; renewal/status C20। |
| 18 | Teacher logins and paper allocation | আংশিক | Valid-mobile normal flow পাস; C14/C16/C17। |
| 19 | Billing and usage limits | ব্যর্থ release blocker | C01–C03/C10/C11/C24। |
| 20 | Settings notifications support tickets | আংশিক | Support flow tested; C25/C26। |
| 21 | Platform admin plans and users | আংশিক | Pages/plan assignment tested; account-state/mobile edge C14/C16। |
| 22 | Database feature flags and limits | আংশিক | Gates কাজ করে; quota/unused controls C10/C11/C25। |
| 23 | Usage and audit tables | আছে ও records tested | Consumed quota ledger হিসেবে সঠিক ব্যবহার C10-এ বাকি। |
| 24 | Processing status and timestamps | আংশিক | Timestamps আছে; stale guard/fake progress/delivery status C09/C20। |
| 25 | Tenant access and private files | যাচাইকৃত সীমায় ঠিক | Tested own/assigned/published/private-file/support scopes; full pentest নয়। |
| 26 | Laravel background jobs | আংশিক | Architecture আছে; C09 এবং live worker verification বাকি। |
| 27 | Demo marker and production adapter points | Demo scope-এ আছে | Adapter boundary আছে; real-exam/provider quality sign-off নয়। |
| 28 | Role-specific end-to-end flows | আংশিক | Parent/student/team/paid lifecycle gaps। |
| 29 | Functional acceptance documentation | আংশিক | Checklist exists; completed evidence/documentation C27। |
| 30 | Deployment checklist | আংশিক | Guidance exists; integration/preflight/package C27 ও release blockers। |

## 6 Integration and deployment boundaries

- Real AI/handwriting accuracy, official examiner calibration, live payment settlement, SMTP delivery, real SMS/WhatsApp, hosting/load ও live worker crash recovery যাচাই বাকি।
- S4-এর workstation PATH lookup-এ `pdftoppm` ছিল; `tesseract` ও `antiword` পাওয়া যায়নি। এটি সব server-এর অবস্থা নয়। Local reader hardcoded shell tools ও DOC helper-এ Unix `/dev/null` ব্যবহার করে; Windows preflight দরকার।
- DOCX extraction ও corrected ZIP-এর জন্য PHP `ZipArchive` extension দরকার; OCR tools-এর সঙ্গে এটিও deployment preflight-এ যাচাই করতে হবে।
- Production OCR adapter-এর explicit exception/extension point এবং demo mobile log declared scope-এর অংশ। Live-ready হিসেবে advertise করার আগে implementation ও evidence চাই।
- Corrected ZIP original answer ও HTML correction summary দেয়; annotated answer PDF আলাদা scope decision।
- PostgreSQL docs-এ suggested হলেও current database config-এ SQLite/MySQL connection আছে; PostgreSQL readiness আলাদাভাবে প্রস্তুত করতে হবে।
- S4-এর configured database `CAST(... AS INTEGER)` read-only query গ্রহণ করেছে; speculative CAST incompatibility confirmed defect হিসেবে নেওয়া হয়নি।

## 7 Developer priorities and acceptance gates

| পর্যায় | বিষয় | Done হওয়ার শর্ত |
|---|---|---|
| P0 Paid access protection | C01/C02/C03; monthly promise থাকলে C24 | Unpaid/spoofed/mismatched/replayed activation বন্ধ; enabled gateway sandbox/cancel path সফল; entitlement policy tested। |
| P1 Marking and data integrity | C04–C09/C21–C23 | Exact question maxima/IDs, inserts/no-MS policy, strict payload, durable teacher edits, stable grade ও complete report। |
| P2 Quota identity and batch safety | C10–C18 | Atomic/durable caps, all-or-nothing bulk, validated CSV/mobile, active identities, authorized linking/allocation। |
| P3 Parent and notification workflow | C19/C20/C25/C26 | User-facing authorized send, correct renewals/state, real control effects ও preference-aware events। |
| P4 Acceptance and release preparation | C27 এবং live boundaries | Reproducible warning-reviewed tests, real provider/worker evidence, fresh-install/upgrade separation, sanitized release ও signed acceptance checklist। |

Minimum acceptance checks:

1. Missing QP/WA reject, optional MS accept, supported/unsupported formats, max-size এবং single/bulk upload retry।
2. Q1(a)[2], Q1(b)[3], Q2[5] fixture; labels/parts/marks/parent totals এবং source booklet influence।
3. Invalid JSON/empty/duplicate/negative/over-max/inconsistent marks reject; rubric persist; no-MS provisional result।
4. Edit/recheck/finalise-এর পরে marks, grade, teacher feedback ও HTML/PDF consistency; long/Bengali report fixture।
5. Duplicate/stale jobs, concurrent start, worker timeout/kill/retry এবং finalised-result protection।
6. Free serial/parallel attempts, paid processed deletion, concurrent reservations, batch student cap ও failed-attempt policy।
7. Inactive/suspended/new/existing session, suspended owner policy, email verification/reset/rate limits।
8. New student login linking, same-owner active teacher single/bulk assignment/reassignment, cross-tenant denials।
9. Parent UI send, missing contacts, usage limits, mocked plus real sandbox mail/PDF/link, expiry/renewal/reopen।
10. Provider signature/order/gateway/reference/amount/currency, duplicate callbacks, cancellation ও monthly expiry/renewal policy।
11. Existing Support Center privacy/thread/status/notifications regression অক্ষত রাখা।
12. Exact command/runtime/source fingerprint, counts/warnings/exit status এবং manual acceptance evidence সংরক্ষণ।

## 8 Traceability and duplicate mapping

`—` মানে source-এ আলাদা explicit finding ছিল না; generic discussion-কে নতুন independent evidence হিসেবে গণনা করা হয়নি।

| Final ID | S1 | S2 contribution | S3 | S4 |
|---|---|---|---|---|
| C01 | F01 | — | F01 | F01 |
| C02 | F02 | Billing verification | F02 | F02 |
| C03 | F19 | PayPal cancel and Paytm | F19 | F03 and integration notes |
| C04 | F03 | — | F03 | F05 |
| C05 | F04 | Heuristic scope | F04 | F06 context |
| C06 | F05 | — | F05 | F06 |
| C07 | F06/F11 | Insert omission | F06/F11 | F08 |
| C08 | F11 | — | F11 | F07 including rubric |
| C09 | F10 | Processing coverage | F10 | F04 including progress |
| C10 | F08 | — | F08 | F10 paid deletion |
| C11 | F07 | — | F07 | F10 student cap |
| C12 | F16 bulk | — | F16 bulk | F11 |
| C13 | F16 CSV | CSV edge case | F16 CSV | F12 |
| C14 | F09 | — | F09 | F14 |
| C15 | F18 | — | F18 | F16 |
| C16 | — | — | — | F13 |
| C17 | F17 | — | F17 | Allocation notes |
| C18 | F21 linking | — | F21 linking | Linking notes |
| C19 | — | Backend communication scope | — | F09 |
| C20 | F20 | — | F20 | Expiry pass is complementary |
| C21 | F13 | — | F13 | Download pass is complementary |
| C22 | F14 | — | F14 | — |
| C23 | F15 | — | F15 | Area 9 scope note |
| C24 | F12 | — | F12 | — |
| C25 | — | — | — | F15 and dormant flags |
| C26 | F21 events | — | F21 events | F15 |
| C27 | Acceptance and package notes | Warnings and acceptance | F22 and release notes | Verification/docs/deployment |

## 9 Source fingerprints and evidence locations

Input files-এর SHA256:

- S1: `234ff1e317fa7f3e3734215f2059fb27f4949a5497a9292bfd0bf19b7fcc03f3`
- S2: `bc37ddd222171b1ea4f250e9dde1c2572a886140c9e51561770642791fe2f4eb`
- S3: `af4b7fb42b594e40d825da3a87252334cb21ac94328a183fe380afe15b5f7414`
- S4: `495f9bbd725be7160e83ad22b4aadde7650f833f0ef9d52cd5851e5eae654482`

S1/S3-এ reported audited archive fingerprint ছিল `cbec558e1962b69ddc9b97bbbaad34024cc925bb2c62cf06dddb7bbd6c464022`। এটি উপরের চার report file-এর checksum নয়; underlying ZIP এই চার input-এর অন্তর্ভুক্ত নয়।

Local evidence:

- Input inventory/extracted text: `storage/framework/testing/audit_merge/sources.json` এবং `S1.txt`–`S4.txt`।
- Current Artisan run output: `storage/framework/testing/audit_merge/artisan-test-run.txt`।
- Paired direct PHPUnit output: `storage/framework/testing/audit_merge/direct-phpunit-check.txt`।
- S4 diagnostic code: `storage/framework/testing/audit/V6AuditProbeTest.php`।
- S4 XML: `v6-audit-probes.xml`, `v6-audit-positive-probes.xml`, `v6-audit-quota-probe.xml`, `v6-audit-teacher-probe.xml`—সব `storage/framework/testing/audit/`-এ।

**Final sign-off: Not approved for paid public launch or trusted real-exam marking.** Demo/workflow foundation review করা যায়। P0/P1 blockers, agreed policy decisions ও acceptance evidence সম্পন্ন হলে এই একই finding IDs ধরে পুনরায় sign-off করা উচিত।
