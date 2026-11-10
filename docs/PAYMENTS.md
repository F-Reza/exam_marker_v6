# Exam Marker V6 payment and subscription setup

Only implemented authenticated adapters can be enabled. Credential values are encrypted using the application key; preserve that key during upgrades.

- **PayU / INR:** hosted checkout uses a server SHA-512 request hash. Callback processing checks the reverse response hash and stored order/amount/merchant binding, then calls the authenticated Verify Payment API and requires a captured success with matching original amount before granting a plan. See [PayU response hashing](https://docs.payu.in/docs/hashing-request-and-response) and [Verify Payment API](https://docs.payu.in/reference/verify_payment_api).
- **PayPal:** server OAuth creates an order and later retrieves/captures that same stored order, checking reference, amount, currency and capture status. Create/capture requests use stable idempotency keys. See [PayPal Orders integration](https://developer.paypal.com/whats-an-order/). PayPal is hidden for INR; there is no implicit currency conversion. If using a supported currency, configure EXAM_MARKER_CURRENCY and matching plan prices before enabling checkout.
- **PhonePe, Paytm, bKash and card:** unavailable; admin enable requests are rejected and checkout hides them until complete authenticated adapters and merchant tests exist.

Callbacks are bound to the transaction gateway and run under a transaction lock. Replayed success does not extend the subscription again; later failure/cancellation cannot downgrade a paid transaction. The authenticated cancel route only cancels the owner's pending checkout. Direct paid-plan changes redirect to checkout.

Paid access is a manually renewed monthly term, with start/end timestamps and anniversary-based paper limits. Cancellation preserves the paid term until expiry; no automatic recurring charge is made. Admin plan assignment is an explicit monthly grant. Existing paid owners receive a migration-time transition month. Expired users/staff inherit Free entitlements while transaction history remains intact.

Before production, record real merchant sandbox success, failure, cancellation, replay, wrong amount/currency/order, provider timeout and reconciliation evidence. HTTP mock tests establish application behaviour; they do not establish live settlement or merchant-account readiness. Never enable an adapter by accepting client-supplied success text.