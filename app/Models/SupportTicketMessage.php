<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'message', 'type', 'is_admin'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'ticket_id' => 'integer', 'is_admin' => 'boolean'];
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
