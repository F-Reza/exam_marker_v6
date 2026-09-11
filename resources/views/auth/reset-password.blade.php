@extends('layouts.app')
@section('title','Reset Password')
@section('content')
<section class="auth-page single">
    <form method="post" action="{{route('password.update')}}" class="auth-card">@csrf
        <input type="hidden" name="token" value="{{$token}}">
        <h2>Reset Password</h2>
        <label>Email<input class="input" type="email" name="email" required></label>
        <label>New Password<input class="input" type="password" name="password" required></label>
        <label>Confirm Password<input class="input" type="password" name="password_confirmation" required></label>
        <button style="color: ghostwhite; font-weight: 600;" class="btn full">Reset Password</button>
    </form>
</section>
@endsection