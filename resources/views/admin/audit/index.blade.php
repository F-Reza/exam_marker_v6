@extends('layouts.app')

@section('title','Audit Logs')

@section('page-title','Audit Logs')


@section('content')


<div class="card">


<h2>
Audit Logs
</h2>


<form method="GET">

<input 
class="input"
name="search"
placeholder="Search audit..."
value="{{request('search')}}">


<button class="btn">
Search
</button>

</form>


<br>



<form method="POST" action="{{route('admin.audit.delete')}}">

@csrf
@method('DELETE')



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


@forelse($logs as $log)


<tr>


<td>

<input 
type="checkbox"
name="logs[]"
value="{{$log->id}}">

</td>



<td>

{{$log->user?->name ?? 'System'}}

</td>



<td>

{{$log->action}}

</td>



<td>

{{$log->subject_type ?? '-'}}

</td>



<td>

{{$log->subject_id ?? '-'}}

</td>



<td>

@if($log->meta)

{{json_encode($log->meta)}}

@else

-

@endif

</td>



<td>

{{$log->ip_address ?? '-'}}

</td>



<td>

{{$log->created_at}}

</td>



</tr>



@empty


<tr>

<td colspan="8">

No audit records found.

</td>

</tr>


@endforelse



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


{{$logs->links()}}


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



@endsection