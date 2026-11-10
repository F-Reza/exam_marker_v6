<?php $__env->startSection('title','Forgot Password'); ?>
<?php $__env->startSection('content'); ?>
<section class="auth-page single">
    <form method="post" action="<?php echo e(route('password.email')); ?>" class="auth-card"><?php echo csrf_field(); ?>
        <h2>Forgot Password</h2>
        <p>Enter your email and we will send a password reset link.</p>
        <label>Email address<input class="input" type="email" name="email" required></label>
        <button style="color: ghostwhite; font-weight: 600;" class="btn full">Send Reset Link</button>
        <p class="center"><a href="<?php echo e(route('login')); ?>">Back to Login</a></p>
    </form>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>