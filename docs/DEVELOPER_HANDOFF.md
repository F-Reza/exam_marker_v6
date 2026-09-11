# Developer Handoff

This package is a complete Laravel workflow implementation rather than a static mockup. The demo marker makes every screen testable immediately. Replace only the `PaperMarker` adapter when connecting production OCR/AI.

## Main workflow

1. Register or login.
2. Create a paper check.
3. Enter assessment details.
4. Upload QP and optional MS on the same step.
5. Upload WA and confirm.
6. Review uploaded documents.
7. Start checking and view progress.
8. Review question-wise results.
9. Mode 2/3: edit marks, recheck and download result report.
10. Mode 3: download corrected paper and send a secure parent report.

## Production tasks requiring credentials

- OCR / handwriting recognition
- LLM marking provider
- Payment gateway
- SMTP provider
- SMS/WhatsApp provider
- Virus scanning and object storage

All have clear service boundaries and do not require redesigning the UI or data model.
