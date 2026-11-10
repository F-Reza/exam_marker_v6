<?php echo $__env->make('help.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('title', 'Support ticket #'.$ticket->id); ?>
<?php $__env->startSection('page-title', 'Support Center'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $backTab = in_array(request('tab'), ['inbox', 'feedback', 'solutions'], true) ? request('tab') : 'inbox';
?>
<div class="support-page">
    <div class="welcome">
        <div>
            <a class="back" href="<?php echo e(route('admin.support.index', ['tab' => $backTab])); ?>">&larr; Support Center</a>
            <span class="support-reference">TICKET #<?php echo e($ticket->id); ?></span>
            <h1><?php echo e($ticket->subject); ?></h1>
            <p>Read the full conversation, reply to the user and update the ticket status.</p>
        </div>
        <span class="support-badge status-<?php echo e($ticket->status); ?>"><?php echo e($statuses[$ticket->status] ?? ucfirst($ticket->status)); ?></span>
    </div>

    <div class="support-detail-grid">
        <section class="panel">
            <?php echo $__env->make('help.partials.ticket-thread', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="support-reply">
                <h2>Reply to user</h2>
                <p class="support-note">Sending a reply marks this ticket as Answered. The user can read it in their Support Center.</p>
                <form method="post" action="<?php echo e(route('admin.support.reply', $ticket)); ?>" class="support-form">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <label for="admin-message">Message
                        <textarea class="input" id="admin-message" name="message" rows="6" minlength="1" maxlength="10000" required <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="admin-message-error" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(is_string(old('message')) ? old('message') : ''); ?></textarea>
                        <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error" id="admin-message-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <div class="support-form-actions"><button class="btn">Send reply</button></div>
                </form>
            </div>
        </section>

        <aside class="support-detail-sidebar">
            <section class="panel support-details">
                <h2>Ticket details</h2>
                <dl>
                    <div><dt>Submitted by</dt><dd><?php echo e($ticket->user?->name ?? 'Account unavailable'); ?><small><?php echo e($ticket->user?->email); ?></small></dd></div>
                    <div><dt>Category</dt><dd><?php echo e($categories[$ticket->category] ?? ucfirst($ticket->category)); ?></dd></div>
                    <div><dt>Priority</dt><dd><span class="support-badge priority-<?php echo e($ticket->priority); ?>"><?php echo e($priorities[$ticket->priority] ?? ucfirst($ticket->priority)); ?></span></dd></div>
                    <div><dt>Created</dt><dd><?php echo e($ticket->created_at->format('d M Y, H:i')); ?></dd></div>
                    <div><dt>Last updated</dt><dd><?php echo e($ticket->updated_at->format('d M Y, H:i')); ?></dd></div>
                </dl>
            </section>
            <section class="panel support-status-action">
                <h2>Update status</h2>
                <form method="post" action="<?php echo e(route('admin.support.reply', $ticket)); ?>" class="support-form">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <label for="ticket-status">Status
                        <select class="input" name="status" id="ticket-status" required>
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('status', $ticket->status) === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <button class="btn ghost">Update status</button>
                </form>
            </section>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/support/show.blade.php ENDPATH**/ ?>