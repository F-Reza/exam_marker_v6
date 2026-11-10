 <?php $__env->startSection('title','Login'); ?> <?php $__env->startSection('content'); ?>
<section class="auth-page single">
    <form method="post" class="auth-card"><?php echo csrf_field(); ?><h2>Welcome back</h2>
        <p>Login to manage and check papers.</p><label>Email address<input class="input" type="email" name="email"
                value="<?php echo e(old('email')); ?>" required></label><label>Password<input class="input" type="password"
                name="password" required></label><label class="check"><input type="checkbox" name="remember"> Remember
            me</label><button style="color: ghostwhite; font-weight: 600;" class="btn full">Login</button>
        <p class="center"><a href="<?php echo e(route('password.request')); ?>">Forgot Password?</a></p>
        <p class="center">New to Exam Marker? <a href="<?php echo e(route('register')); ?>">Register</a></p>
    </form>
</section><?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/auth/login.blade.php ENDPATH**/ ?>