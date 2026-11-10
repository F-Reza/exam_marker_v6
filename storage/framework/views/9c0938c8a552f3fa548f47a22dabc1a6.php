<div class="support-thread">
    <h2>Conversation</h2>
    <article class="support-message">
        <div class="support-message-meta">
            <div><b><?php echo e($ticket->user?->name ?? 'Account unavailable'); ?></b><small>Original request</small></div>
            <time datetime="<?php echo e($ticket->created_at->toIso8601String()); ?>"><?php echo e($ticket->created_at->format('d M Y, H:i')); ?></time>
        </div>
        <div class="support-message-body"><?php echo e($ticket->message); ?></div>
    </article>

    <?php if($ticket->hasLegacyReply()): ?>
    <article class="support-message is-admin">
        <div class="support-message-meta"><div><b>Support Team</b><small>Previous reply</small></div></div>
        <div class="support-message-body"><?php echo e($ticket->admin_reply); ?></div>
    </article>
    <?php endif; ?>

    <?php $__currentLoopData = $ticket->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($entry->type === 'status_change'): ?>
        <div class="support-event">
            <span><?php echo e($entry->message); ?></span>
            <time datetime="<?php echo e($entry->created_at->toIso8601String()); ?>"><?php echo e($entry->created_at->format('d M Y, H:i')); ?></time>
        </div>
        <?php else: ?>
        <?php ($fromSupport = $entry->is_admin || $entry->type === 'legacy_reply'); ?>
        <article class="support-message <?php echo e($fromSupport ? 'is-admin' : ''); ?>">
            <div class="support-message-meta">
                <div>
                    <b><?php echo e($fromSupport ? 'Support Team' : ($entry->user?->name ?? 'Account unavailable')); ?></b>
                    <?php if($entry->type === 'legacy_reply'): ?><small>Previous reply</small><?php endif; ?>
                </div>
                <time datetime="<?php echo e($entry->created_at->toIso8601String()); ?>"><?php echo e($entry->created_at->format('d M Y, H:i')); ?></time>
            </div>
            <div class="support-message-body"><?php echo e($entry->message); ?></div>
        </article>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH D:\My App\exam_marker_v6\resources\views/help/partials/ticket-thread.blade.php ENDPATH**/ ?>