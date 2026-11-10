<?php $__env->startSection('title','Parent Communication'); ?> <?php $__env->startSection('page-title','Parent Communication'); ?>
<?php $__env->startSection('content'); ?>
<div class="welcome">
    <div><span class="eyebrow"> Notify the Parent</span>
        <h1>Parent Communication</h1>
        <p>Track every result sent to a parent.</p>
    </div>
</div>
<div class="panel table-panel">
    <div class="panel-head">
        <h2>Delivery log</h2><span class="badge"><?php echo e($items->total()); ?> messages</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Assessment</th>
                    <th>Recipient</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
                    <td><?php echo e($item->student?->name??'—'); ?></td>
                    <td><a href="<?php echo e(route('results.show',$item->assessment)); ?>"><b><?php echo e($item->assessment->title); ?></b></a>
                    </td>
                    <td><?php echo e($item->recipient); ?></td>
                    <td><?php echo e(strtoupper($item->channel)); ?></td>
                    <td><span class="badge <?php echo e($item->status==='sent'?'green':''); ?>"><?php echo e(ucfirst($item->status)); ?></span></td>
                    <td><?php echo e($item->sent_at?->format('d M Y, h:i A')??'Pending'); ?></td>
                </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                    <td colspan="6" class="empty">No parent messages have been sent.</td>
                </tr><?php endif; ?></tbody>
        </table>
    </div>
    <div class="pagination"><?php echo e($items->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/communications/index.blade.php ENDPATH**/ ?>