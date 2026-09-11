@extends('layouts.app') @section('title','Dashboard') @section('page-title','Dashboard') @section('content')
<div class="welcome">
    <div>
        <h1>Welcome, {{explode(' ',auth()->user()->name)[0]}}!</h1>
        <p>Here is what is happening with your account today.</p>
    </div><a class="btn" href="{{route('assessments.create')}}">＋ Check New Paper</a>
</div>
<div class="stats-grid">
    <article><span class="stat-icon blue">▤</span>
        <div><small>Total Papers</small><strong>{{$total}}</strong></div>
    </article>
    <article><span class="stat-icon green">✓</span>
        <div><small>Completed</small><strong>{{$completed}}</strong></div>
    </article>
    <article><span class="stat-icon amber">◷</span>
        <div><small>Processing</small><strong>{{$processing}}</strong></div>
    </article>
    <article><span class="stat-icon purple">♙</span>
        <div><small>Total Students</small><strong>{{$students}}</strong></div>
    </article>
</div>
<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Recent Papers</h2>
                <p>Your latest uploaded and checked papers.</p>
            </div><a href="{{route('assessments.index')}}">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Paper</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>@forelse($assessments as $a)<tr>
                        <td><b>{{$a->title}}</b><small>{{$a->subject}}</small></td>
                        <td>{{$a->student?->name??'Individual'}}</td>
                        <td><span class="status {{$a->status}}">{{str_replace('_',' ',$a->status)}}</span></td>
                        <td>{{$a->percentage!==null?$a->percentage.'%':'—'}}</td>
                        <td><a class="table-link"
                                href="{{route($a->results()->exists()?'results.show':'assessments.show',$a)}}">View
                                →</a></td>
                    </tr>@empty<tr>
                        <td colspan="5" class="empty">No papers yet. Upload your first QP, MS and WA.</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>
    </section>
    <section class="panel performance">
        <div class="panel-head">
            <div>
                <h2>Performance Overview</h2>
                <p>Current account average.</p>
            </div>
        </div>
        <div class="big-ring" style="--value:{{$average}}"><span>{{$average}}%</span></div>
        <div class="legend"><span><i class="good"></i>Strong 80–100%</span><span><i class="mid"></i>Developing
                50–79%</span><span><i class="low"></i>Needs support</span></div>
    </section>
</div>
@endsection