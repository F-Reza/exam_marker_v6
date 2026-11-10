<?php

namespace App\Services;

use App\Models\{SupportTicket, User};
use App\Notifications\SupportTicketActivity;

class SupportTicketNotifier
{
    public function markRead(SupportTicket $ticket, User $viewer): void
    {
        $viewer->unreadNotifications()
            ->where('type', SupportTicketActivity::class)
            ->where('data->ticket_id', $ticket->id)
            ->update(['read_at' => now()]);
    }

    public function notify(SupportTicket $ticket, User $actor, string $event): void
    {
        $recipients = $actor->isPlatformAdmin()
            ? User::whereKey($ticket->user_id)->where('id', '!=', $actor->id)->get()
            : User::where(fn($query) => $query->where('is_admin', true)->orWhere('user_type', 'platform_admin'))
                ->where('id', '!=', $actor->id)->get();

        $title = match ($event) {
            'created' => 'New support ticket #'.$ticket->id,
            'replied' => 'New reply on ticket #'.$ticket->id,
            'status_changed' => 'Ticket #'.$ticket->id.' status updated',
            default => throw new \InvalidArgumentException('Unknown support notification event.'),
        };
        $status = SupportTicket::STATUSES[$ticket->status] ?? $ticket->status;
        $message = $ticket->subject.' — '.$status;

        foreach ($recipients as $recipient) {
            $url = route($recipient->isPlatformAdmin() ? 'admin.support.show' : 'help.show', $ticket, false);
            $recipient->notify(new SupportTicketActivity($ticket->id, $title, $message, $url));
        }
    }
}
