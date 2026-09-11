# Exam Marker V6 Payment Setup

The Platform Administrator can enable PayPal, PayU, PhonePe and Paytm from **Admin > Payment Gateways**. Credentials are stored with Laravel's `encrypted:array` cast and therefore depend on the application's `APP_KEY`.

## Implemented checkout adapters

- **PayPal**: OAuth order creation and capture using the configured Client ID and Client Secret.
- **PayU**: hosted checkout form with SHA-512 request hash, success and failure callbacks.
- **PhonePe**: configurable current PG checkout endpoint and Authorization header. PhonePe changes API versions, so use the endpoint/authorization format supplied for the merchant account.
- **Paytm**: administration/configuration UI and transaction model are included. Production Paytm checkout must use Paytm's current official checksum utility/SDK; the code deliberately refuses to invent a checksum implementation.

Plans are upgraded only after a callback is verified as successful. Failed and cancelled transactions do not change the plan.

Before going live, use HTTPS, configure provider webhooks/callback allowlists, rotate keys, test refunds and reconcile gateway transaction IDs.
