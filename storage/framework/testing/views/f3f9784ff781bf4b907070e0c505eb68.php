<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="/assets/backend/css/user-management.css">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('title','User Management'); ?>

<?php $__env->startSection('page-title','User Management'); ?>


<?php $__env->startSection('content'); ?>




<div class="page-head">

<div>

<p class="page-subtitle">
Create and manage students, teachers, coaching accounts and administrators.
</p>

</div>


<div class="page-actions">

<button class="btn"
onclick="openUserModal()">

＋ Create User

</button>

</div>

</div>








<div class="stats-grid">


<div class="stat-card">
<h3><?php echo e(\App\Models\User::count()); ?></h3>
<p>Total Users</p>
</div>



<div class="stat-card">
<h3><?php echo e(\App\Models\User::where('user_type','student')->count()); ?></h3>
<p>Students</p>
</div>



<div class="stat-card">
<h3><?php echo e(\App\Models\User::where('user_type','teacher')->count()); ?></h3>
<p>Teachers</p>
</div>



<div class="stat-card">
<h3><?php echo e(\App\Models\User::where('is_admin',true)->count()); ?></h3>
<p>Admins</p>
</div>


</div>










<div class="card">


<h2>
Role Permissions
</h2>



<div class="role-grid">


<div class="role-box">

<span class="badge student">
Student
</span>

<p>
Personal results and paper access.
</p>

</div>



<div class="role-box">

<span class="badge teacher">
Teacher
</span>

<p>
Assigned paper checking workspace.
</p>

</div>




<div class="role-box">

<span class="badge coaching">
Coaching
</span>

<p>
Organisation dashboard and Mode 3 features.
</p>

</div>




<div class="role-box">

<span class="badge admin">
Platform Admin
</span>

<p>
Unlimited system access.
</p>

</div>



</div>


</div>












<div id="userModal"
class="modal-overlay">


<div class="modal-box">


<div class="modal-header">


<h2>
Create New User
</h2>


<button class="modal-close"
onclick="closeUserModal()">

✕

</button>


</div>





<form method="POST"
action="<?php echo e(route('admin.users.store')); ?>"
class="form-grid">


<?php echo csrf_field(); ?>



<label>

Full Name

<input 
class="input"
name="name"
required>

</label>




<label>

Email

<input
class="input"
type="email"
name="email"
required>

</label>




<label>

Mobile

<input
class="input"
name="mobile">

</label>




<label>

Role


<select
class="input"
name="user_type">


<option value="student">
Student
</option>


<option value="teacher">
Teacher
</option>


<option value="coaching">
Coaching Class
</option>


</select>

</label>





<label>

Plan


<select
class="input"
name="plan_id">


<option value="">
No Plan
</option>


<?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

<option value="<?php echo e($p->id); ?>">

<?php echo e($p->name); ?>


</option>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


</select>


</label>




<label>

Password


<input
class="input"
type="password"
name="password"
required>


</label>





<div class="modal-footer">


<button type="button"
class="btn ghost"
onclick="closeUserModal()">

Cancel

</button>


<button class="btn">

Create User

</button>


</div>



</form>



</div>

</div>












<div class="card">


<div class="card-header">


<h2>
All Users
</h2>



<div class="search-box">


<input

id="userSearch"

class="input"

placeholder="Search name, email or mobile..."

autocomplete="off">


</div>


</div>






<div class="table-wrap">


<table class="data-table">


<thead>


<tr>

<th>
User
</th>


<th>
Role
</th>


<th>
Plan
</th>


<th>
Status
</th>


<th style="width:430px">

Actions

</th>


</tr>


</thead>





<tbody id="usersTable">


<?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


<tr>


<td>


<div class="user-cell">


<div class="avatar">

<?php echo e(strtoupper(substr($u->name,0,2))); ?>


</div>


<div>

<b><?php echo e($u->name); ?></b>

<br>

<small>
<?php echo e($u->email); ?>

</small>

</div>


</div>


</td>





<td>


<?php if($u->isPlatformAdmin()): ?>

<span class="badge admin">
Platform Admin
</span>


<?php else: ?>


<span class="badge <?php echo e($u->user_type); ?>">

<?php echo e(ucfirst($u->user_type)); ?>


</span>


<?php endif; ?>


</td>






<td>

<?php echo e($u->plan?->name ?? 'No Plan'); ?>


</td>






<td>


<?php if($u->account_status=='active'): ?>


<span class="status active">
Active
</span>


<?php else: ?>


<span class="status danger">
Suspended
</span>


<?php endif; ?>


</td>







<td>



<?php if($u->isPlatformAdmin()): ?>


<span class="status active">

Action Disabled

</span>



<?php else: ?>



<form method="POST"

action="<?php echo e(route('admin.users.update',$u)); ?>"

class="user-action-form">


<?php echo csrf_field(); ?>

<?php echo method_field('PUT'); ?>





<select
class="input action-select-plan"
name="plan_id">


<option value="">
No Plan
</option>


<?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


<option value="<?php echo e($p->id); ?>"

<?php if($u->plan_id==$p->id): echo 'selected'; endif; ?>>


<?php echo e($p->name); ?>



</option>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


</select>






<select

class="input action-select-status"

name="account_status">


<option value="active"

<?php if($u->account_status=='active'): echo 'selected'; endif; ?>>

Active

</option>



<option value="suspended"

<?php if($u->account_status=='suspended'): echo 'selected'; endif; ?>>

Suspended

</option>


</select>







<label class="admin-check">


<input

type="checkbox"

name="is_admin"

value="1"

<?php if($u->is_admin): echo 'checked'; endif; ?>>


Admin


</label>







<button class="btn small">

Save

</button>




</form>


<?php endif; ?>



</td>



</tr>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



</tbody>


</table>


</div>





<?php echo e($users->links()); ?>




</div>









<script>


function openUserModal()
{

document
.getElementById('userModal')
.style.display='flex';

}



function closeUserModal()
{

document
.getElementById('userModal')
.style.display='none';

}





let searchTimer;


document
.getElementById('userSearch')
.addEventListener(
'keyup',
function(){


clearTimeout(searchTimer);


let value=this.value;



searchTimer=setTimeout(()=>{


fetch(
"<?php echo e(route('admin.users.search')); ?>?search="+value
)



.then(response=>response.text())


.then(html=>{


document
.getElementById('usersTable')
.innerHTML=html;


});


},300);



});



</script>



<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/users/index.blade.php ENDPATH**/ ?>