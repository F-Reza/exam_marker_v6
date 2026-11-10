@extends('layouts.app')
@section('title','Analytics')
@section('page-title','Analytics')
@section('content')
@php
    $hasTopicAnalysis = auth()->user()->hasFeature('topic_analysis');
    $hasGrade = auth()->user()->hasFeature('grade');
    $hasPercentage = auth()->user()->hasFeature('percentage');
@endphp
<div class="welcome">
    <div>
        <h1>Performance analytics</h1>
        <p>{{$hasTopicAnalysis ? 'Assessment trends and topic-level performance for your account.' : 'Assessment trends and recent performance for your account.'}}</p>
    </div>
</div>
<div class="stats-grid">
    <article><span class="stat-icon blue">▥</span><div><small>Completed Papers</small><strong>{{$assessments->count()}}</strong></div></article>
    <article><span class="stat-icon green">%</span><div><small>Average Score</small><strong>{{round($assessments->avg('percentage')??0,1)}}%</strong></div></article>
    <article><span class="stat-icon amber">↗</span><div><small>Highest Score</small><strong>{{round($assessments->max('percentage')??0,1)}}%</strong></div></article>
    @if($hasTopicAnalysis)
    <article><span class="stat-icon purple">◎</span><div><small>Topics Tracked</small><strong>{{$topicRows->count()}}</strong></div></article>
    @endif
</div>
<div class="{{$hasTopicAnalysis ? 'analytics-grid' : ''}}">
    @if($hasTopicAnalysis)
    <section class="panel chart-panel">
        <div class="panel-head"><div><h2>Topic performance</h2><p>Average percentage by question topic.</p></div></div>
        <div class="bar-chart">
            @forelse($topicRows as $row)
            <div class="bar-item"><span>{{$row['topic']}}</span><div><i style="height:{{$row['percentage']}}%"></i></div><b>{{$row['percentage']}}%</b></div>
            @empty
            <p class="empty">Complete assessments to see analytics.</p>
            @endforelse
        </div>
    </section>
    @endif
    <section class="panel">
        <div class="panel-head"><div><h2>Recent performance</h2><p>Latest completed papers.</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Assessment</th><th>Date</th><th>Score</th>@if($hasGrade)<th>Grade</th>@endif</tr></thead>
                <tbody>
                    @foreach($assessments->take(10) as $a)
                    <tr>
                        <td><b>{{$a->title}}</b><small>{{$a->subject}}</small></td>
                        <td>{{$a->created_at->format('d M Y')}}</td>
                        <td>
                            @if($hasPercentage && $a->percentage !== null)
                            {{$a->percentage}}%
                            @elseif($a->results->isNotEmpty())
                            {{$a->results->sum(fn($result) => $result->finalMarks())}} / {{$a->results->sum('max_marks') ?: $a->total_marks}}
                            @else
                            —
                            @endif
                        </td>
                        @if($hasGrade)<td>{{$a->grade_awarded}}</td>@endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
