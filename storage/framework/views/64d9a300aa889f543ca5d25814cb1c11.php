 <?php $__env->startSection('title','Create Account'); ?> <?php $__env->startSection('content'); ?>
<section class="auth-page">
    <div class="auth-copy"><span class="eyebrow">JOIN EXAM MARKER</span>
        <h1>Start checking papers with confidence.</h1>
        <p>Your first paper check is free. Upgrade only when you need more capacity and advanced tools.</p>
        <ul>
            <li>One-page QP, MS and WA upload</li>
            <li>Question-wise marks and feedback</li>
            <li>Teacher-controlled final results</li>
        </ul>
    </div>
    <form method="post" class="auth-card"><?php echo csrf_field(); ?><h2>Create your account</h2>
        <p>Register to start checking papers.</p><label>Full name<input class="input" name="name"
                value="<?php echo e(old('name')); ?>" required></label><label>Email address<input class="input" type="email"
                name="email" value="<?php echo e(old('email')); ?>" required></label><label>Mobile number<input class="input"
                name="mobile" value="<?php echo e(old('mobile')); ?>" required></label><label>Password<input class="input"
                type="password" name="password" required></label><label>Confirm password<input class="input"
                type="password" name="password_confirmation" required></label><label>I am a<select class="input"
                name="user_type">
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="coaching">Coaching Class</option>
            </select></label><button style="color: ghostwhite; font-weight: 600;" class="btn full">Create Free Account</button>
        <p class="center">Already registered? <a href="<?php echo e(route('login')); ?>">Login</a></p>
    </form>
</section><?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/auth/register.blade.php ENDPATH**/ ?>