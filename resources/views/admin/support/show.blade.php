@extends('layouts.app')
@include('help.partials.styles')
@section('title', 'Support ticket #'.$ticket->id)
@section('page-title', 'Support Center')
@section('content')
@php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $backTab = in_array(request('tab'), ['inbox', 'feedback', 'solutions'], true) ? request('tab') : 'inbox';
@endphp
<div class="support-page">
    <div class="welcome">
        <div>
            <a class="back" href="{{ route('admin.support.index', ['tab' => $backTab]) }}">&larr; Support Center</a>
            <span class="support-reference">TICKET #{{ $ticket->id }}</span>
            <h1>{{ $ticket->subject }}</h1>
            <p>Read the full conversation, reply to the user and update the ticket status.</p>
        </div>
        <span class="support-badge status-{{ $ticket->status }}">{{ $statuses[$ticket->status] ?? ucfirst($ticket->status) }}</span>
    </div>

    <div class="support-detail-grid">
        <section class="panel">
            @include('help.partials.ticket-thread')
            <div class="support-reply">
                <h2>Reply to user</h2>
                <p class="support-note">Sending a reply marks this ticket as Answered. The user can read it in their Support Center.</p>
                <form method="post" action="{{ route('admin.support.reply', $ticket) }}" class="support-form">
                    @csrf @method('PUT')
                    <label for="admin-message">Message
                        <textarea class="input" id="admin-message" name="message" rows="6" minlength="1" maxlength="10000" required @error('message') aria-invalid="true" aria-describedby="admin-message-error" @enderror>{{ is_string(old('message')) ? old('message') : '' }}</textarea>
                        @error('message')<span class="support-field-error" id="admin-message-error">{{ $message }}</span>@enderror
                    </label>
                    <div class="support-form-actions"><button class="btn">Send reply</button></div>
                </form>
            </div>
        </section>

        <aside class="support-detail-sidebar">
            <section class="panel support-details">
                <h2>Ticket details</h2>
                <dl>
                    <div><dt>Submitted by</dt><dd>{{ $ticket->user?->name ?? 'Account unavailable' }}<small>{{ $ticket->user?->email }}</small></dd></div>
                    <div><dt>Category</dt><dd>{{ $categories[$ticket->category] ?? ucfirst($ticket->category) }}</dd></div>
                    <div><dt>Priority</dt><dd><span class="support-badge priority-{{ $ticket->priority }}">{{ $priorities[$ticket->priority] ?? ucfirst($ticket->priority) }}</span></dd></div>
                    <div><dt>Created</dt><dd>{{ $ticket->created_at->format('d M Y, H:i') }}</dd></div>
                    <div><dt>Last updated</dt><dd>{{ $ticket->updated_at->format('d M Y, H:i') }}</dd></div>
                </dl>
            </section>
            <section class="panel support-status-action">
                <h2>Update status</h2>
                <form method="post" action="{{ route('admin.support.reply', $ticket) }}" class="support-form">
                    @csrf @method('PUT')
                    <label for="ticket-status">Status
                        <select class="input" name="status" id="ticket-status" required>
                            @foreach($statuses as $value => $text)
                            <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        @error('status')<span class="support-field-error">{{ $message }}</span>@enderror
                    </label>
                    <button class="btn ghost">Update status</button>
                </form>
            </section>
        </aside>
    </div>
</div>
@endsection
