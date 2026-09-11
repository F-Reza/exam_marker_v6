@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<section class="auth-page single">
    <form method="post" action="{{route('password.email')}}" class="auth-card">@csrf
        <h2>Forgot Password</h2>
        <p>Enter your email and we will send a password reset link.</p>
        <label>Email address<input class="input" type="email" name="email" required></label>
        <button style="color: ghostwhite; font-weight: 600;" class="btn full">Send Reset Link</button>
        <p class="center"><a href="{{route('login')}}">Back to Login</a></p>
    </form>
</section>
@endsection