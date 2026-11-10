# Exam Marker V6 document upload and extraction hotfix

Scope: PDF / DOC / DOCX validation, storage and extraction for QP, optional MS, inserts and WA, in single and bulk flows. Built against the supplied exam_marker_v6(1).zip. This is not a fix for all 27 consolidated audit findings.

## Install

1. Back up the affected files. Extract the patch into the Laravel project root, preserving app/, config/, resources/, tests/ and docs/ paths. Do not replace your database or production .env. If your source has changed since the supplied ZIP, merge the controller/provider/view changes rather than blindly overwriting newer work.
2. Set EXAM_MARKER_OCR=local in your existing .env to use the corrected local reader. ProductionDocumentReader remains an integration stub; demo mode still supplies demo text.
3. Enable PHP XML/DOM and either PHP ZipArchive or PharData. PHP fileinfo, mbstring and the usual Laravel extensions are also needed. DOCX extraction automatically falls back to PharData when ZipArchive is unavailable. It does not require Microsoft Word or a composer dependency change.
4. For legacy binary DOC, install antiword OR LibreOffice. Merely accepting a .doc extension cannot decode its binary contents. The patch tries antiword, then LibreOffice; if neither works it reports the missing dependency instead of generating a blank answer/result.
5. From the project root run:

   composer dump-autoload
   php artisan optimize:clear
   php artisan queue:restart

   Restart your local PHP server or long-running worker if necessary. The queue restart command matters when workers are in use. No database migration is required.
6. Upload a new mixed-format paper and test. Failed existing uploads whose DOCX was stored as .zip/.bin can also be retried because the new reader detects content; damaged or missing files must be uploaded again.

## Windows local development

The screenshot uses 127.0.0.1; use these settings if your local machine is Windows. Enable extension=zip, extension=dom/XML and extension=mbstring in the php.ini used by both the web server and CLI. Check `php --ini` and `php -m`; the web server may use a different php.ini.

If using LibreOffice for DOC, install it and set the executable path in .env, for example:

LIBREOFFICE_PATH="C:/Program Files/LibreOffice/program/soffice.exe"

Or, if antiword is installed:

ANTIWORD_PATH="C:/tools/antiword/antiword.exe"

Keep antiword mapping data installed alongside it. The extractor requests UTF-8 text. Process execution/proc_open must be available to Laravel's Symfony Process component. Absolute executable paths avoid PATH differences between CLI, web server and queue workers. The new commands use argument arrays and avoid Unix /dev/null redirection.

## Linux server dependencies

Use the package equivalents for your PHP version/distribution: php-xml, php-zip (or phar support), antiword or libreoffice-writer. For image-only PDFs also install poppler-utils and tesseract-ocr. Plain text PDFs use the PHP parser first, then pdftotext, then scanned-PDF OCR. Optional paths:

PDFTOTEXT_PATH=/usr/bin/pdftotext
PDFTOPPM_PATH=/usr/bin/pdftoppm
TESSERACT_PATH=/usr/bin/tesseract
ANTIWORD_PATH=/usr/bin/antiword
LIBREOFFICE_PATH=/usr/bin/soffice

The server's temporary directory must be writable. Conversion and OCR use isolated temporary directories and clean them after each attempt. Private file storage must be writable too.

## What changed

- Replaced MIME-guessed extension validation with whitelisted extension AND content checks. Valid PDF/DOCX uploads reported as application/octet-stream or application/zip no longer fail only because of MIME classification. An arbitrary file renamed .pdf/.docx is still rejected. DOCX containers must contain word/document.xml. Legacy DOC must be a Word OLE container or RTF.
- Applied the same rule to QP, MS, inserts and single/bulk written answers; retained 50 MB per-file limit, maximum five inserts, compulsory QP/WA, optional MS and existing plan gates.
- Preserved the validated lowercase extension while saving random private filenames. Previous MIME-derived .zip/.bin DOCX storage is recognised by content when reading.
- DOCX XML parsing preserves paragraphs, tables, tabs, line breaks and text boxes; deleted text/field instructions are excluded. Added PharData fallback, archive/XML size guard and blocked external DTD content.
- Added legacy DOC antiword/LibreOffice fallback using argument-array processes, configurable paths, timeouts and isolated conversion profiles. No Word macros are intentionally executed by application code.
- Missing dependency, unreadable/encrypted file, missing file and empty extracted text errors now identify the document role. Optional uploaded MS/insert errors are no longer silently ignored.
- Synchronous checking failures return to the paper details with an error; bulk synchronous failures keep other uploaded papers and report failures. This does not implement full atomic bulk rollback or queue locking from the broader audit.
- Replaced simulated progress and blind results redirect with polling of the existing authorised status endpoint. Failure text is rendered through textContent, not injected HTML.
- Added document regression fixtures/tests; updated the existing upload-access regression to use real PDF content rather than empty fake files. Added tests/.env.testing only for isolated tests.

## Verification

See the verification summary appended before packaging. Tests use an isolated in-memory SQLite database and do not mark your real papers or contact an AI provider. Real-user files from the reported failure have not been supplied yet; their exact content-specific failure cannot be claimed as reproduced. The screenshot's inserts.0 MIME-validation condition is covered using generic-MIME PDF and DOCX fixtures.

## Limits

Typed Word documents are supported. A DOCX containing only scanned images has no text for this local Word extractor; use PDF OCR or a configured vision/OCR provider. Password-protected documents must be unlocked. Extraction support does not establish examiner-grade marking accuracy. Question parsing, AI settings, payment, quotas and other audit topics outside this patch remain separate work.

### Verification result on 10 November 2026

- PHP 8.2.32, isolated SQLite in-memory tests: **42 tests, 642 assertions, no failures** using `php vendor/bin/phpunit --no-progress`.
- Includes nine new document tests: Mode1/Mode2 mixed PDF+DOCX processing, optional MS, all-DOCX input roles, Mode2 bulk, generic MIME regression, disguised-file rejection, Phar DOCX fallback, previously misnamed DOCX recovery, clear missing-DOC-converter error, and graceful processing failure (some scenarios share a test method).
- Existing upload-access test now supplies genuine PDF fixture bytes rather than empty MIME-labelled files; production validation was not weakened to accept empty files.
- Real binary DOC extraction smoke check passed with antiword using Apache POI's simple.doc test sample (https://raw.githubusercontent.com/apache/poi/trunk/test-data/document/simple.doc). This external sample was used only for verification and is not included in the patch.
- DOCX text/table extraction smoke passed using native ZipArchive and PharData fallback.
- PHP lint passed for all ten changed/new PHP source/test files; processing JavaScript passed syntax checking.
- Windows LibreOffice conversion and your specific original documents have not been run here. On Windows, configure the installed executable path as above and retry with your files.
