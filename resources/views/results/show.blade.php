@extends('layouts.app')

@section('title', 'Result - ' . $assessment->title)

@section('page-title', 'Paper Result')


@section('content')

@php

$obt = $assessment->results->sum(
    fn($r) => (float)($r->teacher_marks ?? $r->ai_marks)
);

$max = $assessment->results->sum('max_marks');

$canEdit = auth()->user()->hasFeature('manual_mark_editing');
$hasReportDownload = auth()->user()->hasFeature('result_report_download');
$hasCorrectedPaper = auth()->user()->hasFeature('corrected_paper_download');
$hasParentEmail = auth()->user()->hasFeature('parent_email');
$hasParentSms = auth()->user()->hasFeature('parent_sms');
$hasQuestionRecheck = auth()->user()->hasFeature('question_recheck');



/*
|--------------------------------------------------------------------------
| Sort questions correctly
|--------------------------------------------------------------------------
*/

$sortedResults = $assessment->results->sort(function($a,$b){

    preg_match('/\d+/', $a->question_number ?? '', $ma);
    preg_match('/\d+/', $b->question_number ?? '', $mb);


    $qa = (int)($ma[0] ?? 0);
    $qb = (int)($mb[0] ?? 0);


    if($qa == $qb){

        return strcmp(
            $a->question_part ?? '',
            $b->question_part ?? ''
        );

    }


    return $qa <=> $qb;

});



$groups = $sortedResults->groupBy(function($r){

    preg_match(
        '/^(\d+)/',
        $r->question_number ?? '',
        $m
    );


    return $m[1] ?? $r->question_number;

});


@endphp



<div class="result-head">

<div>

<a class="back" href="{{ route('assessments.index') }}">
← My papers
</a>


<h1>
{{ $assessment->title }}
</h1>


<p>
{{ $assessment->student?->name ?? auth()->user()->name }}
·
{{ $assessment->subject }}
</p>


</div>



<div class="result-actions">


@if($hasReportDownload)

<a class="btn ghost"
href="{{ route('results.report',$assessment) }}"
target="_blank">
Preview Report
</a>


<a class="btn ghost"
href="{{ route('results.report.download',$assessment) }}">
Download Report
</a>

@endif



@if($hasCorrectedPaper)

<a class="btn ghost"
href="{{ route('results.corrected',$assessment) }}">
Corrected Paper
</a>

@endif



@if($canEdit)

<form method="post"
action="{{ route('results.finalise',$assessment) }}">

@csrf

<button class="btn">
Finalise Result
</button>

</form>

@endif


</div>

</div>





<div class="result-stats">


<article>
<small>Total Marks</small>
<strong>{{ $max }}</strong>
</article>


<article>
<small>Obtained Marks</small>
<strong>{{ $obt }}</strong>
</article>


<article>
<small>Percentage</small>
<strong>
{{ $assessment->percentage ?? round($obt/max(1,$max)*100,1) }}%
</strong>
</article>


<article>
<small>Grade</small>
<strong>
{{ $assessment->grade_awarded ?? '—' }}
</strong>
</article>


</div>





<div class="result-layout">


<section class="panel">


<div class="panel-head">

<div>

<h2>
Question-wise Results
</h2>


<p class="muted">
Sub-question marks are displayed separately:
Q1(a), Q1(b), Q1(c)
</p>


<p>
AI suggested marks with teacher review controls.
</p>


</div>



<span class="badge {{ $assessment->status==='finalised'?'green':'' }}">

{{ ucwords(str_replace('_',' ',$assessment->status)) }}

</span>


</div>





@if($groups->contains(fn($g)=>$g->count()>1))


<div class="subquestion-summary">

<b>
Cumulative score by main question
</b>


<div class="subquestion-chips">


@foreach($groups as $main=>$items)


<span>

<strong>
Q{{ $main }}
</strong>


{{ $items->sum(fn($r)=>(float)($r->teacher_marks ?? $r->ai_marks)) }}

/

{{ $items->sum(fn($r)=>(float)$r->max_marks) }}


</span>


@endforeach


</div>


</div>


@endif






<div class="question-list">


@foreach($sortedResults as $r)


<article class="question-row">



<div class="q-number">

{{ 
$r->question_part 
? $r->question_number.'('.$r->question_part.')'
: $r->question_number
}}

</div>



<div class="q-main">


<div class="q-top">


<b>

{{ $r->teacher_marks ?? $r->ai_marks }}

/

{{ $r->max_marks }}

</b>


<span class="confidence {{ $r->confidence }}">

{{ $r->confidence }} confidence

</span>


</div>



<p>
{{ $r->feedback }}
</p>



@if($r->teacher_comment)

<div class="teacher-note">

Teacher:
{{ $r->teacher_comment }}

</div>

@endif


</div>





@if($canEdit)


<form method="post"
action="{{ route('results.update',$r) }}"
class="mark-form">


@csrf

@method('PATCH')



<label>

Final mark

<input

class="input"

type="number"

step="0.5"

min="0"

max="{{ $r->max_marks }}"

name="teacher_marks"

value="{{ $r->teacher_marks ?? $r->ai_marks }}"

/>

</label>




<label>

Teacher comment


<input

class="input"

name="teacher_comment"

value="{{ $r->teacher_comment }}"

/>


</label>




<button class="btn small">

Save

</button>


</form>





@if($hasQuestionRecheck)


<form method="post"
action="{{ route('results.recheck-question',$r) }}">

@csrf


<button class="btn small ghost">

Recheck

{{ 
$r->question_part
? $r->question_number.'('.$r->question_part.')'
: $r->question_number
}}


</button>


</form>


@endif


@endif



</article>



@endforeach


</div>



</section>





<aside>


<div class="panel summary-card">


<h3>
AI Feedback Summary
</h3>



@if($assessment->ai_summary)


<h4>
Strengths
</h4>


<ul>

@foreach($assessment->ai_summary['strengths'] ?? [] as $s)

<li class="positive">
✓ {{ $s }}
</li>

@endforeach

</ul>



<h4>
Areas to improve
</h4>


<ul>

@foreach($assessment->ai_summary['weaknesses'] ?? [] as $s)

<li class="negative">
• {{ $s }}
</li>

@endforeach

</ul>



<div class="info-note">

{{ $assessment->ai_summary['notice'] ?? '' }}

</div>


@endif


</div>



</aside>


</div>



@endsection