<?php echo $__env->make('help.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('title', 'Help & Support'); ?>
<?php $__env->startSection('page-title', 'Help & Support'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
?>
<div class="support-page">
    <div class="welcome">
        <div><span class="eyebrow">SUPPORT CENTER</span><h1>Help & Support</h1><p>Send a request, track its progress and read replies from the support team.</p></div>
    </div>
    <div class="help-grid">
        <section class="panel support-card">
            <h2>Create support ticket</h2>
            <form method="post" action="<?php echo e(route('help.store')); ?>" class="support-form">
                <?php echo csrf_field(); ?>
                <label for="support-subject">Subject
                    <input class="input" id="support-subject" name="subject" value="<?php echo e(is_string(old('subject')) ? old('subject') : ''); ?>" maxlength="180" required <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="subject-error" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                    <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error" id="subject-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>
                <div class="form-grid">
                    <label for="support-category">Category
                        <select class="input" name="category" id="support-category" required>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('category', 'general') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <label for="support-priority">Priority
                        <select class="input" name="priority" id="support-priority" required>
                            <?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('priority', 'normal') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['priority'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                </div>
                <label for="support-message">Message
                    <textarea class="input" name="message" id="support-message" rows="6" minlength="10" maxlength="10000" required <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="message-error" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(is_string(old('message')) ? old('message') : ''); ?></textarea>
                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="support-field-error" id="message-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>
                <div class="support-form-actions"><button class="btn">Submit Ticket</button><span class="support-note">Choose Feedback to share a suggestion.</span></div>
            </form>
        </section>
        <section class="panel faq-card">
            <h2>Quick answers</h2>
            <details open><summary>How do I follow up on a ticket?</summary><p>Open a ticket from the list below to read replies and send more details. You can close it when the issue is sorted, or reopen it if you still need help.</p></details>
            <details><summary>Why is MS optional?</summary><p>A mark scheme provides better marking guidance. If it is missing, the suggested result may need extra teacher review.</p></details>
            <details><summary>Can I edit AI marks?</summary><p>Mark editing is available when your plan includes it. Open Plan & Billing to review the features available to your account.</p></details>
            <details><summary>Who can see my support tickets?</summary><p>Only you and the platform support administrators can read your tickets. Other teachers and students cannot see them.</p></details>
        </section>
    </div>
    <section class="panel support-ticket-list">
        <div class="panel-head"><h2>Your tickets <span class="support-heading-count">(<?php echo e($tickets->total()); ?>)</span></h2></div>
        <form method="get" action="<?php echo e(route('help.index')); ?>" class="support-filters">
            <label class="support-search" for="ticket-search">Search tickets<input class="input" id="ticket-search" type="search" name="q" maxlength="100" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Subject, message or ticket number"></label>
            <label for="ticket-filter-status">Status<select class="input" id="ticket-filter-status" name="status"><option value="">All statuses</option><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <button class="btn ghost">Filter</button>
            <?php if(!empty($filters['q']) || !empty($filters['status'])): ?><a class="support-clear" href="<?php echo e(route('help.index')); ?>">Clear filters</a><?php endif; ?>
        </form>
        <div class="table-wrap">
            <table class="support-table">
                <thead><tr><th>Ticket</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="support-reference">#<?php echo e($t->id); ?></span><a class="support-ticket-subject" href="<?php echo e(route('help.show', $t)); ?>"><?php echo e($t->subject); ?></a><small class="support-ticket-preview"><?php echo e(\Illuminate\Support\Str::limit($t->message, 110)); ?></small></td>
                        <td><?php echo e($categories[$t->category] ?? ucfirst($t->category)); ?></td>
                        <td><span class="support-badge priority-<?php echo e($t->priority); ?>"><?php echo e($priorities[$t->priority] ?? ucfirst($t->priority)); ?></span></td>
                        <td><span class="support-badge status-<?php echo e($t->status); ?>"><?php echo e($statuses[$t->status] ?? ucfirst($t->status)); ?></span></td>
                        <td><time datetime="<?php echo e($t->updated_at->toIso8601String()); ?>" title="<?php echo e($t->updated_at->format('d M Y, H:i')); ?>"><?php echo e($t->updated_at->diffForHumans()); ?></time></td>
                        <td><a class="table-link" href="<?php echo e(route('help.show', $t)); ?>">View ticket</a></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6"><div class="support-empty"><b><?php echo e(!empty($filters['q']) || !empty($filters['status']) ? 'No tickets match your filters.' : 'No support tickets yet.'); ?></b><?php echo e(!empty($filters['q']) || !empty($filters['status']) ? 'Try a different search or clear the filters.' : 'Submit a ticket above when you need help.'); ?></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($tickets->hasPages()): ?><div class="pagination"><?php echo e($tickets->links()); ?></div><?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/help/index.blade.php ENDPATH**/ ?>