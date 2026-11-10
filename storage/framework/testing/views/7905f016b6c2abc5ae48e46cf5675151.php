<?php $__env->startSection('title','Audit Logs'); ?>

<?php $__env->startSection('page-title','Audit Logs'); ?>


<?php $__env->startSection('content'); ?>


<div class="card">


<h2>
Audit Logs
</h2>


<form method="GET">

<input 
class="input"
name="search"
placeholder="Search audit..."
value="<?php echo e(request('search')); ?>">


<button class="btn">
Search
</button>

</form>


<br>



<form method="POST" action="<?php echo e(route('admin.audit.delete')); ?>">

<?php echo csrf_field(); ?>
<?php echo method_field('DELETE'); ?>



<table class="data-table">


<thead>

<tr>

<th>
<input type="checkbox" id="selectAll">
</th>

<th>User</th>

<th>Action</th>

<th>Module</th>

<th>ID</th>

<th>Details</th>

<th>IP</th>

<th>Date</th>

</tr>

</thead>




<tbody>


<?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>


<tr>


<td>

<input 
type="checkbox"
name="logs[]"
value="<?php echo e($log->id); ?>">

</td>



<td>

<?php echo e($log->user?->name ?? 'System'); ?>


</td>



<td>

<?php echo e($log->action); ?>


</td>



<td>

<?php echo e($log->subject_type ?? '-'); ?>


</td>



<td>

<?php echo e($log->subject_id ?? '-'); ?>


</td>



<td>

<?php if($log->meta): ?>

<?php echo e(json_encode($log->meta)); ?>


<?php else: ?>

-

<?php endif; ?>

</td>



<td>

<?php echo e($log->ip_address ?? '-'); ?>


</td>



<td>

<?php echo e($log->created_at); ?>


</td>



</tr>



<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>


<tr>

<td colspan="8">

No audit records found.

</td>

</tr>


<?php endif; ?>



</tbody>


</table>



<br>


<button 
type="submit"
class="btn danger"
onclick="return confirm('Delete selected audit logs?')">

Delete Selected

</button>



</form>



<br>


<?php echo e($logs->links()); ?>



</div>



<script>

document
.getElementById('selectAll')
.addEventListener('change',function(){


let checkboxes=document
.querySelectorAll('input[name="logs[]"]');


checkboxes.forEach(function(box){

box.checked=document
.getElementById('selectAll')
.checked;

});


});


</script>



<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/audit/index.blade.php ENDPATH**/ ?>