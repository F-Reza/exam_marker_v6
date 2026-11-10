@extends('layouts.app')
@include('help.partials.styles')
@section('title', 'Support ticket #'.$ticket->id)
@section('page-title', 'Support Center')
@section('content')
@php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
@endphp
<div class="support-page">
    <div class="welcome">
        <div>
            <a class="back" href="{{ route('help.index') }}">&larr; Your tickets</a>
            <span class="support-reference">TICKET #{{ $ticket->id }}</span>
            <h1>{{ $ticket->subject }}</h1>
            <p>Messages and updates from the support team appear here.</p>
        </div>
        <span class="support-badge status-{{ $ticket->status }}">{{ $statuses[$ticket->status] ?? ucfirst($ticket->status) }}</span>
    </div>

    <div class="support-detail-grid">
        <section class="panel">
            @include('help.partials.ticket-thread')
            <div class="support-reply">
                @if($isClosed)
                <h2>{{ $ticket->status === 'resolved' ? 'This ticket has been resolved' : 'This ticket is closed' }}</h2>
                <p class="support-note">If you still need help with this issue, reopen the ticket to continue the conversation.</p>
                <form method="post" action="{{ route('help.status', $ticket) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="open">
                    <button class="btn">Reopen ticket</button>
                </form>
                @else
                <h2>Reply to support</h2>
                <p class="support-note">Add more details or let us know whether the reply helped.</p>
                <form method="post" action="{{ route('help.reply', $ticket) }}" class="support-form">
                    @csrf
                    <label for="reply-message">Message
                        <textarea class="input" id="reply-message" name="message" rows="5" minlength="1" maxlength="10000" required @error('message') aria-invalid="true" aria-describedby="reply-message-error" @enderror>{{ is_string(old('message')) ? old('message') : '' }}</textarea>
                        @error('message')<span class="support-field-error" id="reply-message-error">{{ $message }}</span>@enderror
                    </label>
                    <div class="support-form-actions"><button class="btn">Send reply</button></div>
                </form>
                @endif
            </div>
        </section>

        <aside class="support-detail-sidebar">
            <section class="panel support-details">
                <h2>Ticket details</h2>
                <dl>
                    <div><dt>Category</dt><dd>{{ $categories[$ticket->category] ?? ucfirst($ticket->category) }}</dd></div>
                    <div><dt>Priority</dt><dd><span class="support-badge priority-{{ $ticket->priority }}">{{ $priorities[$ticket->priority] ?? ucfirst($ticket->priority) }}</span></dd></div>
                    <div><dt>Created</dt><dd>{{ $ticket->created_at->format('d M Y, H:i') }}</dd></div>
                    <div><dt>Last updated</dt><dd>{{ $ticket->updated_at->format('d M Y, H:i') }}</dd></div>
                </dl>
            </section>
            @if(!$isClosed)
            <section class="panel support-status-action">
                <h2>Issue sorted?</h2>
                <p class="support-note">Close this ticket when you no longer need help. You can reopen it later.</p>
                <form method="post" action="{{ route('help.status', $ticket) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="closed">
                    <button class="btn ghost">Close ticket</button>
                </form>
            </section>
            @endif
        </aside>
    </div>
</div>
@endsection
