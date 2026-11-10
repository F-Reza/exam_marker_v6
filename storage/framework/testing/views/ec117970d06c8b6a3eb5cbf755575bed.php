<?php $__env->startSection('title','Payment Gateways'); ?>

<?php $__env->startSection('page-title','Payment Gateways'); ?>



<?php $__env->startSection('content'); ?>



<div class="welcome">

<div>

<span class="eyebrow">
PLATFORM ADMINISTRATOR
</span>


<h1>
Payment Gateway Setup
</h1>


<p>
Configure online payment providers securely.
</p>


</div>

</div>





<div class="gateway-admin-grid">



<?php $__currentLoopData = $gateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


<form method="POST"
class="panel gateway-admin-card"
action="<?php echo e(route('admin.payment-gateways.update',$g)); ?>">


<?php echo csrf_field(); ?>
<?php echo method_field('PUT'); ?>



<div class="gateway-card-head">


<div class="gateway-logo">

<?php echo e(strtoupper(substr($g->label,0,2))); ?>


</div>


<div>

<h2>
<?php echo e($g->label); ?>

</h2>

<small>
<?php echo e($g->provider); ?>

</small>

</div>



<label class="switch">

<input
type="checkbox"
name="enabled"
value="1"
<?php if($g->enabled): echo 'checked'; endif; ?>
>

<span></span>

</label>


</div>





<label>

Environment

<select
class="input"
name="environment">


<option value="sandbox"
<?php if($g->environment=='sandbox'): echo 'selected'; endif; ?>>

Sandbox

</option>


<option value="live"
<?php if($g->environment=='live'): echo 'selected'; endif; ?>>

Live

</option>


</select>

</label>





<?php

$c=$g->credentials ?? [];

?>







<?php if($g->provider=='paypal'): ?>


<h3>PayPal</h3>


<input class="input"
name="client_id"
placeholder="Client ID">


<input class="input"
type="password"
name="client_secret"
placeholder="Client Secret">



<?php endif; ?>







<?php if($g->provider=='payu'): ?>


<h3>PayU</h3>


<input class="input"
name="merchant_key"
placeholder="Merchant Key">


<input class="input"
type="password"
name="salt"
placeholder="Salt">


<?php endif; ?>







<?php if($g->provider=='phonepe'): ?>


<h3>PhonePe</h3>


<input class="input"
name="merchant_id"
placeholder="Merchant ID">


<input class="input"
name="authorization"
placeholder="Authorization">


<input class="input"
name="checkout_url"
placeholder="Checkout URL">


<?php endif; ?>







<?php if($g->provider=='paytm'): ?>


<h3>Paytm</h3>


<input class="input"
name="merchant_id"
placeholder="Merchant ID">


<input class="input"
name="checkout_url"
placeholder="Checkout URL">


<?php endif; ?>







<?php if($g->provider=='bkash'): ?>


<h3>
bKash Bangladesh
</h3>


<input class="input"
name="app_key"
placeholder="App Key">


<input class="input"
type="password"
name="app_secret"
placeholder="App Secret">


<input class="input"
name="username"
placeholder="Username">


<input class="input"
type="password"
name="password"
placeholder="Password">


<input class="input"
name="base_url"
value="https://tokenized.pay.bka.sh/v1.2.0-beta">


<?php endif; ?>







<?php if($g->provider=='card'): ?>


<h3>
Debit / Credit Card
</h3>



<select class="input"
name="card_provider">


<option value="stripe">
Stripe
</option>


<option value="sslcommerz">
SSLCommerz Bangladesh
</option>


<option value="razorpay">
Razorpay
</option>


</select>




<input class="input"
name="publishable_key"
placeholder="Publishable Key">


<input class="input"
type="password"
name="secret_key"
placeholder="Secret Key">


<?php endif; ?>





<button class="btn full">

Save <?php echo e($g->label); ?>


</button>




</form>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



</div>



<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/payment-gateways.blade.php ENDPATH**/ ?>