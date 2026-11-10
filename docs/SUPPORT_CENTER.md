# Support Center

Support tickets are available to every signed-in account, including Free teachers and students. Ticket ownership is personal: a coaching administrator does not automatically see a staff member's private support requests. Platform administrators manage all tickets from the admin Support Center.

## Customer flow

1. Open **Support Center** (`/help`). Create a ticket with subject, category, priority and message. Choose **Feedback** for suggestions or product feedback.
2. After submission, open the ticket conversation. The ticket list contains only the current user's tickets, with search, status filtering and pagination.
3. Read support replies and add follow-up messages. A follow-up on an answered ticket returns it to **Open**.
4. Close the ticket when help is no longer needed. Reopen a resolved or closed ticket before continuing the conversation.

## Administrator flow

1. Open `/admin/support` to see real tickets from all account types.
2. **Support Inbox** includes all tickets. **Customer Feedback** filters the feedback category. **Solutions Provided** shows resolved/closed tickets that have a support reply.
3. Search by ticket number, subject, original message or requester. Filter by status, priority and category; filters remain when changing pages.
4. Open a ticket, read its full conversation, reply or update its status. A reply defaults to **Answered**; a status-only update is also supported.

Statuses are **Open**, **In progress**, **Answered**, **Resolved** and **Closed**. Conversations retain the original request, replies and status changes. Historical `admin_reply` values remain visible and are copied into the conversation before the first update.

## Notifications and access

- New tickets, requester replies and requester status changes notify platform administrators inside the application.
- Administrator replies and status changes notify the requester. These notifications use the database channel; they do not send email or SMS.
- Notifications link to the appropriate user/admin ticket page. Opening a ticket marks only the current viewer's notifications for that ticket as read.
- Ownership is checked for ticket details, replies and status changes. Normal users cannot access another user's tickets or administrator endpoints.
- Notification recipients and ticket author identities come from the authenticated session. Submitted ownership/status fields cannot override ticket creation defaults.

## Storage and verification

The existing `support_tickets` table is preserved. The incremental migration `2026_11_10_000001_create_support_ticket_messages_table.php` adds conversation storage; it does not reset tickets or other application data.

For another installation, apply the migration with:

```bash
php artisan migrate --path=database/migrations/2026_11_10_000001_create_support_ticket_messages_table.php
```

Run the support regression tests with:

```bash
php vendor/phpunit/phpunit/phpunit tests/Feature/SupportCenterTest.php
```

The test harness uses a separate SQLite database in memory and does not load the application's `.env` file.
