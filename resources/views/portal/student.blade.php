@extends('layouts.app')
@section('title','Student Portal') @section('page-title','Student Portal')
@section('content')
<div class="page-head"><div><h1>My Results</h1><p>View your checked papers, marks and teacher feedback.</p></div></div>
@if(!$student)<div class="empty-state"><h3>Your student profile is not linked yet</h3><p>Ask your teacher or coaching administrator to link this login to your student record.</p></div>
@else
<div class="stats-grid"><div class="stat-card"><span>Student</span><strong>{{$student->name}}</strong><small>{{$student->grade}}</small></div><div class="stat-card"><span>Roll No.</span><strong>{{$student->roll_number??'—'}}</strong></div><div class="stat-card"><span>Papers</span><strong>{{$assessments->total()}}</strong></div></div>
<div class="card"><h3>Assessment History</h3><div class="table-wrap"><table><thead><tr><th>Paper</th><th>Subject</th><th>Status</th><th>Marks</th><th>Action</th></tr></thead><tbody>@forelse($assessments as $a)<tr><td>{{$a->title}}</td><td>{{$a->subject}}</td><td><span class="status-pill">{{$a->status}}</span></td><td>{{$a->percentage!==null?$a->percentage.'%':'Pending'}}</td><td>@if($a->status==='finalised')<a class="btn small" href="{{route('results.show',$a)}}">View result</a>@else — @endif</td></tr>@empty<tr><td colspan="5">No papers are available yet.</td></tr>@endforelse</tbody></table></div></div>
@endif
@endsection
