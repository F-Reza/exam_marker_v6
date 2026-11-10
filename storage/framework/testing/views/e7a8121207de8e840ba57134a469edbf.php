<?php $__env->startSection('title','Support Center'); ?>

<?php $__env->startSection('page-title','Support Center'); ?>


<?php $__env->startSection('content'); ?>


<div class="welcome">

<h1>Support Center</h1>

<p>
Manage customer support requests and feedback.
</p>

</div>



<div class="panel">


<div class="support-tabs">


<a href="<?php echo e(route('admin.support.index',['tab'=>'inbox'])); ?>"
class="<?php echo e(request('tab','inbox')=='inbox'?'active':''); ?>">
Support Inbox
</a>



<a href="<?php echo e(route('admin.support.index',['tab'=>'feedback'])); ?>"
class="<?php echo e(request('tab')=='feedback'?'active':''); ?>">
Customer Feedback
</a>



<a href="<?php echo e(route('admin.support.index',['tab'=>'solutions'])); ?>"
class="<?php echo e(request('tab')=='solutions'?'active':''); ?>">
Solutions Provided
</a>


</div>





<div class="support-content">



<?php if($tab=='inbox'): ?>




<table class="data-table">

<tr>
<th>User</th>
<th>Subject</th>
<th>Status</th>
</tr>


<tr>
<td colspan="3">
No support tickets available.
</td>
</tr>


</table>





<?php elseif($tab=='feedback'): ?>





<table class="data-table">

<tr>
<th>User</th>
<th>Feedback</th>
</tr>


<tr>
<td colspan="2">
No feedback received.
</td>
</tr>


</table>







<?php elseif($tab=='solutions'): ?>





<table class="data-table">

<tr>
<th>Issue</th>
<th>Solution</th>
</tr>


<tr>
<td colspan="2">
No solutions recorded.
</td>
</tr>


</table>



<?php endif; ?>



</div>


</div>



<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/support/index.blade.php ENDPATH**/ ?>