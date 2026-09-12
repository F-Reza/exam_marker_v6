<?php $__env->startSection('title','Exam Marker - AI Paper Checking'); ?>
<?php $__env->startSection('content'); ?>
<section class="hero">
    <div><span class="eyebrow">AI-ASSISTED MARKING FOR EDUCATION</span>
        <h1>Check examination papers <em>faster and more accurately.</em></h1>
        <p>Upload a Question Paper, optional Mark Scheme and Written Answer Paper. Receive question-wise marks, feedback
            and performance insights.</p>
        <div class="hero-actions"><a class="btn" style="color: ghostwhite; font-weight: 600;" href="<?php echo e(route('register')); ?>">Register & Check First Paper Free</a><a
                class="btn ghost ghost-btn" href="<?php echo e(route('pricing')); ?>">View Plans</a></div>
        <div class="trust-row"><span>✓ PDF & Word</span><span>✓ Secure uploads</span><span>✓ Teacher review</span></div>
    </div>
    <div class="hero-visual">
        <div class="mock-window">
            <div class="mock-head"><i></i><i></i><i></i></div>
            <div class="mock-score">
                <div class="score-ring">78<small>/100</small></div>
                <div><span class="badge green">Completed</span>
                    <h3>Mathematics Unit Test</h3>
                    <p>24 questions analysed</p>
                </div>
            </div>
            <div class="mini-bars"><i style="height:45%"></i><i style="height:68%"></i><i style="height:52%"></i><i
                    style="height:88%"></i><i style="height:74%"></i></div>
        </div>
    </div>
</section>
<section id="features" class="feature-strip">
    <article><span>◎</span><b>Accurate AI Checking</b>
        <p>Question-wise marking grounded in your QP and MS.</p>
    </article>
    <article><span>▥</span><b>Detailed Analysis</b>
        <p>Percentages, grades, graphs and topic performance.</p>
    </article>
    <article><span>✎</span><b>Teacher Control</b>
        <p>Review, edit and finalise suggested marks.</p>
    </article>
    <article><span>✉</span><b>Parent Sharing</b>
        <p>Mode 3 sends secure reports to parents.</p>
    </article>
</section>
<section class="section">
    <div class="section-head"><span class="eyebrow">HOW IT WORKS</span>
        <h2>From upload to result in four clear steps</h2>
    </div>
    <div class="steps">
        <article><b>01</b>
            <h3>Create an account</h3>
            <p>Register as a student, teacher or coaching class.</p>
        </article>
        <article><b>02</b>
            <h3>Upload all files</h3>
            <p>QP and MS appear together, with WA on the same page.</p>
        </article>
        <article><b>03</b>
            <h3>AI checks answers</h3>
            <p>The system generates question-wise suggested marks.</p>
        </article>
        <article><b>04</b>
            <h3>Review and report</h3>
            <p>Finalise, download or share according to your plan.</p>
        </article>
    </div>
</section>
<section class="cta">
    <div><span class="eyebrow">READY TO START?</span>
        <h2>Check your first paper free.</h2>
        <p>No payment required for the first lifetime check.</p>
    </div><a class="btn light light-btn" href="<?php echo e(route('register')); ?>">Create Free Account</a>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\F_Reza\Documents\New folder\exam_marker_v6_final\resources\views/home.blade.php ENDPATH**/ ?>