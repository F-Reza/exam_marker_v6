@extends('layouts.app')
@section('title','Teacher Workspace') @section('page-title','Teacher Workspace')
@section('content')
<div class="page-head">
    <div>
        <h1>{{auth()->user()->owner_user_id ? 'Assigned Papers' : 'My Papers'}}</h1>
        <p>Open a paper to view its files and checking results.</p>
    </div>
    @if(auth()->user()->canAccessModule('assessments'))
    <a class="btn" href="{{route('assessments.create')}}">Check New Paper</a>
    @endif
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>@forelse($assessments as $a)<tr>
                    <td>{{$a->title}}<small>{{$a->subject}}</small></td>
                    <td>{{$a->student?->name??'Individual paper'}}</td>
                    <td><span class="status-pill">{{$a->status}}</span></td>
                    <td>{{$a->created_at->format('d M Y')}}</td>
                    <td><a class="btn small" href="{{route('assessments.show',$a)}}">Open</a></td>
                </tr>@empty<tr>
                    <td colspan="5">No papers are available yet.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
@endsection
