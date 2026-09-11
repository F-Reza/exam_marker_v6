@extends('layouts.app')

@section('title','Support Center')

@section('page-title','Support Center')


@section('content')


<div class="welcome">

<h1>Support Center</h1>

<p>
Manage customer support requests and feedback.
</p>

</div>



<div class="panel">


<div class="support-tabs">


<a href="{{route('admin.support.index',['tab'=>'inbox'])}}"
class="{{request('tab','inbox')=='inbox'?'active':''}}">
Support Inbox
</a>



<a href="{{route('admin.support.index',['tab'=>'feedback'])}}"
class="{{request('tab')=='feedback'?'active':''}}">
Customer Feedback
</a>



<a href="{{route('admin.support.index',['tab'=>'solutions'])}}"
class="{{request('tab')=='solutions'?'active':''}}">
Solutions Provided
</a>


</div>





<div class="support-content">



@if($tab=='inbox')


{{-- <h2>Support Inbox</h2> --}}

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





@elseif($tab=='feedback')


{{-- <h2>Customer Feedback</h2> --}}


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







@elseif($tab=='solutions')


{{-- <h2>Solutions Provided</h2> --}}


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



@endif



</div>


</div>



@endsection