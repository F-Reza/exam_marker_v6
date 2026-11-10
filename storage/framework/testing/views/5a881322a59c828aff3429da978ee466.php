<?php $__env->startSection('title','Notifications'); ?> <?php $__env->startSection('page-title','Notifications'); ?>
<?php $__env->startSection('content'); ?>
<div class="welcome"><div><span class="eyebrow">ACTIVITY</span><h1>Notifications</h1><p>Support replies, paper-processing, report and billing updates.</p></div><form method="post" action="<?php echo e(route('notifications.read')); ?>"><?php echo csrf_field(); ?><button class="btn ghost">Mark all read</button></form></div>
<div class="panel notification-list">
    <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <article class="<?php echo e($n->read_at?'':'unread'); ?>">
        <span class="notification-icon">●</span>
        <div>
            <b><?php echo e($n->data['title']??'Notification'); ?></b>
            <p><?php echo e($n->data['message']??'Account update.'); ?></p>
            <?php if(($n->data['type']??null) === 'support_ticket' && isset($n->data['ticket_id'])): ?>
            <a class="table-link" href="<?php echo e(route(auth()->user()->isPlatformAdmin() ? 'admin.support.show' : 'help.show', (int)$n->data['ticket_id'])); ?>">Open ticket #<?php echo e((int)$n->data['ticket_id']); ?></a>
            <?php endif; ?>
            <small><?php echo e($n->created_at->diffForHumans()); ?></small>
        </div>
    </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="empty">No notifications yet.</div>
    <?php endif; ?>
    <div class="pagination"><?php echo e($notifications->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/notifications/index.blade.php ENDPATH**/ ?>