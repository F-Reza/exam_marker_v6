 <?php $__env->startSection('title','Plans & Pricing'); ?> <?php $__env->startSection('content'); ?>
<section class="page-hero"><span class="eyebrow">PLANS & PRICING</span>
    <h1>Choose the power you need</h1>
    <p>Start free, then upgrade from marks-only checking to full coaching-class communication.</p>
</section>
<section class="pricing-grid">
    <?php $plans=\App\Models\Plan::where('active',true)->orderBy('price')->get(); ?>
    <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="price-card <?php echo e($plan->slug==='mode-2'?'popular':''); ?>">
            <?php if($plan->slug==='mode-2'): ?><span class="ribbon">Most Popular</span><?php endif; ?>
            <span class="plan-kicker"><?php echo e($plan->slug==='free'?'TRY ONCE':strtoupper($plan->slug)); ?></span>
            <h2><?php echo e($plan->name); ?></h2>
            <div class="price">₹<?php echo e(number_format((float)$plan->price)); ?><small>/<?php echo e($plan->billing_period); ?></small></div>
            <ul>
                <li>✓ <?php echo e($plan->limits['paper_check_limit']??1); ?> paper checks</li>
                <li>✓ Question-wise marks</li>
                <li>✓ Total marks and feedback</li>
                <?php if($plan->features['graphs']??false): ?><li>✓ Percentage, grade and graphs</li><?php endif; ?>
                <?php if($plan->features['manual_mark_editing']??false): ?><li>✓ Manual mark editing</li><?php endif; ?>
                <?php if($plan->features['result_report_download']??false): ?>
                    <li>✓ Download result report</li>
                <?php else: ?>
                    <li class="off">× No report downloads</li>
                <?php endif; ?>
                <?php if($plan->features['corrected_paper_download']??false): ?>
                    <li>✓ Download corrected paper</li>
                <?php else: ?>
                    <li class="off">× No corrected-paper download</li>
                <?php endif; ?>
                <?php if($plan->features['parent_email']??false): ?>
                    <li>✓ Parent communication</li>
                <?php else: ?>
                    <li class="off">× No parent communication</li>
                <?php endif; ?>
                <?php if($plan->features['multiple_teachers']??false): ?>
                    <li>✓ Multiple teachers</li>
                <?php else: ?>
                    <li class="off">× Single user/teacher</li>
                <?php endif; ?>
            </ul>
            <a style="<?php echo e($plan->slug === 'free' ? '' : 'color: ghostwhite; font-weight: 600;'); ?>" 
               class="btn <?php echo e($plan->slug==='free'?'ghost':''); ?> full"
               href="<?php echo e(auth()->check()?($plan->slug==='free'?route('dashboard'):route('payments.checkout',$plan)):route('register')); ?>">
               <?php echo e($plan->slug==='free'?'Try Free':'Choose Plan'); ?>

            </a>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/pricing.blade.php ENDPATH**/ ?>