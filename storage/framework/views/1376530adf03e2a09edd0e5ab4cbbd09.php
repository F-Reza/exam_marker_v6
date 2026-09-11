<?php $__env->startSection('title', 'Result - ' . $assessment->title); ?>
<?php $__env->startSection('page-title', 'Paper Result'); ?>
<?php $__env->startSection('content'); ?>

<?php
    $obt = $assessment->results->sum(fn($r) => (float)($r->teacher_marks ?? $r->ai_marks));
    $max = $assessment->results->sum('max_marks');
    $canEdit = auth()->user()->hasFeature('manual_mark_editing');
    $hasReportDownload = auth()->user()->hasFeature('result_report_download');
    $hasCorrectedPaper = auth()->user()->hasFeature('corrected_paper_download');
    $hasParentEmail = auth()->user()->hasFeature('parent_email');
    $hasParentSms = auth()->user()->hasFeature('parent_sms');
    $hasQuestionRecheck = auth()->user()->hasFeature('question_recheck');
?>

<div class="result-head">
    <div>
        <a class="back" href="<?php echo e(route('assessments.index')); ?>">← My papers</a>
        <h1><?php echo e($assessment->title); ?></h1>
        <p><?php echo e($assessment->student?->name ?? auth()->user()->name); ?> · <?php echo e($assessment->subject); ?></p>
    </div>
    <div class="result-actions">
        <?php if($hasReportDownload): ?>
            <a class="btn ghost" href="<?php echo e(route('results.report', $assessment)); ?>" target="_blank">Preview Report</a>
            <a class="btn ghost" href="<?php echo e(route('results.report.download', $assessment)); ?>">Download Report</a>
        <?php endif; ?>
        
        <?php if($hasCorrectedPaper): ?>
            <a class="btn ghost" href="<?php echo e(route('results.corrected', $assessment)); ?>">Corrected Paper</a>
        <?php endif; ?>
        
        <?php if($canEdit): ?>
            <form method="post" action="<?php echo e(route('results.finalise', $assessment)); ?>">
                <?php echo csrf_field(); ?>
                <button class="btn">Finalise Result</button>
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
    <article>
        <small>Percentage</small>
        <strong><?php echo e($assessment->percentage ?? round($obt / max(1, $max) * 100, 1)); ?>%</strong>
    </article>
    <article>
        <small>Grade</small>
        <strong><?php echo e($assessment->grade_awarded ?? '—'); ?></strong>
    </article>
</div>

