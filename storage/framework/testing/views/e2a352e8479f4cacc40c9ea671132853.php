<?php $__env->startSection('title','Help & Support'); ?> <?php $__env->startSection('page-title','Help & Support'); ?>
<?php $__env->startSection('content'); ?>
<div class="welcome">
    <div><span class="eyebrow">SUPPORT CENTRE</span>
        <h1>Help & Support</h1>
        <p>Find answers or send a request to the support team.</p>
    </div>
</div>
<div class="help-grid">
    <section class="panel form-card">
        <h2>Create support ticket</h2>
        <form method="post" action="<?php echo e(route('help.store')); ?>" class="form-grid"><?php echo csrf_field(); ?><label class="span-2">Subject<input
                    class="input" name="subject" required></label><label>Category<select class="input" name="category">
                    <option>general</option>
                    <option>paper processing</option>
                    <option>billing</option>
                    <option>account</option>
                </select></label><label>Priority<select class="input" name="priority">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                </select></label><label class="span-2">Message<textarea class="input" rows="6" name="message"
                    required></textarea></label>
            <div class="span-2"><button class="btn">Submit Ticket</button></div>
        </form>
    </section>
    <section>
        <div class="panel faq-card">
            <h2>Quick answers</h2>
            <details open>
                <summary>Why is MS optional?</summary>
                <p>The demo marker can create a suggested marking structure, but an official mark scheme provides better
                    accuracy.</p>
            </details>
            <details>
                <summary>Can I edit AI marks?</summary>
                <p>Manual mark editing is available in Mode 2 and Mode 3.</p>
            </details>
            <details>
                <summary>Why can’t I download the corrected paper?</summary>
                <p>Corrected-paper download is a Mode 3 feature.</p>
            </details>
            <details>
                <summary>How are parent links protected?</summary>
                <p>They use expiring secure report tokens.</p>
            </details>
        </div>
    </section>
</div>
<div class="panel table-panel">
    <div class="panel-head">
        <h2>Your tickets</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody><?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
                    <td><b><?php echo e($t->subject); ?></b><small><?php echo e($t->message); ?></small></td>
                    <td><?php echo e($t->category); ?></td>
                    <td><?php echo e(ucfirst($t->priority)); ?></td>
                    <td><span class="badge"><?php echo e(ucfirst($t->status)); ?></span></td>
                    <td><?php echo e($t->updated_at->diffForHumans()); ?></td>
                </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                    <td colspan="5" class="empty">No support tickets.</td>
                </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/help/index.blade.php ENDPATH**/ ?>