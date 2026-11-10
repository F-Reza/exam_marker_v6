@extends('layouts.app')
@section('title',$assessment->title)
@section('page-title','Paper Details')
@push('styles')
<link rel="stylesheet" href="{{asset('assets/backend/css/paper-details.css')}}">
@endpush
@section('content')
@php
    $canReplaceFiles=in_array($assessment->status,['uploaded','failed'],true);
    $showAllocation=$canAllocateTeacher && ($eligibleTeachers->isNotEmpty() || $assessment->assigned_teacher_id);
    $documentErrors=collect($errors->getMessages())->filter(fn($messages,$field)=>in_array($field,['question_paper','mark_scheme','written_answer','inserts','remove_mark_scheme','total_marks'],true) || str_starts_with($field,'inserts.'))->flatten();
    $inactiveAssignment=$assessment->assigned_teacher_id && !$eligibleTeachers->contains('id',$assessment->assigned_teacher_id);
    $showFailure=$assessment->status==='failed' && filled($assessment->error_message) && trim((string)session('error'))!==trim($assessment->error_message);
@endphp
@if($showFailure)
<div class="alert danger" role="alert">! {{$assessment->error_message}}</div>
@endif
<div class="detail-head"><div><a class="back" href="{{route('assessments.index')}}">← My papers</a><h1>{{$assessment->title}}</h1><p>{{$assessment->subject}} · {{$assessment->grade?:'No grade'}} · {{$assessment->total_marks}} marks</p></div>
    <div class="paper-detail-head-actions">
        <span class="status {{$assessment->status}}">{{str_replace('_',' ',$assessment->status)}}</span>
        <div class="paper-detail-buttons">
            @if($canReplaceFiles)
            <button class="btn ghost small" type="button" data-paper-dialog="edit-paper-dialog" aria-haspopup="dialog" aria-controls="edit-paper-dialog">Edit paper</button>
            @endif
            @if($showAllocation)
            <button class="btn ghost small" type="button" data-paper-dialog="allocate-teacher-dialog" aria-haspopup="dialog" aria-controls="allocate-teacher-dialog">{{$assessment->assigned_teacher_id?'Change teacher':'Allocate teacher'}}</button>
            @endif
        </div>
    </div>
