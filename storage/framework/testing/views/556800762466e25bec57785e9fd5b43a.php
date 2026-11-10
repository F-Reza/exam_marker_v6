<?php $__env->startSection('title', 'Result - ' . $assessment->title); ?>

<?php $__env->startSection('page-title', 'Paper Result'); ?>


<?php $__env->startSection('content'); ?>

<?php

$obt = $assessment->results->sum(
    fn($r) => (float)($r->teacher_marks ?? $r->ai_marks)
);

$max = $assessment->results->sum('max_marks');

$resultUser = auth()->user();
$isFinalised = in_array($assessment->status, ['finalised', 'reported'], true);
$canEdit = $resultUser->canAccessModule('result-edit') && !$isFinalised;
$canMarkEdit = $resultUser->hasFeature('manual_mark_editing');
$canFeedbackEdit = $resultUser->hasFeature('feedback_editing');
$canFinalise = $resultUser->canAccessModule('result-finalise');
$hasReportDownload = $resultUser->canAccessModule('reports');
$hasCorrectedPaper = $resultUser->canAccessModule('corrected-paper');
$hasQuestionRecheck = $resultUser->canAccessModule('result-recheck') && !$isFinalised;



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


?>



<div class="result-head">

<div>

<a class="back" href="<?php echo e(route('assessments.index')); ?>">
← My papers
</a>


<h1>
<?php echo e($assessment->title); ?>

</h1>


<p>
<?php echo e($assessment->student?->name ?? auth()->user()->name); ?>

·
<?php echo e($assessment->subject); ?>

</p>


</div>



<div class="result-actions">


<?php if($hasReportDownload): ?>

<a class="btn ghost"
href="<?php echo e(route('results.report',$assessment)); ?>"
target="_blank">
Preview Report
</a>


<a class="btn ghost"
href="<?php echo e(route('results.report.download',$assessment)); ?>">
Download Report
</a>

<?php endif; ?>



<?php if($hasCorrectedPaper): ?>

<a class="btn ghost"
href="<?php echo e(route('results.corrected',$assessment)); ?>">
Corrected Paper
</a>

<?php endif; ?>



<?php if($canFinalise): ?>

<form method="post"
action="<?php echo e(route($isFinalised ? 'results.unfinalise' : 'results.finalise',$assessment)); ?>">

<?php echo csrf_field(); ?>

<button class="btn">
<?php echo e($isFinalised ? 'Reopen for Review' : 'Finalise Result'); ?>

</button>

</form>

<?php endif; ?>


</div>

</div>





<div class="result-stats">


<article>
<small>Total Marks</small>
<strong><?php echo e($max); ?></strong>
</article>


<article>
<small>Obtained Marks</small>
<strong><?php echo e($obt); ?></strong>
</article>


<?php if($resultUser->hasFeature('percentage')): ?>
<article>
<small>Percentage</small>
<strong>
<?php echo e($assessment->percentage ?? round($obt/max(1,$max)*100,1)); ?>%
</strong>
</article>
<?php endif; ?>


<?php if($resultUser->hasFeature('grade')): ?>
<article>
<small>Grade</small>
<strong>
<?php echo e($assessment->grade_awarded ?? '—'); ?>

</strong>
</article>
<?php endif; ?>


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



<span class="badge <?php echo e($assessment->status==='finalised'?'green':''); ?>">

<?php echo e(ucwords(str_replace('_',' ',$assessment->status))); ?>


</span>


</div>





<?php if($groups->contains(fn($g)=>$g->count()>1)): ?>


<div class="subquestion-summary">

<b>
Cumulative score by main question
</b>


<div class="subquestion-chips">


<?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $main=>$items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


<span>

<strong>
Q<?php echo e($main); ?>

</strong>


<?php echo e($items->sum(fn($r)=>(float)($r->teacher_marks ?? $r->ai_marks))); ?>


/

<?php echo e($items->sum(fn($r)=>(float)$r->max_marks)); ?>



</span>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


</div>


</div>


<?php endif; ?>






<div class="question-list">


<?php $__currentLoopData = $sortedResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


<article class="question-row">



<div class="q-number">

<?php echo e($r->question_part 
? $r->question_number.'('.$r->question_part.')'
: $r->question_number); ?>


</div>



<div class="q-main">


<div class="q-top">


<b>

<?php echo e($r->teacher_marks ?? $r->ai_marks); ?>


/

<?php echo e($r->max_marks); ?>


</b>


<span class="confidence <?php echo e($r->confidence); ?>">

<?php echo e($r->confidence); ?> confidence

</span>


</div>



<p>
<?php echo e($r->feedback); ?>

</p>



<?php if($r->teacher_comment): ?>

<div class="teacher-note">

Teacher:
<?php echo e($r->teacher_comment); ?>


</div>

<?php endif; ?>


</div>





<?php if($canEdit): ?>
<form method="post" action="<?php echo e(route('results.update',$r)); ?>" class="mark-form">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PATCH'); ?>
    <?php if($canMarkEdit): ?>
    <label>
        Final mark
        <input class="input" type="number" step="0.5" min="0" max="<?php echo e($r->max_marks); ?>" name="teacher_marks" value="<?php echo e($r->teacher_marks ?? $r->ai_marks); ?>">
    </label>
    <?php endif; ?>
    <?php if($canFeedbackEdit): ?>
    <label>
        Teacher comment
        <input class="input" name="teacher_comment" value="<?php echo e($r->teacher_comment); ?>">
    </label>
    <?php endif; ?>
    <button class="btn small">Save</button>
</form>
<?php endif; ?>

<?php if($hasQuestionRecheck): ?>
<form method="post" action="<?php echo e(route('results.recheck-question',$r)); ?>">
    <?php echo csrf_field(); ?>
    <button class="btn small ghost">Recheck <?php echo e($r->question_part ? $r->question_number.'('.$r->question_part.')' : $r->question_number); ?></button>
</form>
<?php endif; ?>

</article>



<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


</div>



</section>





<aside>


<div class="panel summary-card">


<h3>
AI Feedback Summary
</h3>



<?php if($assessment->ai_summary): ?>


<h4>
Strengths
</h4>


<ul>

<?php $__currentLoopData = $assessment->ai_summary['strengths'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

<li class="positive">
✓ <?php echo e($s); ?>

</li>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</ul>



<h4>
Areas to improve
</h4>


<ul>

<?php $__currentLoopData = $assessment->ai_summary['weaknesses'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

<li class="negative">
• <?php echo e($s); ?>

</li>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</ul>



<div class="info-note">

<?php echo e($assessment->ai_summary['notice'] ?? ''); ?>


</div>


<?php endif; ?>


</div>



</aside>


</div>



<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/results/show.blade.php ENDPATH**/ ?>