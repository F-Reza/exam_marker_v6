@extends('layouts.app')
@section('title','Help & Support') @section('page-title','Help & Support')
@section('content')
<div class="welcome">
    <div><span class="eyebrow">SUPPORT CENTRE</span>
        <h1>Help & Support</h1>
        <p>Find answers or send a request to the support team.</p>
    </div>
</div>
<div class="help-grid">
    <section class="panel form-card">
        <h2>Create support ticket</h2>
        <form method="post" action="{{route('help.store')}}" class="form-grid">@csrf<label class="span-2">Subject<input
                    class="input" name="subject" required></label><label>Category<select class="input" name="category">
                    <option>general</option>
                    <option>paper processing</option>
                    <option>billing</option>
                    <option>account</option>
                </select></label><label>Priority<select class="input" name="priority">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                </select></label><label class="span-2">Message<textarea class="input" rows="6" name="message"
                    required></textarea></label>
            <div class="span-2"><button class="btn">Submit Ticket</button></div>
        </form>
    </section>
    <section>
        <div class="panel faq-card">
            <h2>Quick answers</h2>
            <details open>
                <summary>Why is MS optional?</summary>
                <p>The demo marker can create a suggested marking structure, but an official mark scheme provides better
                    accuracy.</p>
            </details>
            <details>
                <summary>Can I edit AI marks?</summary>
                <p>Manual mark editing is available in Mode 2 and Mode 3.</p>
            </details>
            <details>
                <summary>Why can’t I download the corrected paper?</summary>
                <p>Corrected-paper download is a Mode 3 feature.</p>
            </details>
            <details>
                <summary>How are parent links protected?</summary>
                <p>They use expiring secure report tokens.</p>
            </details>
        </div>
    </section>
</div>
<div class="panel table-panel">
    <div class="panel-head">
        <h2>Your tickets</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>@forelse($tickets as $t)<tr>
                    <td><b>{{$t->subject}}</b><small>{{$t->message}}</small></td>
                    <td>{{$t->category}}</td>
                    <td>{{ucfirst($t->priority)}}</td>
                    <td><span class="badge">{{ucfirst($t->status)}}</span></td>
                    <td>{{$t->updated_at->diffForHumans()}}</td>
                </tr>@empty<tr>
                    <td colspan="5" class="empty">No support tickets.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
@endsection