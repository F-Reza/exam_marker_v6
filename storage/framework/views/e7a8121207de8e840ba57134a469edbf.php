<?php echo $__env->make('help.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('title', 'Support Center'); ?>
<?php $__env->startSection('page-title', 'Support Center'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $statuses = \App\Models\SupportTicket::STATUSES;
    $categories = \App\Models\SupportTicket::CATEGORIES;
    $priorities = \App\Models\SupportTicket::PRIORITIES;
    $tabs = ['inbox' => 'Support Inbox', 'feedback' => 'Customer Feedback', 'solutions' => 'Solutions Provided'];
?>
<div class="support-page">
    <div class="welcome">
        <div><span class="eyebrow">CUSTOMER SUPPORT</span><h1>Support Center</h1><p>Manage support requests and feedback from every account and plan.</p></div>
    </div>
    <section class="panel support-ticket-list">
        <nav class="support-tabs" aria-label="Support ticket views">
            <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.support.index', ['tab' => $value])); ?>" class="<?php echo e($tab === $value ? 'active' : ''); ?>" <?php if($tab === $value): ?> aria-current="page" <?php endif; ?>><?php echo e($text); ?><span class="support-tab-count"><?php echo e($counts[$value] ?? 0); ?></span></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
        <div class="panel-head">
            <div><h2><?php echo e($tabs[$tab] ?? 'Support Inbox'); ?> <span class="support-heading-count">(<?php echo e($tickets->total()); ?>)</span></h2><p><?php echo e($tab === 'feedback' ? 'Suggestions and feedback submitted by users.' : ($tab === 'solutions' ? 'Resolved or closed tickets with a support reply.' : 'All submitted support tickets, including feedback.')); ?></p></div>
        </div>
        <form method="get" action="<?php echo e(route('admin.support.index')); ?>" class="support-filters">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
            <label class="support-search" for="admin-ticket-search">Search<input class="input" type="search" name="q" id="admin-ticket-search" maxlength="100" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Ticket, subject, message or user"></label>
            <label for="admin-status-filter">Status<select class="input" id="admin-status-filter" name="status"><option value="">All statuses</option><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label for="admin-priority-filter">Priority<select class="input" id="admin-priority-filter" name="priority"><option value="">All priorities</option><?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['priority'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <?php if($tab !== 'feedback'): ?>
            <label for="admin-category-filter">Category<select class="input" id="admin-category-filter" name="category"><option value="">All categories</option><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['category'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($text); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <?php endif; ?>
            <button class="btn ghost">Filter</button>
            <?php if(!empty($filters['q']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['category'])): ?><a class="support-clear" href="<?php echo e(route('admin.support.index', ['tab' => $tab])); ?>">Clear filters</a><?php endif; ?>
        </form>
        <div class="table-wrap">
            <table class="support-table">
                <thead><tr><th>Ticket</th><th>User</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="support-reference">#<?php echo e($t->id); ?></span><a class="support-ticket-subject" href="<?php echo e(route('admin.support.show', ['ticket' => $t, 'tab' => $tab])); ?>"><?php echo e($t->subject); ?></a><small class="support-ticket-preview"><?php echo e(\Illuminate\Support\Str::limit($tab === 'solutions' ? $t->admin_reply : $t->message, 110)); ?></small></td>
                        <td><b><?php echo e($t->user?->name ?? 'Account unavailable'); ?></b><small><?php echo e($t->user?->email); ?></small></td>
                        <td><?php echo e($categories[$t->category] ?? ucfirst($t->category)); ?></td>
                        <td><span class="support-badge priority-<?php echo e($t->priority); ?>"><?php echo e($priorities[$t->priority] ?? ucfirst($t->priority)); ?></span></td>
                        <td><span class="support-badge status-<?php echo e($t->status); ?>"><?php echo e($statuses[$t->status] ?? ucfirst($t->status)); ?></span></td>
                        <td><time datetime="<?php echo e($t->updated_at->toIso8601String()); ?>" title="<?php echo e($t->updated_at->format('d M Y, H:i')); ?>"><?php echo e($t->updated_at->diffForHumans()); ?></time></td>
                        <td><a class="table-link" href="<?php echo e(route('admin.support.show', ['ticket' => $t, 'tab' => $tab])); ?>">Open ticket</a></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7"><div class="support-empty"><b>No tickets found.</b><?php echo e($tab === 'solutions' ? 'Tickets appear here once a support reply has been recorded and the issue is resolved or closed.' : ($tab === 'feedback' ? 'User suggestions submitted under Feedback appear here.' : 'New support requests appear here as soon as a user submits a ticket.')); ?></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($tickets->hasPages()): ?><div class="pagination"><?php echo e($tickets->links()); ?></div><?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/support/index.blade.php ENDPATH**/ ?>