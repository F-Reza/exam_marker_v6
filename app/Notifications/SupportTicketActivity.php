<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SupportTicketActivity extends Notification
{
    public function __construct(
        public int $ticketId,
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket',
            'ticket_id' => $this->ticketId,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