<div class="result-layout">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Question-wise Results</h2>
                <p class="muted">Sub-question marks are displayed separately where available (for example Q1(a), Q1(b), Q1(c)).</p>
                <p>AI-suggested marks with teacher review controls.</p>
            </div>
            <span class="badge <?php echo e($assessment->status === 'finalised' ? 'green' : ''); ?>">
                <?php echo e(ucwords(str_replace('_', ' ', $assessment->status))); ?>

            </span>
        </div>

        <?php
            $groups = $assessment->results->groupBy(function($r) {
                preg_match('/^(\d+)/', $r->question_number, $m);
                return $m[1] ?? $r->question_number;
            });
        ?>

        <?php if($groups->contains(fn($g) => $g->count() > 1)): ?>
            <div class="subquestion-summary">
                <b>Cumulative score by main question</b>
                <div class="subquestion-chips">
                    <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $main => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span>
                            <strong>Q<?php echo e($main); ?></strong>
                            <?php echo e($items->sum(fn($r) => (float)($r->teacher_marks ?? $r->ai_marks))); ?> /
                            <?php echo e($items->sum(fn($r) => (float)$r->max_marks)); ?>

                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <small>Sub-questions such as 1(a), 1(b) are marked separately and automatically added to the cumulative score for Question 1.</small>
            </div>
        <?php endif; ?>

        <div class="question-list">
            <?php $__currentLoopData = $assessment->results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="question-row">
                    <div class="q-number"><?php echo e($r->question_part ? $r->question_number.'('.$r->question_part.')' : $r->question_number); ?></div>
                    <div class="q-main">
                        <div class="q-top">
                            <b><?php echo e($r->teacher_marks ?? $r->ai_marks); ?> / <?php echo e($r->max_marks); ?></b>
                            <span class="confidence <?php echo e($r->confidence); ?>"><?php echo e($r->confidence); ?> confidence</span>
                        </div>
                        <p><?php echo e($r->feedback); ?></p>
                        <?php if($r->teacher_comment): ?>
                            <div class="teacher-note">Teacher: <?php echo e($r->teacher_comment); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if($canEdit): ?>
                        <form method="post" action="<?php echo e(route('results.update', $r)); ?>" class="mark-form">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <label>Final mark
                                <input class="input" type="number" step="0.5" min="0" max="<?php echo e($r->max_marks); ?>" name="teacher_marks" value="<?php echo e($r->teacher_marks ?? $r->ai_marks); ?>">
                            </label>
                            <label>Teacher comment
                                <input class="input" name="teacher_comment" value="<?php echo e($r->teacher_comment); ?>" placeholder="Optional comment">
                            </label>
                            <button class="btn small">Save</button>
                        </form>

                        <?php if($hasQuestionRecheck): ?>
                            <form method="post" action="<?php echo e(route('results.recheck-question', $r)); ?>">
                                <?php echo csrf_field(); ?>
                                <button class="btn small ghost">Recheck <?php echo e($r->question_part ? $r->question_number.'('.$r->question_part.')' : $r->question_number); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <aside>
        <div class="panel summary-card">
            <h3>AI Feedback Summary</h3>
            <?php if($assessment->ai_summary): ?>
                <h4>Strengths</h4>
                <ul>
                    <?php $__currentLoopData = $assessment->ai_summary['strengths'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="positive">✓ <?php echo e($s); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <h4>Areas to improve</h4>
                <ul>
                    <?php $__currentLoopData = $assessment->ai_summary['weaknesses'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="negative">• <?php echo e($s); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <div class="info-note"><?php echo e($assessment->ai_summary['notice'] ?? ''); ?></div>
            <?php endif; ?>
        </div>

        <?php if($hasParentEmail && $assessment->student?->parent_email): ?>
            <div class="panel parent-card">
                <h3>Send to Parent</h3>
                <p><?php echo e($assessment->student->parent_name); ?> · <?php echo e($assessment->student->parent_email); ?></p>
                <form method="post" action="<?php echo e(route('parent-report.send', $assessment)); ?>">
                    <?php echo csrf_field(); ?>
                    <textarea class="input" name="message" placeholder="Optional message"></textarea>
                    <button class="btn full">Email PDF + Secure Link</button>
                </form>

                <?php if($hasParentSms && $assessment->student?->parent_mobile): ?>
                    <form method="post" action="<?php echo e(route('parent-report.mobile', $assessment)); ?>" style="margin-top:8px">
                        <?php echo csrf_field(); ?>
                        <button class="btn ghost full">Send Secure Mobile Link</button>
                    </form>
                <?php endif; ?>

                <?php if($assessment->public_report_token): ?>
                    <label class="share-link-label">Secure report link
                        <input class="input" readonly value="<?php echo e(route('parent.report.public', $assessment->public_report_token)); ?>" onclick="this.select()">
                    </label>
                    <div class="actions" style="margin-top:8px">
                        <a class="btn small ghost" target="_blank" href="<?php echo e(route('parent.report.public', $assessment->public_report_token)); ?>">Preview Parent Link</a>
                        <a class="btn small ghost" href="<?php echo e(route('parent.report.pdf', $assessment->public_report_token)); ?>">Parent PDF</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\F_Reza\Downloads\Exam_Marker_V6_F\resources\views/results/show.blade.php ENDPATH**/ ?>