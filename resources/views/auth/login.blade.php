@extends('layouts.app') @section('title','Login') @section('content')
<section class="auth-page single">
    <form method="post" class="auth-card">@csrf<h2>Welcome back</h2>
        <p>Login to manage and check papers.</p><label>Email address<input class="input" type="email" name="email"
                value="{{old('email')}}" required></label><label>Password<input class="input" type="password"
                name="password" required></label><label class="check"><input type="checkbox" name="remember"> Remember
            me</label><button style="color: ghostwhite; font-weight: 600;" class="btn full">Login</button>
        <p class="center"><a href="{{route('password.request')}}">Forgot Password?</a></p>
        <p class="center">New to Exam Marker? <a href="{{route('register')}}">Register</a></p>
    </form>
</section>@endsection