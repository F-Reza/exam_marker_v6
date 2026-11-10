@extends('layouts.app')
@include('help.partials.styles')
@section('title', 'Help & Support')
@section('page-title', 'Help & Support')
@section('content')
@php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
@endphp
<div class="support-page">
    <div class="welcome">
        <div><span class="eyebrow">SUPPORT CENTER</span><h1>Help & Support</h1><p>Send a request, track its progress and read replies from the support team.</p></div>
    </div>
    <div class="help-grid">
        <section class="panel support-card">
            <h2>Create support ticket</h2>
            <form method="post" action="{{ route('help.store') }}" class="support-form">
                @csrf
                <label for="support-subject">Subject
                    <input class="input" id="support-subject" name="subject" value="{{ is_string(old('subject')) ? old('subject') : '' }}" maxlength="180" required @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
                    @error('subject')<span class="support-field-error" id="subject-error">{{ $message }}</span>@enderror
                </label>
                <div class="form-grid">
                    <label for="support-category">Category
                        <select class="input" name="category" id="support-category" required>
                            @foreach($categories as $value => $text)<option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $text }}</option>@endforeach
                        </select>
                        @error('category')<span class="support-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label for="support-priority">Priority
                        <select class="input" name="priority" id="support-priority" required>
                            @foreach($priorities as $value => $text)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $text }}</option>@endforeach
                        </select>
                        @error('priority')<span class="support-field-error">{{ $message }}</span>@enderror
                    </label>
                </div>
                <label for="support-message">Message
                    <textarea class="input" name="message" id="support-message" rows="6" minlength="10" maxlength="10000" required @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ is_string(old('message')) ? old('message') : '' }}</textarea>
                    @error('message')<span class="support-field-error" id="message-error">{{ $message }}</span>@enderror
                </label>
                <div class="support-form-actions"><button class="btn">Submit Ticket</button><span class="support-note">Choose Feedback to share a suggestion.</span></div>
            </form>
        </section>
        <section class="panel faq-card">
            <h2>Quick answers</h2>
            <details open><summary>How do I follow up on a ticket?</summary><p>Open a ticket from the list below to read replies and send more details. You can close it when the issue is sorted, or reopen it if you still need help.</p></details>
            <details><summary>Why is MS optional?</summary><p>A mark scheme provides better marking guidance. If it is missing, the suggested result may need extra teacher review.</p></details>
            <details><summary>Can I edit AI marks?</summary><p>Mark editing is available when your plan includes it. Open Plan & Billing to review the features available to your account.</p></details>
            <details><summary>Who can see my support tickets?</summary><p>Only you and the platform support administrators can read your tickets. Other teachers and students cannot see them.</p></details>
        </section>
    </div>
    <section class="panel support-ticket-list">
        <div class="panel-head"><h2>Your tickets <span class="support-heading-count">({{ $tickets->total() }})</span></h2></div>
        <form method="get" action="{{ route('help.index') }}" class="support-filters">
            <label class="support-search" for="ticket-search">Search tickets<input class="input" id="ticket-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Subject, message or ticket number"></label>
            <label for="ticket-filter-status">Status<select class="input" id="ticket-filter-status" name="status"><option value="">All statuses</option>@foreach($statuses as $value => $text)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $text }}</option>@endforeach</select></label>
            <button class="btn ghost">Filter</button>
            @if(!empty($filters['q']) || !empty($filters['status']))<a class="support-clear" href="{{ route('help.index') }}">Clear filters</a>@endif
        </form>
        <div class="table-wrap">
            <table class="support-table">
                <thead><tr><th>Ticket</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td><span class="support-reference">#{{ $t->id }}</span><a class="support-ticket-subject" href="{{ route('help.show', $t) }}">{{ $t->subject }}</a><small class="support-ticket-preview">{{ \Illuminate\Support\Str::limit($t->message, 110) }}</small></td>
                        <td>{{ $categories[$t->category] ?? ucfirst($t->category) }}</td>
                        <td><span class="support-badge priority-{{ $t->priority }}">{{ $priorities[$t->priority] ?? ucfirst($t->priority) }}</span></td>
                        <td><span class="support-badge status-{{ $t->status }}">{{ $statuses[$t->status] ?? ucfirst($t->status) }}</span></td>
                        <td><time datetime="{{ $t->updated_at->toIso8601String() }}" title="{{ $t->updated_at->format('d M Y, H:i') }}">{{ $t->updated_at->diffForHumans() }}</time></td>
                        <td><a class="table-link" href="{{ route('help.show', $t) }}">View ticket</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="support-empty"><b>{{ !empty($filters['q']) || !empty($filters['status']) ? 'No tickets match your filters.' : 'No support tickets yet.' }}</b>{{ !empty($filters['q']) || !empty($filters['status']) ? 'Try a different search or clear the filters.' : 'Submit a ticket above when you need help.' }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())<div class="pagination">{{ $tickets->links() }}</div>@endif
    </section>
</div>
@endsection
