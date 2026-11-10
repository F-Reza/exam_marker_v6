<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\LifecycleActivity;

class LifecycleNotifier
{
    public function send(?User $user, string $category, string $title, string $message, ?string $url = null): void
    {
        if (!$user || !($user->notification_preferences[$category] ?? true)) return;
        try {
            $user->notify(new LifecycleActivity($category, $title, $message, $url));
        } catch (\Throwable $e) {
            // Notification availability must not roll back a completed business action.
            report($e);
        }
    }
}
