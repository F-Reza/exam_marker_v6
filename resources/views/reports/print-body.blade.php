<h1>Exam Marker Result Report</h1>
<p><b>Assessment:</b> {{$assessment->title}}<br><b>Student:</b> {{$assessment->student?->name??'Individual User'}}<br><b>Subject:</b> {{$assessment->subject}}<br><b>Grade/Class:</b> {{$assessment->grade?:'-'}}</p>
<p><b>Total marks:</b> {{$obtained}} / {{$maximum}} @if($showPercentage)<br><b>Percentage:</b> {{$percentage}}%@endif @if($showGrade)<br><b>Grade:</b> {{$grade}}@endif</p>
<h2>Question-wise marks and feedback</h2>
@foreach($rows as $row)
<div class="question-result"><h3>Question {{$row['label']}} - {{$row['marks']}} / {{$row['maximum']}}</h3><p>{!!nl2br(e($row['feedback']??''))!!}</p>
@foreach($row['rubric'] as $criterion=>$details)<p><b>{{ucwords(str_replace('_',' ',$criterion))}}:</b> {{$details['awarded']??'-'}} / {{$details['max_marks']??'-'}} - {{$details['feedback']??''}}</p>@endforeach</div>
@endforeach
<h2>Overall feedback</h2>
@foreach(['strengths'=>'Strengths','weaknesses'=>'Areas to improve'] as $key=>$label)<h3>{{$label}}</h3><ul>@foreach($assessment->ai_summary[$key]??[] as $item)<li>{{$item}}</li>@endforeach</ul>@endforeach
@if(!empty($assessment->ai_summary['notice']))<p>{{$assessment->ai_summary['notice']}}</p>@endif
<p>AI suggestions require examiner review. Published reports reflect the latest approved teacher marks and feedback.</p>
