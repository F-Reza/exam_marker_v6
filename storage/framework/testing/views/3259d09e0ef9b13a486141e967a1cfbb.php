<?php $__env->startSection('title','Analytics'); ?>
<?php $__env->startSection('page-title','Analytics'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $hasTopicAnalysis = auth()->user()->hasFeature('topic_analysis');
    $hasGrade = auth()->user()->hasFeature('grade');
    $hasPercentage = auth()->user()->hasFeature('percentage');
?>
<div class="welcome">
    <div>
        <h1>Performance analytics</h1>
        <p><?php echo e($hasTopicAnalysis ? 'Assessment trends and topic-level performance for your account.' : 'Assessment trends and recent performance for your account.'); ?></p>
    </div>
</div>
<div class="stats-grid">
    <article><span class="stat-icon blue">▥</span><div><small>Completed Papers</small><strong><?php echo e($assessments->count()); ?></strong></div></article>
    <article><span class="stat-icon green">%</span><div><small>Average Score</small><strong><?php echo e(round($assessments->avg('percentage')??0,1)); ?>%</strong></div></article>
    <article><span class="stat-icon amber">↗</span><div><small>Highest Score</small><strong><?php echo e(round($assessments->max('percentage')??0,1)); ?>%</strong></div></article>
    <?php if($hasTopicAnalysis): ?>
    <article><span class="stat-icon purple">◎</span><div><small>Topics Tracked</small><strong><?php echo e($topicRows->count()); ?></strong></div></article>
    <?php endif; ?>
</div>
<div class="<?php echo e($hasTopicAnalysis ? 'analytics-grid' : ''); ?>">
    <?php if($hasTopicAnalysis): ?>
    <section class="panel chart-panel">
        <div class="panel-head"><div><h2>Topic performance</h2><p>Average percentage by question topic.</p></div></div>
        <div class="bar-chart">
            <?php $__empty_1 = true; $__currentLoopData = $topicRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bar-item"><span><?php echo e($row['topic']); ?></span><div><i style="height:<?php echo e($row['percentage']); ?>%"></i></div><b><?php echo e($row['percentage']); ?>%</b></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="empty">Complete assessments to see analytics.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    <section class="panel">
        <div class="panel-head"><div><h2>Recent performance</h2><p>Latest completed papers.</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Assessment</th><th>Date</th><th>Score</th><?php if($hasGrade): ?><th>Grade</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $assessments->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><b><?php echo e($a->title); ?></b><small><?php echo e($a->subject); ?></small></td>
                        <td><?php echo e($a->created_at->format('d M Y')); ?></td>
                        <td>
                            <?php if($hasPercentage && $a->percentage !== null): ?>
                            <?php echo e($a->percentage); ?>%
                            <?php elseif($a->results->isNotEmpty()): ?>
                            <?php echo e($a->results->sum(fn($result) => $result->finalMarks())); ?> / <?php echo e($a->results->sum('max_marks') ?: $a->total_marks); ?>

                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <?php if($hasGrade): ?><td><?php echo e($a->grade_awarded); ?></td><?php endif; ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/analytics/index.blade.php ENDPATH**/ ?>