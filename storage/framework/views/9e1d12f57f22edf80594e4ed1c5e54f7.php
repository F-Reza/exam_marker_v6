<?php $__env->startSection('title','Student Portal'); ?> <?php $__env->startSection('page-title','Student Portal'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-head">
    <div><h1>My Results</h1><p>View your checked papers, marks and teacher feedback.</p></div>
    <?php if(auth()->user()->canAccessModule('assessments')): ?>
    <a class="btn" href="<?php echo e(route('assessments.create')); ?>">Check New Paper</a>
    <?php endif; ?>
</div>
<?php if(!$student): ?>
<div class="empty-state"><h3>Your student profile is not linked yet</h3><p>Ask your teacher or coaching administrator to link this login to your student record. Your own paper checks appear below.</p></div>
<?php else: ?>
<div class="stats-grid">
    <div class="stat-card"><span>Student</span><strong><?php echo e($student->name); ?></strong><small><?php echo e($student->grade); ?></small></div>
    <div class="stat-card"><span>Roll No.</span><strong><?php echo e($student->roll_number??'—'); ?></strong></div>
    <div class="stat-card"><span>Papers</span><strong><?php echo e($assessments->total()); ?></strong></div>
</div>
<?php endif; ?>
<div class="card">
    <h3>Assessment History</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Paper</th><th>Subject</th><th>Status</th><th>Marks</th><th>Action</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $canViewResult = $a->user_id === auth()->id()
                        ? $a->results()->exists()
                        : in_array($a->status, ['finalised', 'reported'], true);
                ?>
                <tr>
                    <td><?php echo e($a->title); ?></td>
                    <td><?php echo e($a->subject); ?></td>
                    <td><span class="status-pill"><?php echo e($a->status); ?></span></td>
                    <td>
                        <?php if($canViewResult && auth()->user()->hasFeature('percentage') && $a->percentage !== null): ?>
                        <?php echo e($a->percentage); ?>%
                        <?php elseif($canViewResult): ?>
                        <?php echo e($a->results->sum(fn($result) => $result->finalMarks())); ?> / <?php echo e($a->total_marks); ?>

                        <?php else: ?>
                        Pending
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($canViewResult): ?>
                        <a class="btn small" href="<?php echo e(route('results.show',$a)); ?>">View result</a>
                        <?php elseif($a->user_id === auth()->id()): ?>
                        <a class="btn small" href="<?php echo e(route('assessments.show',$a)); ?>">Open paper</a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5">No papers are available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php echo e($assessments->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/portal/student.blade.php ENDPATH**/ ?>