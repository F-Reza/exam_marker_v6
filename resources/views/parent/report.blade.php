<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Student Report</title>@vite(['resources/css/app.css'])
</head>

<body class="parent-report-page">@php
    $obt=$assessment->results->sum(fn($r)=>(float)($r->teacher_marks??$r->ai_marks));$max=$assessment->results->sum('max_marks');@endphp
    <header class="public-nav"><a class="brand"><span class="brand-icon">✓</span><span><b>Exam Marker</b><small>Secure
                    Parent Report</small></span></a>
        <div class="actions"><a class="btn small ghost"
                href="{{route('parent.report.pdf',$assessment->public_report_token)}}">Download PDF</a><span
                class="badge green">Verified report</span></div>
    </header>
    <main class="parent-report-wrap">
        <section class="panel parent-hero">
            <div><span>Student Name</span>
                <h1>{{$assessment->student?->name??'Student'}}</h1>
                <p>{{$assessment->title}} · {{$assessment->subject}} · {{$assessment->grade}}</p>
            </div>
            <div><small>Finalised</small><b>{{$assessment->finalised_at?->format('d M
                    Y')??$assessment->updated_at->format('d M Y')}}</b></div>
        </section>
        <div class="result-stats">
            <article><small>Total Marks</small><strong>{{$max}}</strong></article>
            <article><small>Obtained Marks</small><strong>{{$obt}}</strong></article>
            <article><small>Percentage</small><strong>{{$assessment->percentage}}%</strong></article>
            <article><small>Grade</small><strong>{{$assessment->grade_awarded}}</strong></article>
        </div>
        <div class="result-layout">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Question-wise summary</h2>
                        <p>Final marks after teacher review.</p>
                    </div>
                </div>
                <div class="question-list">@foreach($assessment->results as $r)<article class="question-row">
                        <div class="q-number">{{$r->question_number}}</div>
                        <div class="q-main">
                            <div class="q-top"><b>{{$r->teacher_marks??$r->ai_marks}} / {{$r->max_marks}}</b><span
                                    class="confidence {{$r->confidence}}">{{$r->topic??'General'}}</span></div>
                            <p>{{$r->teacher_comment?:$r->feedback}}</p>
                        </div>
                    </article>@endforeach</div>
            </section>
            <aside>
                <div class="panel summary-card">
                    <h3>Overall feedback</h3>
                    <h4>Strengths</h4>
                    <ul>@foreach($assessment->ai_summary['strengths']??[] as $s)<li class="positive">✓ {{$s}}</li>
                        @endforeach</ul>
                    <h4>Areas to improve</h4>
                    <ul>@foreach($assessment->ai_summary['weaknesses']??[] as $s)<li class="negative">• {{$s}}</li>
                        @endforeach</ul>
                </div>
                <div class="info-note">This secure report link expires automatically. Contact the coaching class for any
                    clarification.</div>
            </aside>
        </div>
    </main>
</body>

</html>