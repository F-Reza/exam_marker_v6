# Exam Marker V6 remediation status - 10 November 2026

রিপোর্টের C01-C27 অনুযায়ী application changes যুক্ত করা হয়েছে। এটি আগের audit-এর পরিবর্তে নতুন production certification নয়। নিচের table code changes ও available evidence আলাদা করে দেখায়। Real merchant settlement, provider account credit, SMTP delivery, handwriting accuracy এবং multi-node load acceptance external verification হিসেবে বাকি।

## Reported upload and stuck-queue error

- Single/bulk QP, optional MS, inserts and WA now use the same extension-and-content validation. Real PDF/Word containers reported as generic binary/ZIP are accepted; renamed arbitrary archives are rejected.
- Random private filenames retain the validated extension. Older Word uploads stored as `.zip`/`.bin` are read by content.
- DOCX reader preserves automatic numbering, paragraphs, tables, breaks and Unicode text. Word 97-2003 DOC uses bounded native PHP compound-file/piece-table extraction; optional converters support additional legacy variants. Password-protected/image-only/unreadable documents produce a document-specific error.
- The screenshot's stuck `Queued` state was caused by a processing-attempt schema mismatch. The missing migration and subsequent account/invitation migrations have been applied locally, without a database reset. Paper #2 was recovered to a retryable state.
- Local extraction from paper #2's four uploaded roles succeeded. A subsequent live retry reached OpenAI and returned HTTP 429. This is now reported as provider credit/quota/rate-limit guidance, with same-paper retry. **OpenAI remains selected at the user's request; API billing/quota must be restored in the provider account.** No provider switch was performed.
- Failed/uploaded papers have replacement-document controls, retaining the same quota reservation. Polling uses real server status and never redirects a failed paper to a fabricated success result.

## C01-C27 implementation map

| ID | Change | Evidence / practical boundary |
|---|---|---|
| C01 | Direct paid-plan changes require checkout; Free changes remain owner-authorized. | Regression verifies unpaid entitlement unchanged. |
| C02 | Gateway/order/amount/currency binding, PayU reverse signature plus server verification, PayPal authenticated order/capture verification, locked idempotent activation. | Mocked verified payments, forged/cross-gateway, wrong amount, replay and late cancellation tested. Real merchant settlement remains external. |
| C03 | Authorized cancel route added; checkout exposes supported adapters only. PayU supports INR; PayPal is hidden for INR. Unsupported PhonePe/Paytm/bKash/card enable requests fail closed. | Checkout/callback application paths tested; merchant sandbox acceptance required before public enablement. |
| C04 | Database provider/model/key drive actual requests through one configuration resolver. Azure deployment URL/model are aligned; HTTP failures and empty responses have explicit errors. | Gemini configuration/context and OpenAI targeted/error paths mocked. Other real provider accounts require integration checks. |
| C05 | Printed question maxima are preserved and reconciled with assessment total; unreadable/conflicting structure fails clearly. Equal/synthetic question generation removed. | Unequal 2/8 maxima and unknown-structure tests. Local keyword comparison is explicitly provisional. |
| C06 | Shared canonical identifiers parse QP/MS/WA, parenthesized/spaced/combined parts and continuation/nested labels. | Subquestion and Word automatic-numbering regression fixtures. |
| C07 | Inserts are passed into real marking context; optional MS uses an explicit provisional policy. | Outgoing unique source marker asserted; local scoring uses source context. |
| C08 | Nonempty/unique/bounded questions, rubric and total consistency checked; totals/grade calculated server-side; writing rubric/type persisted. | Invalid payload rejection, rubric persistence, server percentage and replay tests. |
| C09 | Dispatch and execution identities, atomic claim, overlap protection, protected terminal/finalised results, compatible retry interval, failed callback, stale recovery command and real UI polling. | Database worker executed in isolated tests; stale attempt invalidation and finalised-result replay tested. Real crash/load/multiple-worker acceptance still required. |
| C10 | Durable upload reservation accounting survives deletion; owner locks serialize quota checks; subscription-anniversary cycle used. Same-paper retry/replacement is free of new reservations. | Delete/reupload and replacement tests. MySQL locking is implemented; concurrent production load is not certified by serial SQLite tests. |
| C11 | Unique new bulk students are counted before creation; single, CSV and bulk share owner-locked cap enforcement. | Over-cap rejection and no-partial-write regression. |
| C12 | Every bulk row is prevalidated; database writes are atomic and newly stored files are cleaned on failure. Checking occurs after the valid upload batch commits. | Later foreign row leaves no papers/students/files. Individual processing failure retains the valid uploaded batch. |
| C13 | Stream CSV parser handles BOM, quoted multiline fields, uneven rows and row-numbered field errors; imports are atomic. | Malformed and multiline CSV fixtures. |
| C14 | Inactive/suspended accounts and inactive-owner staff are rejected at login and on existing session requests. | Inactive login and suspended-owner session tests. |
| C15 | Real email verification contract/notice, signed verification/resend flows, per-email/IP login throttle and registration/reset throttles. | Verification notice/gating and abuse-limit regression. Existing unverified non-admin users must verify; real mail deliverability remains external. |
| C16 | Nullable mobile schema matches optional team/admin/profile inputs, with uniqueness validation. | Teacher creation without mobile tested; migration applied. |
| C17 | Active same-owner allocation enforced in single/bulk upload and new assign/reassign action; staff self-allocation retained. | Owner allocation and inactive-teacher rejection tested. |
| C18 | Expiring hashed student invitations require the invited student account to accept before linking; conflicting links/roles are rejected. | Wrong-account rejection and accepted linking tested; existing portal visibility regression retained. |
| C19 | Approved-result UI includes authorized parent email/demo mobile actions and contact guidance/history links. | Mode 3 delivery path and role/plan regression. |
| C20 | Expired tokens rotate on resend; only successful delivery marks the result reported; attempts/history remain accurate. | Expired link rotation, old-token rejection, successful status and disabled channel tests. |
| C21 | Shared presenter/print content, latest teacher feedback/marks, canonical parts, full wrapping and embedded Unicode/OpenType fonts replace truncated Helvetica output. | Long feedback endpoint preserved, teacher precedence asserted; Bengali PDF rendered and visually inspected. |
| C22 | One grading service is used by processing, edits, recheck, approval and reports. | Edit/finalise grade consistency tested. Application grade boundaries remain provisional until board/session confirmation. |
| C23 | Secondary question-scoped marker contract sends only the selected question/answer to external AI; unrelated results/teacher marks remain untouched. | Whole-paper method is forbidden in recheck regression; outgoing payload excludes unrelated answer. |
| C24 | Monthly term start/end, expiry-aware owner/staff entitlements, manual renewal/cancellation, anniversary quota and explicit admin grants. | Expiry and activation tests; payment replay does not extend term. No automatic recurring charging is advertised. |
| C25 | Maintenance, global checking, confidence threshold and parent email/mobile controls have effects. Obsolete branding/portal checkboxes are removed; student portal is role-driven and parent access uses expiring links. | Maintenance/admin bypass, global AI stop, channel disable and navigation regression. |
| C26 | Processing/report/billing/parent-delivery database notifications respect recipient preferences; Support Center remains independent. | Enabled/disabled preference regression plus existing Support Center suite. |
| C27 | Isolated test environment, V6 README, incremental upgrade procedure, safe seeding/admin provisioning, source-only release builder and secret/runtime Git exclusions added. Dependencies patched after audit. | Final verification recorded below. Old shared Git history/archive is not erased by an index cleanup; rotate credentials if previously shared. Live release checklist remains explicit. |