</div>
<div class="document-grid"><article class="panel document-card"><span class="file-icon qp">QP</span><div><h3>Question Paper</h3><p>{{basename($assessment->question_paper_path)}}</p></div><a href="{{route('files.show',[$assessment,'qp'])}}" target="_blank">View</a></article><article class="panel document-card"><span class="file-icon ms">MS</span><div><h3>Mark Scheme</h3><p>{{$assessment->mark_scheme_path?basename($assessment->mark_scheme_path):'Not uploaded'}}</p></div>@if($assessment->mark_scheme_path)<a href="{{route('files.show',[$assessment,'ms'])}}" target="_blank">View</a>@endif</article><article class="panel document-card"><span class="file-icon wa">WA</span><div><h3>Written Answer</h3><p>{{basename($assessment->written_answer_path)}}</p></div><a href="{{route('files.show',[$assessment,'wa'])}}" target="_blank">View</a></article></div>
@if($assessment->attachments()->where('type','insert')->exists())<div class="panel insert-panel"><div class="panel-head"><div><h2>Insert / Source Booklet</h2><p>Separate supporting material used during marking.</p></div></div><div class="insert-files">@foreach($assessment->attachments()->where('type','insert')->get() as $att)<a class="insert-file" target="_blank" href="{{route('files.attachment',[$assessment,$att])}}"><span class="file-icon insert">IN</span><span><b>{{$att->original_name?:'Insert'}}</b><small>Open source material</small></span></a>@endforeach</div></div>@endif
<div class="panel start-card"><div><span class="ai-orb">AI</span><div><h2>Ready to check this paper</h2><p>The system will compare the WA with the QP and {{$assessment->mark_scheme_path?'uploaded MS':'an AI-generated marking structure'}}.</p></div></div>@if(in_array($assessment->status,['uploaded','failed']))<form method="post" action="{{route('assessments.start',$assessment)}}">@csrf<button class="btn">Start Paper Checking</button></form>@elseif($assessment->results()->exists())<a class="btn" href="{{route('results.show',$assessment)}}">View Results</a>@else<span class="status processing">Processing</span>@endif</div>
@if($canReplaceFiles)
<dialog class="paper-edit-dialog" id="edit-paper-dialog" aria-labelledby="edit-paper-title" @if($documentErrors->isNotEmpty()) data-reopen @endif>
    <div class="paper-dialog-head">
        <h2 id="edit-paper-title">Edit paper</h2>
        <button class="paper-dialog-close" type="button" data-close-dialog aria-label="Close edit paper">&times;</button>
    </div>
            <form class="paper-option-form" method="post" enctype="multipart/form-data" action="{{route('assessments.documents',$assessment)}}">
                @csrf @method('PUT')
                <p class="paper-option-note">Use the total printed on the question paper. Select a file only when it needs replacing. These changes do not use another paper check.</p>
                @if($documentErrors->isNotEmpty())
                <div class="alert danger" role="alert"><b>Please correct:</b><ul>@foreach($documentErrors as $message)<li>{{$message}}</li>@endforeach</ul></div>
                @endif
                <label>Total marks<input class="input" type="number" name="total_marks" min="1" max="1000" step="0.5" value="{{old('total_marks',$assessment->total_marks)}}" required></label>
                <div class="paper-option-grid">
                    <label>Question Paper<input class="input" type="file" name="question_paper" accept=".pdf,.doc,.docx"></label>
                    <label>Written Answer<input class="input" type="file" name="written_answer" accept=".pdf,.doc,.docx"></label>
                    <label>Mark Scheme (optional)<input class="input" type="file" name="mark_scheme" accept=".pdf,.doc,.docx"></label>
                    <label>Replace all inserts<input class="input" type="file" name="inserts[]" accept=".pdf,.doc,.docx" multiple></label>
                </div>
                <label class="paper-option-check"><input type="checkbox" name="remove_mark_scheme" value="1" @checked(old('remove_mark_scheme'))> Remove the existing optional mark scheme</label>
                <div class="paper-option-actions"><button class="btn ghost small" type="button" data-close-dialog>Cancel</button><button class="btn small" type="submit">Save changes</button></div>
            </form>
</dialog>
@endif
@if($showAllocation)
<dialog class="paper-edit-dialog" id="allocate-teacher-dialog" aria-labelledby="allocate-teacher-title" @if($errors->has('assigned_teacher_id')) data-reopen @endif>
    <div class="paper-dialog-head">
        <h2 id="allocate-teacher-title">{{$assessment->assigned_teacher_id?'Change teacher':'Allocate teacher'}}</h2>
        <button class="paper-dialog-close" type="button" data-close-dialog aria-label="Close teacher allocation">&times;</button>
    </div>
            <form class="paper-option-form" method="post" action="{{route('assessments.assign',$assessment)}}">
                @csrf @method('PUT')
                <p class="paper-option-note">Current allocation: {{$assessment->assignedTeacher?->name??'Not allocated'}}{{$inactiveAssignment?' (unavailable)':''}}.</p>
                @error('assigned_teacher_id')<p class="alert danger" role="alert">{{$message}}</p>@enderror
                <label>Teacher
                    <select name="assigned_teacher_id" class="input">
                        <option value="">{{$assessment->assigned_teacher_id?'Remove current allocation':'Not allocated'}}</option>
                        @foreach($eligibleTeachers as $teacher)
                        <option value="{{$teacher->id}}" @selected((string)old('assigned_teacher_id',$assessment->assigned_teacher_id)===(string)$teacher->id)>{{$teacher->name}}</option>
                        @endforeach
                    </select>
                </label>
                <div class="paper-option-actions"><button class="btn ghost small" type="button" data-close-dialog>Cancel</button><button class="btn small" type="submit">Save allocation</button></div>
            </form>
</dialog>
@endif
@endsection
@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-paper-dialog]').forEach(button => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.paperDialog);
            if (dialog && !dialog.open) dialog.showModal();
        });
    });
    document.querySelectorAll('.paper-edit-dialog').forEach(dialog => {
        dialog.querySelectorAll('[data-close-dialog]').forEach(button => {
            button.addEventListener('click', () => dialog.close());
        });
        if (dialog.hasAttribute('data-reopen') && !dialog.open) dialog.showModal();
    });
})();
</script>
@endpush
