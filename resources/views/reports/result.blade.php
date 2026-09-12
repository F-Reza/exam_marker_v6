<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Result Report</title>

<style>
body{
    font-family:Arial,sans-serif;
    color:#16213d;
    margin:35px
}

.head{
    display:flex;
    justify-content:space-between;
    border-bottom:3px solid #1958d4;
    padding-bottom:18px
}

.brand{
    font-size:24px;
    font-weight:800;
    color:#1958d4
}

.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    margin:22px 0
}

.stats div{
    border:1px solid #dbe4f2;
    border-radius:10px;
    padding:16px;
    text-align:center
}

.stats b{
    display:block;
    font-size:24px
}

.stats small{
    color:#64748b
}

table{
    width:100%;
    border-collapse:collapse
}

th,td{
    padding:11px;
    border-bottom:1px solid #dbe4f2;
    text-align:left
}

th{
    background:#eff5ff
}

.note{
    margin-top:20px;
    background:#f3f7ff;
    padding:15px;
    border-radius:10px
}

@media print{
    button{
        display:none
    }
}

</style>

</head>

<body>


<div class="head">

<div>

<div class="brand">
✓ Exam Marker
</div>

<p>
AI-assisted result report
</p>

</div>


<div>

<b>
{{$assessment->title}}
</b>

<br>

{{$assessment->subject}}

·

{{$assessment->assessment_date?->format('d M Y')}}

</div>

</div>



@php

$obt =
$assessment->results->sum(
    fn($r)=>
    (float)(
        $r->teacher_marks
        ??
        $r->ai_marks
        ??
        0
    )
);


$max =
$assessment->results->sum('max_marks');

@endphp



<div class="stats">


<div>

<small>
Student
</small>

<b style="font-size:16px">

{{$assessment->student?->name ?? $assessment->user->name}}

</b>

</div>



<div>

<small>
Marks
</small>

<b>

{{$obt}} / {{$max}}

</b>

</div>



<div>

<small>
Percentage
</small>

<b>

{{$assessment->percentage}}%

</b>

</div>



<div>

<small>
Grade
</small>

<b>

{{$assessment->grade_awarded}}

</b>

</div>


</div>





<table>


<thead>

<tr>

<th>
Question
</th>


<th>
Maximum
</th>


<th>
Awarded
</th>


<th>
Feedback
</th>


</tr>

</thead>



<tbody>


@foreach($assessment->results as $r)


<tr>


<td>

@if($r->question_part)

{{ $r->question_number }}({{ $r->question_part }})

@else

{{ $r->question_number }}

@endif


</td>



<td>

{{$r->max_marks}}

</td>



<td>

{{$r->teacher_marks ?? $r->ai_marks}}

</td>



<td>

{{$r->teacher_comment ?: $r->feedback}}

</td>



</tr>


@endforeach



</tbody>


</table>




<div class="note">

<b>
Important:
</b>

AI-generated marking suggestions should be reviewed by a qualified teacher before high-stakes use.

</div>



<p>

<button onclick="window.print()">

Print / Save as PDF

</button>

</p>



</body>

</html>