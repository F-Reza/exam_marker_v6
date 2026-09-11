<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>
        <?php echo $__env->yieldContent('title', $systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'); ?>
    </title>

    <meta name="description" content="<?php echo e($systemSettings['meta_description'] ?? 'AI Paper Checking Platform'); ?>">
    <meta name="keywords" content="<?php echo e($systemSettings['meta_keywords'] ?? ''); ?>">

    <?php if(!empty($systemSettings['favicon'])): ?>
    <link rel="icon" href="<?php echo e(Storage::url($systemSettings['favicon'])); ?>">
    <?php else: ?>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <?php endif; ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="stylesheet" href="/assets/backend/css/style.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/backend/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('public/assets/backend/css/style.css')); ?>">

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body>

    <?php if(auth()->guard()->check()): ?>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="<?php echo e(route('dashboard')); ?>" class="brand">
                <?php if(!empty($systemSettings['site_logo'])): ?>
                <img src="<?php echo e(Storage::url($systemSettings['site_logo'])); ?>"
                    style="width:38px;height:38px;border-radius:10px;object-fit:cover;">
                <?php else: ?>
                <span class="brand-icon">✓</span>
                <?php endif; ?>
                <span>
                    <b><?php echo e($systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'); ?></b>
                    <small>AI Paper Checker</small>
                </span>
            </a>

            <nav>
                <?php if(Route::has('dashboard')): ?>
                <a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                    ☰ Dashboard
                </a>
                <?php endif; ?>

                <?php if(Route::has('assessments.create')): ?>
                <a href="<?php echo e(route('assessments.create')); ?>"
                    class="<?php echo e(request()->routeIs('assessments.create') ? 'active' : ''); ?>">
                    🚀 Check New Papers
                </a>
                <?php endif; ?>

                <?php if(Route::has('assessments.bulk.create')): ?>
                <a href="<?php echo e(route('assessments.bulk.create')); ?>"
                    class="<?php echo e(request()->routeIs('assessments.bulk.create') ? 'active' : ''); ?>">
                    🔼 Bulk Check Papers
                </a>
                <?php endif; ?>

                <?php if(Route::has('assessments.index')): ?>
                <a href="<?php echo e(route('assessments.index')); ?>"
                    class="<?php echo e(request()->routeIs('assessments.index') || request()->routeIs('assessments.show') || request()->routeIs('assessments.processing') ? 'active' : ''); ?>">
                    📚 All Papers
                </a>
                <?php endif; ?>

                <?php if(Route::has('students.index')): ?>
                <a href="<?php echo e(route('students.index')); ?>"
                    class="<?php echo e(request()->routeIs('students.index') || request()->routeIs('students.create') ? 'active' : ''); ?>">
                    👨‍🎓 Students
                </a>
                <?php endif; ?>

                <?php if(Route::has('communications.index')): ?>
                <a href="<?php echo e(route('communications.index')); ?>"
                    class="<?php echo e(request()->routeIs('communications.index') ? 'active' : ''); ?>">
                    💬 Parent Messages
                </a>
                <?php endif; ?>

                <?php if(Route::has('team.index')): ?>
                <a href="<?php echo e(route('team.index')); ?>" class="<?php echo e(request()->routeIs('team.index') ? 'active' : ''); ?>">
                    👨‍🏫 Teachers & Team
                </a>
                <?php endif; ?>

                <?php if(Route::has('reports.index')): ?>
                <a href="<?php echo e(route('reports.index')); ?>" class="<?php echo e(request()->routeIs('reports.index') ? 'active' : ''); ?>">
                    📊 Reports
                </a>
                <?php endif; ?>

                <?php if(Route::has('analytics')): ?>
                <a href="<?php echo e(route('analytics')); ?>" class="<?php echo e(request()->routeIs('analytics') ? 'active' : ''); ?>">
                    📈 Analytics
                </a>
                <?php endif; ?>

                <?php if(Route::has('notifications.index')): ?>
                <a href="<?php echo e(route('notifications.index')); ?>"
                    class="<?php echo e(request()->routeIs('notifications.index') ? 'active' : ''); ?>">
                    🔔 Notifications
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.support.index')): ?>
                <a href="<?php echo e(route('admin.support.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.support.index') ? 'active' : ''); ?>">
                    📩 Support Center
                </a>
                <?php endif; ?>

                
                <?php if(auth()->user()->isPlatformAdmin()): ?>
                <?php if(Route::has('admin.plans.index')): ?>
                <a href="<?php echo e(route('admin.plans.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.plans.index') ? 'active' : ''); ?>">
                    💼 Plans & Billing
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.payment-gateways.index')): ?>
                <a href="<?php echo e(route('admin.payment-gateways.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.payment-gateways.index') ? 'active' : ''); ?>">
                    🏛️ Payment Gateway
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.transactions.index')): ?>
                <a href="<?php echo e(route('admin.transactions.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.transactions.index') ? 'active' : ''); ?>">
                    💳 Transactions History
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.users.index')): ?>
                <a href="<?php echo e(route('admin.users.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.users.index') ? 'active' : ''); ?>">
                    👨‍👦‍👦 Users Management
                </a>
                <?php endif; ?>

                <?php if(Route::has('settings.index')): ?>
                <a href="<?php echo e(route('settings.index')); ?>"
                    class="<?php echo e(request()->routeIs('settings.index') ? 'active' : ''); ?>">
                    ⚙️ Profile Settings
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.config.index')): ?>
                <a href="<?php echo e(route('admin.config.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.config.index') ? 'active' : ''); ?>">
                    🛠️ System Configuration
                </a>
                <?php endif; ?>

                
                <?php if(auth()->user()->isPlatformAdmin()): ?>
                <?php if(Route::has('admin.ai-processing.index')): ?>
                <a href="<?php echo e(route('admin.ai-processing.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.ai-processing.index') ? 'active' : ''); ?>">
                    ✨ AI Processing
                </a>
                <?php endif; ?>

                <?php if(Route::has('admin.ai-settings.index')): ?>
                <a href="<?php echo e(route('admin.ai-settings.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.ai-settings.index') ? 'active' : ''); ?>">
                    🤖 AI Provider Settings
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <?php if(Route::has('admin.audit.index')): ?>
                <a href="<?php echo e(route('admin.audit.index')); ?>"
                    class="<?php echo e(request()->routeIs('admin.audit.index') ? 'active' : ''); ?>">
                    📋 Audit Logs
                </a>
                <?php endif; ?>

                <?php else: ?>
                <?php if(Route::has('billing.index')): ?>
                <a href="<?php echo e(route('billing.index')); ?>" class="<?php echo e(request()->routeIs('billing.index') ? 'active' : ''); ?>">
                    ◇ Plan & Billing
                </a>
                <?php endif; ?>

                <?php if(Route::has('help.index')): ?>
                <a href="<?php echo e(route('help.index')); ?>" class="<?php echo e(request()->routeIs('help.index') ? 'active' : ''); ?>">
                    ❓ Help & Support
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="side-plan">
                <?php if(auth()->user()->isPlatformAdmin()): ?>
                <span class="plan-chip">Platform Admin</span>
                <p>Unlimited System Access</p>
                <?php else: ?>
                <span class="plan-chip"><?php echo e(auth()->user()->plan?->name); ?></span>
                <p><?php echo e(auth()->user()->limit('paper_check_limit',1)); ?> checks per cycle</p>
                <?php endif; ?>
            </div>
        </aside>

        <div class="main-wrap">
            <header class="topbar">
                <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    ☰
                </button>

                <div class="top-title">
                    <?php echo $__env->yieldContent('page-title', 'Dashboard'); ?>
                </div>

                <?php if(Route::has('notifications.index')): ?>
                <a class="notification-link" href="<?php echo e(route('notifications.index')); ?>">
                    🔔
                    <span><?php echo e(auth()->user()->unreadNotifications()->count()); ?></span>
                </a>
                <?php endif; ?>

                <div class="user-menu">
                    <span class="avatar"><?php echo e(strtoupper(substr(auth()->user()->name,0,2))); ?></span>
                    <div>
                        <b><?php echo e(auth()->user()->name); ?></b>
                        <small><?php echo e(ucfirst(auth()->user()->user_type)); ?></small>
                    </div>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="icon-btn">↪</button>
                    </form>
                </div>
            </header>

            <main class="content">
                <?php else: ?>
                <header class="public-nav">
                    <a href="/" class="brand">
                        <?php if(!empty($systemSettings['site_logo'])): ?>
                        <img src="<?php echo e(Storage::url($systemSettings['site_logo'])); ?>"
                            style="width:38px;height:38px;border-radius:10px;object-fit:cover;">
                        <?php else: ?>
                        <span class="brand-icon">✓</span>
                        <?php endif; ?>
                        <span>
                            <b><?php echo e($systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'); ?></b>
                            <small>AI Paper Checker</small>
                        </span>
                    </a>

                    <nav>
                        <a href="<?php echo e(route('how')); ?>">How It Works</a>
                        <a href="/#features">Features</a>
                        <a href="<?php echo e(route('pricing')); ?>">Plans & Pricing</a>
                        <a href="<?php echo e(route('login')); ?>">Login</a>
                        <a style="color: ghostwhite; font-weight: bold;" class="btn small" href="<?php echo e(route('register')); ?>">Register</a>
                    </nav>
                </header>

                <main>
                    <?php endif; ?>

                    <?php if(session('success')): ?>
                    <div class="alert success">✓ <?php echo e(session('success')); ?></div>
                    <?php endif; ?>

                    <?php if(session('error')): ?>
                    <div class="alert danger">! <?php echo e(session('error')); ?></div>
                    <?php endif; ?>

                    <?php if($errors->any()): ?>
                    <div class="alert danger">
                        <b>Please correct:</b>
                        <ul>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($e); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php echo $__env->yieldContent('content'); ?>

                </main>

                <?php if(auth()->guard()->check()): ?>
        </div>
    </div>
    <?php endif; ?>

    <footer class="site-footer">
        <span>© <?php echo e(date('Y')); ?> <?php echo e($systemSettings['site_title'] ?? $systemSettings['app_name'] ?? 'Exam Marker'); ?></span>
        <span>AI suggestions require human review.</span>
    </footer>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html><?php /**PATH C:\Users\F_Reza\Downloads\Exam_Marker_V6_F\resources\views/layouts/app.blade.php ENDPATH**/ ?>