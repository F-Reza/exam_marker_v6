@extends('layouts.app')
@include('help.partials.styles')
@section('title', 'Support Center')
@section('page-title', 'Support Center')
@section('content')
@php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $tabs = ['inbox' => 'Support Inbox', 'feedback' => 'Customer Feedback', 'solutions' => 'Solutions Provided'];
@endphp
<div class="support-page">
    <div class="welcome">
        <div><span class="eyebrow">CUSTOMER SUPPORT</span><h1>Support Center</h1><p>Manage support requests and feedback from every account and plan.</p></div>
    </div>
    <section class="panel support-ticket-list">
        <nav class="support-tabs" aria-label="Support ticket views">
            @foreach($tabs as $value => $text)
            <a href="{{ route('admin.support.index', ['tab' => $value]) }}" class="{{ $tab === $value ? 'active' : '' }}" @if($tab === $value) aria-current="page" @endif>{{ $text }}<span class="support-tab-count">{{ $counts[$value] ?? 0 }}</span></a>
            @endforeach
        </nav>
        <div class="panel-head">
            <div><h2>{{ $tabs[$tab] ?? 'Support Inbox' }} <span class="support-heading-count">({{ $tickets->total() }})</span></h2><p>{{ $tab === 'feedback' ? 'Suggestions and feedback submitted by users.' : ($tab === 'solutions' ? 'Resolved or closed tickets with a support reply.' : 'All submitted support tickets, including feedback.') }}</p></div>
        </div>
        <form method="get" action="{{ route('admin.support.index') }}" class="support-filters">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="support-search" for="admin-ticket-search">Search<input class="input" type="search" name="q" id="admin-ticket-search" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Ticket, subject, message or user"></label>
            <label for="admin-status-filter">Status<select class="input" id="admin-status-filter" name="status"><option value="">All statuses</option>@foreach($statuses as $value => $text)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $text }}</option>@endforeach</select></label>
            <label for="admin-priority-filter">Priority<select class="input" id="admin-priority-filter" name="priority"><option value="">All priorities</option>@foreach($priorities as $value => $text)<option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $text }}</option>@endforeach</select></label>
            @if($tab !== 'feedback')
            <label for="admin-category-filter">Category<select class="input" id="admin-category-filter" name="category"><option value="">All categories</option>@foreach($categories as $value => $text)<option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $text }}</option>@endforeach</select></label>
            @endif
            <button class="btn ghost">Filter</button>
            @if(!empty($filters['q']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['category']))<a class="support-clear" href="{{ route('admin.support.index', ['tab' => $tab]) }}">Clear filters</a>@endif
        </form>
        <div class="table-wrap">
            <table class="support-table">
                <thead><tr><th>Ticket</th><th>User</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td><span class="support-reference">#{{ $t->id }}</span><a class="support-ticket-subject" href="{{ route('admin.support.show', ['ticket' => $t, 'tab' => $tab]) }}">{{ $t->subject }}</a><small class="support-ticket-preview">{{ \Illuminate\Support\Str::limit($tab === 'solutions' ? $t->admin_reply : $t->message, 110) }}</small></td>
                        <td><b>{{ $t->user?->name ?? 'Account unavailable' }}</b><small>{{ $t->user?->email }}</small></td>
                        <td>{{ $categories[$t->category] ?? ucfirst($t->category) }}</td>
                        <td><span class="support-badge priority-{{ $t->priority }}">{{ $priorities[$t->priority] ?? ucfirst($t->priority) }}</span></td>
                        <td><span class="support-badge status-{{ $t->status }}">{{ $statuses[$t->status] ?? ucfirst($t->status) }}</span></td>
                        <td><time datetime="{{ $t->updated_at->toIso8601String() }}" title="{{ $t->updated_at->format('d M Y, H:i') }}">{{ $t->updated_at->diffForHumans() }}</time></td>
                        <td><a class="table-link" href="{{ route('admin.support.show', ['ticket' => $t, 'tab' => $tab]) }}">Open ticket</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="support-empty"><b>No tickets found.</b>{{ $tab === 'solutions' ? 'Tickets appear here once a support reply has been recorded and the issue is resolved or closed.' : ($tab === 'feedback' ? 'User suggestions submitted under Feedback appear here.' : 'New support requests appear here as soon as a user submits a ticket.') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())<div class="pagination">{{ $tickets->links() }}</div>@endif
    </section>
</div>
@endsection
