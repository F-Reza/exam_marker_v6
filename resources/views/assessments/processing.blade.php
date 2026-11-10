@extends('layouts.app')
@section('title','Checking Paper')
@section('page-title','Paper Checking')
@section('content')
<div class="processing-shell panel">
 <div class="ai-doc-icon">AI</div>
 <h1 id="processTitle">Checking Your Paper</h1>
 <p id="processMessage" role="status" aria-live="polite">Waiting for the current processing status...</p>
 <div class="info-note">You can return to My Papers while the server processes this paper.</div>
 <a class="btn ghost" href="{{ route('assessments.show', $assessment) }}">Paper details and retry</a>
</div>
<script>
(() => {
 const message = document.getElementById('processMessage');
 const title = document.getElementById('processTitle');
 let failures = 0;
 async function poll() {
  try {
   const response = await fetch(@json(route('assessments.status', $assessment)), {
    headers: {'Accept': 'application/json'}, credentials: 'same-origin', cache: 'no-store'
   });
   if ([401, 403, 419].includes(response.status)) {
    title.textContent = 'Session or access changed';
    message.textContent = 'Please sign in again and open this paper from My Papers.';
    return;
   }
   if (!response.ok) throw new Error('Status unavailable');
   const state = await response.json();
   failures = 0;
   if (['review_required', 'finalised', 'reported', 'completed'].includes(state.status)) {
    window.location.assign(state.url);
    return;
   }
   if (state.status === 'failed') {
    title.textContent = 'Paper checking failed';
    message.textContent = state.error || 'Open paper details to retry or upload a fresh document.';
    return;
   }
   message.textContent = state.status === 'processing'
    ? 'Reading your documents and marking the paper...'
    : state.status === 'queued' ? 'Queued. Waiting for the background worker...'
    : 'Open paper details and press Check Paper to begin.';
   if (!['queued', 'processing'].includes(state.status)) return;
  } catch (error) {
   failures++;
   message.textContent = 'Unable to retrieve status. Retrying; you can also open paper details.';
   if (failures >= 10) return;
  }
  setTimeout(poll, 2500);
 }
 poll();
})();
</script>
@endsection
