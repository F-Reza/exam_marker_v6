 <?php $__env->startSection('title','Dashboard'); ?> <?php $__env->startSection('page-title','Dashboard'); ?> <?php $__env->startSection('content'); ?>
<div class="welcome">
    <div>
        <h1>Welcome, <?php echo e(explode(' ',auth()->user()->name)[0]); ?>!</h1>
        <p>Here is what is happening with your account today.</p>
    </div><a class="btn" href="<?php echo e(route('assessments.create')); ?>">＋ Check New Paper</a>
</div>
<div class="stats-grid">
    <article><span class="stat-icon blue">▤</span>
        <div><small>Total Papers</small><strong><?php echo e($total); ?></strong></div>
    </article>
    <article><span class="stat-icon green">✓</span>
        <div><small>Completed</small><strong><?php echo e($completed); ?></strong></div>
    </article>
    <article><span class="stat-icon amber">◷</span>
        <div><small>Processing</small><strong><?php echo e($processing); ?></strong></div>
    </article>
    <article><span class="stat-icon purple">♙</span>
        <div><small>Total Students</small><strong><?php echo e($students); ?></strong></div>
    </article>
</div>
<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Recent Papers</h2>
                <p>Your latest uploaded and checked papers.</p>
            </div><a href="<?php echo e(route('assessments.index')); ?>">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Paper</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
                        <td><b><?php echo e($a->title); ?></b><small><?php echo e($a->subject); ?></small></td>
                        <td><?php echo e($a->student?->name??'Individual'); ?></td>
                        <td><span class="status <?php echo e($a->status); ?>"><?php echo e(str_replace('_',' ',$a->status)); ?></span></td>
                        <td><?php echo e($a->percentage!==null?$a->percentage.'%':'—'); ?></td>
                        <td><a class="table-link"
                                href="<?php echo e(route($a->results()->exists()?'results.show':'assessments.show',$a)); ?>">View
                                →</a></td>
                    </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                        <td colspan="5" class="empty">No papers yet. Upload your first QP, MS and WA.</td>
                    </tr><?php endif; ?></tbody>
            </table>
        </div>
    </section>
    <section class="panel performance">
        <div class="panel-head">
            <div>
                <h2>Performance Overview</h2>
                <p>Current account average.</p>
            </div>
        </div>
        <div class="big-ring" style="--value:<?php echo e($average); ?>"><span><?php echo e($average); ?>%</span></div>
        <div class="legend"><span><i class="good"></i>Strong 80–100%</span><span><i class="mid"></i>Developing
                50–79%</span><span><i class="low"></i>Needs support</span></div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\F_Reza\Downloads\Exam_Marker_V6_F\resources\views/dashboard.blade.php ENDPATH**/ ?>