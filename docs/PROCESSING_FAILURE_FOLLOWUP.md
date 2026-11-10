Processing failure follow-up (includes the previous PDF/DOC/DOCX patch)

Scope: fixes failures before the job handler starts, queue failure callbacks,
and missing retry state. Does not establish the root cause on your server.
The actual exception and stack trace are recorded with assessment_id in
storage/logs/laravel.log. Do not publish full logs or API credentials.

Install into the Laravel project root after backup, then run:
composer dump-autoload
php artisan optimize:clear
php artisan queue:restart

Use EXAM_MARKER_OCR=local for the supplied local document reader.
Do not switch to demo to diagnose real marking.

Reproduce with a new upload. If it fails, send the latest exception message
and first stack frames for that assessment, removing credentials and personal data.
If an old assessment remains queued, do not reset it while a worker is active.
Once you have stopped workers and confirmed no job is running for it, you may
reset assessment 2 only in php artisan tinker:
App\Models\Assessment::whereKey(2)->whereIn('status', ['queued','processing'])->update(['status'=>'failed','error_message'=>'Previous processing stopped; retry after correcting the server error.']);
Then restart workers if used and open that paper to retry.

The source booklet shown in the screenshot is named Exam-Marker-V6-Audit-Report.docx.
Confirm it is the intended source material for this English test.
