<div class="support-thread">
    <h2>Conversation</h2>
    <article class="support-message">
        <div class="support-message-meta">
            <div><b>{{ $ticket->user?->name ?? 'Account unavailable' }}</b><small>Original request</small></div>
            <time datetime="{{ $ticket->created_at->toIso8601String() }}">{{ $ticket->created_at->format('d M Y, H:i') }}</time>
        </div>
        <div class="support-message-body">{{ $ticket->message }}</div>
    </article>

    @if($ticket->hasLegacyReply())
    <article class="support-message is-admin">
        <div class="support-message-meta"><div><b>Support Team</b><small>Previous reply</small></div></div>
        <div class="support-message-body">{{ $ticket->admin_reply }}</div>
    </article>
    @endif

    @foreach($ticket->messages as $entry)
        @if($entry->type === 'status_change')
        <div class="support-event">
            <span>{{ $entry->message }}</span>
            <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('d M Y, H:i') }}</time>
        </div>
        @else
        @php($fromSupport = $entry->is_admin || $entry->type === 'legacy_reply')
        <article class="support-message {{ $fromSupport ? 'is-admin' : '' }}">
            <div class="support-message-meta">
                <div>
                    <b>{{ $fromSupport ? 'Support Team' : ($entry->user?->name ?? 'Account unavailable') }}</b>
                    @if($entry->type === 'legacy_reply')<small>Previous reply</small>@endif
                </div>
                <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('d M Y, H:i') }}</time>
            </div>
            <div class="support-message-body">{{ $entry->message }}</div>
        </article>
        @endif
    @endforeach
</div>
