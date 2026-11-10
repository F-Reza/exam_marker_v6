 <?php $__env->startSection('title','Plan Management'); ?> <?php $__env->startSection('page-title','Plan Management'); ?> <?php $__env->startSection('content'); ?>
<div class="welcome">
    <div>
        <h1>Plans and user access</h1>
        <p>Change prices, limits and feature permissions without editing code.</p>
    </div>
</div>
<div class="admin-gridX">
    <section class="panel">
        <div class="panel-head">
            <h2>Available Plans</h2>
        </div><?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><form method="post" action="<?php echo e(route('admin.plans.update',$plan)); ?>"
            class="admin-plan"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?><div class="admin-plan-head">
                <div><b><?php echo e($plan->name); ?></b><small><?php echo e($plan->slug); ?></small></div><span class="status active" style="height: 27px;">Active</span>
            </div>
            <div class="mini-form"><label>Price<input class="input" type="number" name="price"
                        value="<?php echo e($plan->price); ?>"></label><label>Paper limit<input class="input" type="number"
                        name="paper_check_limit"
                        value="<?php echo e($plan->limits['paper_check_limit']??1); ?>"></label><label>Student limit<input
                        class="input" type="number" name="student_limit"
                        value="<?php echo e($plan->limits['student_limit']??0); ?>"></label><label>Teacher limit<input class="input"
                        type="number" name="teacher_limit"
                        value="<?php echo e($plan->limits['teacher_limit']??1); ?>"></label><label>Email limit<input class="input"
                        type="number" name="email_limit"
                        value="<?php echo e($plan->limits['email_limit']??0); ?>"></label><label>Mobile/SMS limit<input class="input"
                        type="number" name="sms_limit" value="<?php echo e($plan->limits['sms_limit']??0); ?>"></label><label>Recheck
                    limit<input class="input" type="number" name="recheck_limit"
                        value="<?php echo e($plan->limits['recheck_limit']??0); ?>"></label></div>
            <div class="feature-checks"><?php $__currentLoopData = $plan->features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$on): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox"
                        name="features[<?php echo e($key); ?>]" value="1" <?php if($on): echo 'checked'; endif; ?>> <?php echo e(ucwords(str_replace('_','
                    ',$key))); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div><button class="btn small">Update Plan</button>
        </form><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>
    
</div><?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/plans.blade.php ENDPATH**/ ?>