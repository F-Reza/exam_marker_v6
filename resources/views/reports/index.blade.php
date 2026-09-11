@extends('layouts.app')
@section('title','Reports') @section('page-title','Reports')
@section('content')
<div class="welcome"><div><span class="eyebrow">RESULT LIBRARY</span><h1>Reports</h1><p>View, print and share finalised assessment results.</p></div><a class="btn" href="{{route('assessments.create')}}">＋ Check New Paper</a></div>
<div class="panel table-panel"><div class="panel-head"><h2>Available reports</h2><span class="badge">{{$reports->total()}} reports</span></div>
<div class="table-wrap"><table><thead><tr><th>Assessment</th><th>Student</th><th>Subject</th><th>Score</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($reports as $a)<tr><td><b>{{$a->title}}</b><small>{{$a->created_at->format('d M Y')}}</small></td><td>{{$a->student?->name ?? 'Individual paper'}}</td><td>{{$a->subject}}</td><td>{{$a->percentage!==null?$a->percentage.'%':'—'}}</td><td><span class="badge green">{{ucwords(str_replace('_',' ',$a->status))}}</span></td><td class="actions"><a href="{{route('results.show',$a)}}">View</a>@if(auth()->user()->hasFeature('result_report_download'))<a href="{{route('results.report',$a)}}">Report</a>@endif</td></tr>@empty<tr><td colspan="6" class="empty">No finalised reports yet.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{$reports->links()}}</div></div>
@endsection
