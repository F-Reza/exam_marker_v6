@extends('layouts.app')


@section('title','Transaction Details')


@section('page-title','Transaction Details')



@section('content')



<div class="welcome">

<div>

<h1>
💳 Transaction Details
</h1>


<p>
Complete payment information.
</p>


</div>

</div>





<div class="panel"
style="padding:25px;">



<h3>
Transaction Information
</h3>


<table class="table">


<tr>

<th>
Transaction ID
</th>

<td>

{{$transaction->transaction_id ?? 'N/A'}}

</td>

</tr>




<tr>

<th>
Database ID
</th>

<td>

#{{$transaction->id}}

</td>

</tr>





<tr>

<th>
User
</th>

<td>

{{$transaction->user?->name ?? 'Guest'}}

<br>

{{$transaction->user?->email}}

</td>

</tr>





<tr>

<th>
Plan
</th>

<td>

{{$transaction->plan?->name ?? 'N/A'}}

</td>

</tr>





<tr>

<th>
Gateway
</th>

<td>

{{ucfirst($transaction->gateway ?? 'Manual')}}

</td>

</tr>





<tr>

<th>
Payment Reference
</th>

<td>

{{$transaction->reference ?? 'N/A'}}

</td>

</tr>





<tr>

<th>
Provider Reference
</th>

<td>

{{$transaction->provider_reference ?? 'N/A'}}

</td>

</tr>





<tr>

<th>
Amount
</th>

<td>

{{$transaction->currency}}

{{number_format($transaction->amount,2)}}

</td>

</tr>





<tr>

<th>
Status
</th>

<td>


<span class="status status-{{strtolower($transaction->status)}}">

{{ucfirst($transaction->status)}}

</span>


</td>

</tr>





<tr>

<th>
Paid Date
</th>

<td>

{{$transaction->paid_at?->format('d M Y H:i') ?? 'Not Paid'}}

</td>

</tr>





<tr>

<th>
Created
</th>

<td>

{{$transaction->created_at?->format('d M Y H:i')}}

</td>

</tr>



</table>





<br>


<a href="{{route('admin.transactions.index')}}"
class="btn">

← Back to Transactions

</a>



</div>




@endsection