<?php $__env->startSection('title','Teacher Workspace'); ?> <?php $__env->startSection('page-title','Teacher Workspace'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-head">
    <div>
        <h1>Assigned Papers</h1>
        <p>Review papers assigned to you and finalise student results.</p>
    </div><a class="btn" href="<?php echo e(route('assessments.create')); ?>">Check New Paper</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody><?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
                    <td><?php echo e($a->title); ?><small><?php echo e($a->subject); ?></small></td>
                    <td><?php echo e($a->student?->name??'Individual paper'); ?></td>
                    <td><span class="status-pill"><?php echo e($a->status); ?></span></td>
                    <td><?php echo e($a->created_at->format('d M Y')); ?></td>
                    <td><a class="btn small" href="<?php echo e(route('assessments.show',$a)); ?>">Open</a></td>
                </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                    <td colspan="5">No assigned papers.</td>
                </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/portal/teacher.blade.php ENDPATH**/ ?>