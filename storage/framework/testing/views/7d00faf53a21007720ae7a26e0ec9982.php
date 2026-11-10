<?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>


<tr>

<td>
<b>
<?php echo e($transactions->firstItem() + $loop->index); ?>

</b>
</td>


<td>


<?php echo e($transaction->transaction_id ?? 'N/A'); ?>


</td>


<td>


<b>
<?php echo e($transaction->user?->name ?? 'Guest'); ?>


<small>
<?php echo e($transaction->user?->email ?? ''); ?>

</small>


</td>





<td>

<?php echo e($transaction->plan?->name ?? 'N/A'); ?>


</td>





<td>


<?php echo e($transaction->currency); ?>


<?php echo e(number_format($transaction->amount,2)); ?>



</td>





<td>


<?php echo e(ucfirst($transaction->gateway ?? 'Manual')); ?>



</td>





<td>


<span class="status status-<?php echo e(strtolower($transaction->status)); ?>">

<?php echo e(ucfirst($transaction->status)); ?>


</span>


</td>





<td>


<?php echo e($transaction->created_at?->format('d M Y')); ?>



</td>





<td>


<a

class="btn btn-sm btn-primary"

href="<?php echo e(route('admin.transactions.show',$transaction->id)); ?>">

View

</a>





<form

method="POST"

action="<?php echo e(route('admin.transactions.destroy',$transaction->id)); ?>"

style="display:inline;"

>


<?php echo csrf_field(); ?>

<?php echo method_field('DELETE'); ?>



<button

type="submit"

class="btn btn-sm btn-danger"

onclick="return confirm('Are you sure you want to delete this transaction?')"

>

Delete

</button>


</form>



</td>



</tr>




<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>



<tr>

<td colspan="8" class="empty">


No transactions found.


</td>


</tr>


<?php endif; ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/transactions/partials/table.blade.php ENDPATH**/ ?>