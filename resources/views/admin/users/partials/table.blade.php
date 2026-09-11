@forelse($users as $u)

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


@empty


<tr>

<td colspan="5">

No users found.

</td>

</tr>


@endforelse