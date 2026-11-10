@extends('layouts.app') @section('title',$assessment->title) @section('page-title','Paper Details') @section('content')
<div class="detail-head"><div><a class="back" href="{{route('assessments.index')}}">← My papers</a><h1>{{$assessment->title}}</h1><p>{{$assessment->subject}} · {{$assessment->grade?:'No grade'}} · {{$assessment->total_marks}} marks</p></div><span class="status {{$assessment->status}}">{{str_replace('_',' ',$assessment->status)}}</span></div>
<div class="document-grid"><article class="panel document-card"><span class="file-icon qp">QP</span><div><h3>Question Paper</h3><p>{{basename($assessment->question_paper_path)}}</p></div><a href="{{route('files.show',[$assessment,'qp'])}}" target="_blank">View</a></article><article class="panel document-card"><span class="file-icon ms">MS</span><div><h3>Mark Scheme</h3><p>{{$assessment->mark_scheme_path?basename($assessment->mark_scheme_path):'Not uploaded'}}</p></div>@if($assessment->mark_scheme_path)<a href="{{route('files.show',[$assessment,'ms'])}}" target="_blank">View</a>@endif</article><article class="panel document-card"><span class="file-icon wa">WA</span><div><h3>Written Answer</h3><p>{{basename($assessment->written_answer_path)}}</p></div><a href="{{route('files.show',[$assessment,'wa'])}}" target="_blank">View</a></article></div>
@if($assessment->attachments()->where('type','insert')->exists())<div class="panel insert-panel"><div class="panel-head"><div><h2>Insert / Source Booklet</h2><p>Separate supporting material used during marking.</p></div></div><div class="insert-files">@foreach($assessment->attachments()->where('type','insert')->get() as $att)<a class="insert-file" target="_blank" href="{{route('files.attachment',[$assessment,$att])}}"><span class="file-icon insert">IN</span><span><b>{{$att->original_name?:'Insert'}}</b><small>Open source material</small></span></a>@endforeach</div></div>@endif
<div class="panel start-card"><div><span class="ai-orb">AI</span><div><h2>Ready to check this paper</h2><p>The system will compare the WA with the QP and {{$assessment->mark_scheme_path?'uploaded MS':'an AI-generated marking structure'}}.</p></div></div>@if(in_array($assessment->status,['uploaded','failed']))<form method="post" action="{{route('assessments.start',$assessment)}}">@csrf<button class="btn">Start Paper Checking</button></form>@elseif($assessment->results()->exists())<a class="btn" href="{{route('results.show',$assessment)}}">View Results</a>@else<span class="status processing">Processing</span>@endif</div>
@if($assessment->status==='failed')
<div class="panel" role="alert"><h2>Paper checking needs attention</h2><p>{{$assessment->error_message}}</p><a href="{{route('assessments.processing',$assessment)}}">View checking details and retry</a></div>
@endif
@if(in_array($assessment->status,['uploaded','failed'],true))
<form class="panel" method="post" enctype="multipart/form-data" action="{{route('assessments.documents',$assessment)}}">@csrf @method('PUT')
<h2>Replace uploaded documents</h2><p>Select only the files you want to replace. This keeps the same paper and paper-check reservation.</p>
<label>Question Paper<input class="input" type="file" name="question_paper" accept=".pdf,.doc,.docx"></label>
<label>Mark Scheme<input class="input" type="file" name="mark_scheme" accept=".pdf,.doc,.docx"></label>
<label><input type="checkbox" name="remove_mark_scheme" value="1"> Remove existing optional mark scheme</label>
<label>Written Answer<input class="input" type="file" name="written_answer" accept=".pdf,.doc,.docx"></label>
<label>Replace all inserts<input class="input" type="file" name="inserts[]" accept=".pdf,.doc,.docx" multiple></label>
<button class="btn">Save replacement documents</button></form>
@endif
@if(auth()->user()->canAccessModule('teacher-assignment'))
<form class="panel" method="post" action="{{route('assessments.assign',$assessment)}}">@csrf @method('PUT')<h2>Teacher allocation</h2>
<select name="assigned_teacher_id" class="input"><option value="">Not allocated</option>@foreach(\App\Models\User::where('owner_user_id',$assessment->user_id)->where('user_type','teacher')->where('account_status','active')->get() as $teacher)<option value="{{$teacher->id}}" @selected($assessment->assigned_teacher_id===$teacher->id)>{{$teacher->name}}</option>@endforeach</select><button class="btn">Update allocation</button></form>
@endif
@endsection
