@extends('layouts.app')
@section('title','Verify email')
@section('content')
<div class="panel"><h1>Verify your email address</h1><p>Open the verification link sent to your email before using your workspace.</p>
<form method="post" action="{{route('verification.send')}}">@csrf<button class="btn">Send verification link again</button></form></div>
@endsection