## Verification record

Final post-update direct PHPUnit run: **85 tests, 875 assertions, exit 0**, 8.808 seconds, no PHPUnit warnings, failures or risky-test notices. Command: `php vendor/phpunit/phpunit/phpunit --no-progress --log-junit=storage/framework/testing/remediation-final.xml`. Text output: `storage/framework/testing/remediation-final.txt`. Earlier intermediate failures were corrected and are not release acceptance records.

- Isolated test fixtures use a null logger and reduced bcrypt work factor; production password hashing settings are unchanged.
- PHP 8.2.12; Laravel updated within the 12.x constraint to 12.71.0; mPDF 8.3.1 added.
- Composer update completed with **no security vulnerability advisories found**, after addressing the 13 advisories reported for the earlier dependency set.
- Final PHP lint: 140 source/test/script files passed. Blade cache compilation succeeded. Composer configuration validation passed with one non-security metadata warning: the project does not specify a license.
- Bengali/long-feedback report rendered through Poppler and visually inspected: readable Unicode text, canonical part label, full final feedback marker, clear footer, no clipping in the inspected sample.
- Tests use in-memory SQLite, private fake storage, mail fakes/array mail and mocked HTTP. Live OpenAI's 429 is operational evidence, not a successful real-exam marking acceptance run.

## Remaining deployment gates

1. Restore OpenAI API credit/quota or wait for the provider rate-limit window, then retry the same paper with the intended exam files. Image-only Word files need OCR or a selectable-text copy.
2. Validate actual provider/model accounts, merchant sandbox callbacks, SMTP/password reset/verification/invitation delivery and real document extraction on the deployment server.
3. Run multi-worker crash/retry and concurrent MySQL quota tests under deployment conditions; enable the scheduler and ensure worker timeout/retry configuration is preserved.
4. Confirm official exam/session grade boundaries and marking accuracy with examiners. The demo/local marker remains provisional; demo mobile delivery is a recorded link, not a real SMS integration.
5. Keep previous credential-bearing Git history/archives private. New release packaging excludes credentials, databases, private uploads, logs and generated caches.

See [safe upgrade procedure](V6_SAFE_UPGRADE.md) and [payment setup](PAYMENTS.md). These gates prevent a code/test completion claim from being mistaken for live settlement, real SMS or examiner-quality certification.

## Local release hygiene result

Credential files, database exports, private/runtime storage and generated bootstrap caches were removed from the Git index with user-approved index access. Files remain on disk. The release builder verified a source-only ZIP with no credentials, databases, Git history, runtime storage or vendor dependencies. Historical commits are unchanged.

## Follow-up: real PDF page numbers and writing rubric tables

The uploaded English paper #3 exposed layout cases beyond the earlier small fixtures. A standalone page number `8` reset the QP parser to question 8, so the writing question's final `[25]` appeared to conflict with Q8's `[4]`. The MS parser also treated individual rubric criteria and score bands as competing question totals.

Question-paper parsing now removes contextual page furniture while preserving genuine standalone question numbers. Mark schemes use a separate known-question/table parser that distinguishes whole-question totals from criteria, score bands and ranked answer lists. Genuine QP/MS maximum conflicts are still rejected. Answer text is no longer validated as maximum-mark declarations, and whole/targeted marking retain the correct MS sections.

Read-only extraction of this QP and MS now agrees on **15 scored questions/sub-questions totaling 50 marks**. The form had been set to 30. Paper #3 was corrected to 50 with an audit record and returned to uploaded/retryable status; original documents and quota reservation were preserved. Paper options now permits total-only correction on uploaded/failed papers, while approved/checking papers remain protected. No AI request was made during this correction.

Latest full regression: **104 tests, 935 assertions, exit 0** (8.851 seconds). Added synthetic layout/rubric and total-correction regressions; the real exam files remain private and are not copied into source fixtures. Evidence: `storage/framework/testing/question-layout-regression.xml`.
