@extends('layouts.app')
@section('title','Settings') @section('page-title','Settings')
@section('content')
<div class="welcome">
    <div><span class="eyebrow">ACCOUNT CONTROL</span>
        <h1>Profile Settings</h1>
        <p>Manage your profile, password and notifications.</p>
    </div>
</div>
<div class="settings-grid">
    <section class="panel form-card">
        <h2>Profile</h2>
        <form method="post" action="{{route('settings.profile')}}" class="form-grid">@csrf @method('PUT')
            <label>Full name<input class="input" name="name" value="{{old('name',$user->name)}}"
                    required></label><label>Email<input class="input" value="{{$user->email}}"
                    disabled></label><label>Mobile<input class="input" name="mobile"
                    value="{{old('mobile',$user->mobile)}}" required></label><label>Organisation<input class="input"
                    name="organisation_name" value="{{old('organisation_name',$user->organisation_name)}}"></label>
            <div class="span-2"><button class="btn">Save Profile</button></div>
        </form>
    </section>
    <section class="panel form-card">
        <h2>Change password</h2>
        <form method="post" action="{{route('settings.password')}}" class="form-grid">@csrf @method('PUT')
            <label class="span-2">Current password<input class="input" type="password" name="current_password"
                    required></label><label>New password<input class="input" type="password" name="password"
                    required></label><label>Confirm password<input class="input" type="password"
                    name="password_confirmation" required></label>
            <div class="span-2"><button class="btn">Update Password</button></div>
        </form>
    </section>
    <section class="panel form-card span-all">
        <h2>Notification preferences</h2>@php($prefs=$user->notification_preferences??[])<form method="post"
            action="{{route('settings.notifications')}}">@csrf @method('PUT')<div class="toggle-grid">
                <label class="toggle-row"><input type="checkbox" name="processing" value="1"
                        @checked($prefs['processing']??true)><span><b>Paper processing</b><small>Notify when checking
                            finishes or fails.</small></span></label>
                <label class="toggle-row"><input type="checkbox" name="reports" value="1"
                        @checked($prefs['reports']??true)><span><b>Reports</b><small>Notify when a result is
                            finalised.</small></span></label>
                <label class="toggle-row"><input type="checkbox" name="parent_delivery" value="1"
                        @checked($prefs['parent_delivery']??true)><span><b>Parent delivery</b><small>Notify when a
                            parent report is delivered.</small></span></label>
                <label class="toggle-row"><input type="checkbox" name="billing" value="1"
                        @checked($prefs['billing']??true)><span><b>Billing</b><small>Plan and usage
                            reminders.</small></span></label>
            </div><button class="btn">Save Preferences</button></form>
    </section>
</div>
@endsection