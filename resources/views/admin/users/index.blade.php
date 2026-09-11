@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/assets/backend/css/user-management.css">
@endpush

@section('title','User Management')

@section('page-title','User Management')


@section('content')


{{-- HEADER --}}

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






{{-- STATISTICS --}}

<div class="stats-grid">


<div class="stat-card">
<h3>{{\App\Models\User::count()}}</h3>
<p>Total Users</p>
</div>



<div class="stat-card">
<h3>{{\App\Models\User::where('user_type','student')->count()}}</h3>
<p>Students</p>
</div>



<div class="stat-card">
<h3>{{\App\Models\User::where('user_type','teacher')->count()}}</h3>
<p>Teachers</p>
</div>



<div class="stat-card">
<h3>{{\App\Models\User::where('is_admin',true)->count()}}</h3>
<p>Admins</p>
</div>


</div>








{{-- ROLE PERMISSION --}}

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









{{-- CREATE USER MODAL --}}


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
action="{{route('admin.users.store')}}"
class="form-grid">


@csrf



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


@foreach($plans as $p)

<option value="{{$p->id}}">

{{$p->name}}

</option>

@endforeach


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









{{-- USERS TABLE --}}


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


@foreach($users as $u)


<tr>


<td>


<div class="user-cell">


<div class="avatar">

{{strtoupper(substr($u->name,0,2))}}

</div>


<div>

<b>{{$u->name}}</b>

<br>

<small>
{{$u->email}}
</small>

</div>


</div>


</td>





<td>


@if($u->isPlatformAdmin())

<span class="badge admin">
Platform Admin
</span>


@else


<span class="badge {{$u->user_type}}">

{{ucfirst($u->user_type)}}

</span>


@endif


</td>






<td>

{{$u->plan?->name ?? 'No Plan'}}

</td>






<td>


@if($u->account_status=='active')


<span class="status active">
Active
</span>


@else


<span class="status danger">
Suspended
</span>


@endif


</td>







<td>



@if($u->isPlatformAdmin())


<span class="status active">

Action Disabled

</span>



@else



<form method="POST"

action="{{route('admin.users.update',$u)}}"

class="user-action-form">


@csrf

@method('PUT')





<select
class="input action-select-plan"
name="plan_id">


<option value="">
No Plan
</option>


@foreach($plans as $p)


<option value="{{$p->id}}"

@selected($u->plan_id==$p->id)>


{{$p->name}}


</option>


@endforeach


</select>






<select

class="input action-select-status"

name="account_status">


<option value="active"

@selected($u->account_status=='active')>

Active

</option>



<option value="suspended"

@selected($u->account_status=='suspended')>

Suspended

</option>


</select>







<label class="admin-check">


<input

type="checkbox"

name="is_admin"

value="1"

@checked($u->is_admin)>


Admin


</label>







<button class="btn small">

Save

</button>




</form>


@endif



</td>



</tr>


@endforeach



</tbody>


</table>


</div>





{{$users->links()}}



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
"{{route('admin.users.search')}}?search="+value
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



@endsection