<?php $__env->startSection('title','Students'); ?>

<?php $__env->startSection('page-title','Students'); ?>


<?php $__env->startPush('styles'); ?>

<style>


.student-grid
{
    display:grid;
    gap:20px;
}



.panel
{
    background:#fff;
    border-radius:16px;
    padding:25px;
    box-shadow:0 8px 25px rgba(0,0,0,.05);
}




.panel-head
{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:20px;
}



.panel-head h2
{
    margin:0;
    color:#13213f;
    font-size:20px;
}



.panel-head p
{
    margin:5px 0 0;
    color:#64748b;
    font-size:14px;
}




.bulk-row
{
    display:flex;
    gap:15px;
    align-items:center;
}



.bulk-row .input
{
    flex:1;
}





.welcome
{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}



.welcome h1
{
    margin-bottom:8px;
}



.welcome p
{
    color:#64748b;
}




.table-wrap
{
    overflow-x:auto;
}



table
{
    width:100%;
    border-collapse:collapse;
}



thead th
{
    text-align:left;
    background:#f8fafc;
    padding:14px;
    color:#475569;
    font-size:14px;
}



tbody td
{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    color:#334155;
}



tbody tr:hover
{
    background:#f8fafc;
}



td b
{
    color:#13213f;
}




.actions
{
    display:flex;
    align-items:center;
    gap:12px;
}



.actions a
{
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}



.danger-link
{
    background:none;
    border:none;
    color:#dc2626;
    cursor:pointer;
    font-weight:600;
}



.btn.small
{
    padding:8px 14px;
    font-size:13px;
}



.btn.ghost
{
    background:#f1f5f9;
    color:#334155;
}



.empty
{
    text-align:center;
    padding:35px;
    color:#64748b;
}




.pagination
{
    margin-top:20px;
}





@media(max-width:768px)
{


.welcome
{
    flex-direction:column;
    align-items:flex-start;
    gap:15px;
}



.bulk-row
{
    flex-direction:column;
}



.bulk-row .btn,
.bulk-row .input
{
    width:100%;
}



.actions
{
    flex-direction:column;
    align-items:flex-start;
}


}



</style>


<?php $__env->stopPush(); ?>




<?php $__env->startSection('content'); ?>





<?php if(auth()->user()->hasFeature('bulk_student_import')): ?>


<div class="panel"
style="margin-bottom:20px">


<div class="panel-head">


<div>

<h2>
📥 Bulk Import Students
</h2>


<p>
CSV import is for student records only. Exam documents remain PDF/DOC/DOCX.
</p>


</div>



<a class="btn ghost small"
href="<?php echo e(route('students.template')); ?>">

Download CSV Template

</a>


</div>





<form method="POST"
enctype="multipart/form-data"
action="<?php echo e(route('students.import')); ?>"
class="bulk-row">


<?php echo csrf_field(); ?>



<input
class="input"
type="file"
name="student_file"
accept=".csv,.txt"
required>



<button class="btn" style="white-space:nowrap; background:#038334; color:#fff;">

Import Students

</button>



</form>



</div>


<?php endif; ?>










<div class="welcome">


<div>

<h1>
👨‍🎓 Students
</h1>


<p>
Manage student profiles
<?php echo e($students->count() ? 'and select them during paper upload.' : '.'); ?>

</p>


</div>




<a class="btn"
href="<?php echo e(route('students.create')); ?>">

＋ Add Student

</a>



</div>










<section class="panel">


<div class="table-wrap">


<table>


<thead>

<tr>


<th>
Student
</th>


<th>
Roll No.
</th>


<th>
Grade
</th>



<?php if(auth()->user()->hasFeature('parent_details')): ?>

<th>
Parent
</th>


<th>
Contact
</th>

<?php endif; ?>



<th>
Action
</th>



</tr>


</thead>




<tbody>



<?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>



<tr>



<td>

<b>
<?php echo e($s->name); ?>

</b>

</td>




<td>
<?php echo e($s->roll_number ?? '—'); ?>

</td>




<td>
<?php echo e($s->grade ?? '—'); ?>

</td>





<?php if(auth()->user()->hasFeature('parent_details')): ?>


<td>

<?php echo e($s->parent_name ?? '—'); ?>


</td>




<td>

<?php echo e($s->parent_email ?? $s->parent_mobile ?? '—'); ?>


</td>


<?php endif; ?>





<td class="actions">


<a href="<?php echo e(route('students.edit',$s)); ?>">

Edit

</a>





<form method="POST"
action="<?php echo e(route('students.destroy',$s)); ?>"
onsubmit="return confirm('Delete student?')">


<?php echo csrf_field(); ?>

<?php echo method_field('DELETE'); ?>



<button class="danger-link">

Delete

</button>



</form>



</td>



</tr>



<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>



<tr>

<td colspan="6"
class="empty">

No students added yet.

</td>

</tr>


<?php endif; ?>



</tbody>


</table>



</div>





<div class="pagination">

<?php echo e($students->links()); ?>


</div>



</section>




<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/students/index.blade.php ENDPATH**/ ?>