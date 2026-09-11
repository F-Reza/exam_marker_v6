@extends('layouts.app') @section('title','Create Account') @section('content')
<section class="auth-page">
    <div class="auth-copy"><span class="eyebrow">JOIN EXAM MARKER</span>
        <h1>Start checking papers with confidence.</h1>
        <p>Your first paper check is free. Upgrade only when you need more capacity and advanced tools.</p>
        <ul>
            <li>One-page QP, MS and WA upload</li>
            <li>Question-wise marks and feedback</li>
            <li>Teacher-controlled final results</li>
        </ul>
    </div>
    <form method="post" class="auth-card">@csrf<h2>Create your account</h2>
        <p>Register to start checking papers.</p><label>Full name<input class="input" name="name"
                value="{{old('name')}}" required></label><label>Email address<input class="input" type="email"
                name="email" value="{{old('email')}}" required></label><label>Mobile number<input class="input"
                name="mobile" value="{{old('mobile')}}" required></label><label>Password<input class="input"
                type="password" name="password" required></label><label>Confirm password<input class="input"
                type="password" name="password_confirmation" required></label><label>I am a<select class="input"
                name="user_type">
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="coaching">Coaching Class</option>
            </select></label><button style="color: ghostwhite; font-weight: 600;" class="btn full">Create Free Account</button>
        <p class="center">Already registered? <a href="{{route('login')}}">Login</a></p>
    </form>
</section>@endsection