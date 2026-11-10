<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LifecycleActivity extends Notification
{
    public function __construct(public string $category, public string $title, public string $message, public ?string $url = null) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'lifecycle', 'category' => $this->category, 'title' => $this->title, 'message' => $this->message, 'url' => $this->url];
    }
}
