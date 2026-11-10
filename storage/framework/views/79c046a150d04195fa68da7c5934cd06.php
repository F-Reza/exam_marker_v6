<?php $__env->startSection('title','My Papers'); ?>
<?php $__env->startSection('page-title','My Papers'); ?>
<?php $__env->startSection('content'); ?>
<div class="welcome">
    <div><h1>My papers</h1><p>Manage uploaded papers and open completed results.</p></div>
    <a class="btn" href="<?php echo e(route('assessments.create')); ?>">＋ Check New Paper</a>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Assessment</th><th>Student</th><th>Uploaded</th><th>Status</th><th>Result</th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><b><?php echo e($a->title); ?></b><small><?php echo e($a->subject); ?> · <?php echo e($a->total_marks); ?> marks</small></td>
                    <td><?php echo e($a->student?->name ?? 'Individual'); ?></td>
                    <td><?php echo e($a->created_at->format('d M Y')); ?></td>
                    <td><span class="status <?php echo e($a->status); ?>"><?php echo e(str_replace('_',' ',$a->status)); ?></span></td>
                    <td>
                        <?php if($a->results->isNotEmpty()): ?>
                            <?php if(auth()->user()->hasFeature('percentage') && $a->percentage !== null): ?>
                                <?php echo e($a->percentage); ?>%
                            <?php else: ?>
                                <?php echo e($a->results->sum(fn($result) => $result->finalMarks())); ?> / <?php echo e($a->total_marks); ?>

                            <?php endif; ?>
                            <?php if(auth()->user()->hasFeature('grade') && $a->grade_awarded): ?>
                                · <?php echo e($a->grade_awarded); ?>

                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a class="table-link" href="<?php echo e(route($a->results->isNotEmpty() ? 'results.show' : 'assessments.show',$a)); ?>">Open</a>
                        <?php if(auth()->user()->canDeleteAssessment($a)): ?>
                        <form method="post" action="<?php echo e(route('assessments.destroy',$a)); ?>" onsubmit="return confirm('Delete this assessment?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="danger-link">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="empty">No papers uploaded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination"><?php echo e($assessments->links()); ?></div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/assessments/index.blade.php ENDPATH**/ ?>