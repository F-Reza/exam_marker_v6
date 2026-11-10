<?php echo $__env->make('help.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('title', 'Support ticket #'.$ticket->id); ?>
<?php $__env->startSection('page-title', 'Support Center'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
?>
<div class="support-page">
    <div class="welcome">
        <div>
            <a class="back" href="<?php echo e(route('help.index')); ?>">&larr; Your tickets</a>
            <span class="support-reference">TICKET #<?php echo e($ticket->id); ?></span>
            <h1><?php echo e($ticket->subject); ?></h1>
            <p>Messages and updates from the support team appear here.</p>
        </div>
        <span class="support-badge status-<?php echo e($ticket->status); ?>"><?php echo e($statuses[$ticket->status] ?? ucfirst($ticket->status)); ?></span>
    </div>

    <div class="support-detail-grid">
        <section class="panel">
            <?php echo $__env->make('help.partials.ticket-thread', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="support-reply">
                <?php if($isClosed): ?>
                <h2><?php echo e($ticket->status === 'resolved' ? 'This ticket has been resolved' : 'This ticket is closed'); ?></h2>
                <p class="support-note">If you still need help with this issue, reopen the ticket to continue the conversation.</p>
                <form method="post" action="<?php echo e(route('help.status', $ticket)); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="status" value="open">
                    <button class="btn">Reopen ticket</button>
                </form>
                <?php else: ?>
                <h2>Reply to support</h2>
                <p class="support-note">Add more details or let us know whether the reply helped.</p>
                <form method="post" action="<?php echo e(route('help.reply', $ticket)); ?>" class="support-form">
                    <?php echo csrf_field(); ?>
                    <label for="reply-message">Message
                        <textarea class="input" id="reply-message" name="message" rows="5" minlength="1" maxlength="10000" required <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="reply-message-error" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(is_string(old('message')) ? old('message') : ''); ?></textarea>
                        <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error" id="reply-message-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <div class="support-form-actions"><button class="btn">Send reply</button></div>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <aside class="support-detail-sidebar">
            <section class="panel support-details">
                <h2>Ticket details</h2>
                <dl>
                    <div><dt>Category</dt><dd><?php echo e($categories[$ticket->category] ?? ucfirst($ticket->category)); ?></dd></div>
                    <div><dt>Priority</dt><dd><span class="support-badge priority-<?php echo e($ticket->priority); ?>"><?php echo e($priorities[$ticket->priority] ?? ucfirst($ticket->priority)); ?></span></dd></div>
                    <div><dt>Created</dt><dd><?php echo e($ticket->created_at->format('d M Y, H:i')); ?></dd></div>
                    <div><dt>Last updated</dt><dd><?php echo e($ticket->updated_at->format('d M Y, H:i')); ?></dd></div>
                </dl>
            </section>
            <?php if(!$isClosed): ?>
            <section class="panel support-status-action">
                <h2>Issue sorted?</h2>
                <p class="support-note">Close this ticket when you no longer need help. You can reopen it later.</p>
                <form method="post" action="<?php echo e(route('help.status', $ticket)); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="status" value="closed">
                    <button class="btn ghost">Close ticket</button>
                </form>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/help/show.blade.php ENDPATH**/ ?>