<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'answered' => 'Answered',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public const CATEGORIES = [
        'general' => 'General',
        'paper processing' => 'Paper processing',
        'billing' => 'Billing',
        'account' => 'Account',
        'feedback' => 'Feedback',
        'upload' => 'Upload',
    ];

    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'];

    protected $fillable = ['user_id', 'subject', 'category', 'priority', 'status', 'message', 'admin_reply'];

    protected function casts(): array
    {
        return ['user_id' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('id');
    }

    public function hasLegacyReply(): bool
    {
        if (!filled($this->admin_reply)) return false;

        $hasAdminReply = $this->relationLoaded('messages')
            ? $this->messages->contains(fn($message) => $message->type === 'message' && $message->is_admin)
            : $this->messages()->where('type', 'message')->where('is_admin', true)->exists();

        return !$hasAdminReply;
    }

    public function preserveLegacyReply(): void
    {
        if (!$this->hasLegacyReply()) return;

        $reply = $this->messages()->make([
            'user_id' => null,
            'message' => $this->admin_reply,
            'type' => 'message',
            'is_admin' => true,
        ]);
        $timestamp = $this->updated_at ?? $this->created_at ?? now();
        $reply->setCreatedAt($timestamp);
        $reply->setUpdatedAt($timestamp);
        $reply->save();
        $this->unsetRelation('messages');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach (['status', 'priority', 'category'] as $field) {
            if (filled($filters[$field] ?? null)) $query->where($field, $filters[$field]);
        }

        if (filled($filters['q'] ?? null)) {
            $search = trim($filters['q']);
            $query->where(function (Builder $query) use ($search) {
                $query->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn(Builder $user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                if (ctype_digit(ltrim($search, '#'))) $query->orWhereKey((int) ltrim($search, '#'));
            });
        }

        return $query;
    }
}
