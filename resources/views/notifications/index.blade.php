@extends('layouts.app')
@section('title','Notifications') @section('page-title','Notifications')
@section('content')
<div class="welcome"><div><span class="eyebrow">ACTIVITY</span><h1>Notifications</h1><p>Paper-processing, report and billing updates.</p></div><form method="post" action="{{route('notifications.read')}}">@csrf<button class="btn ghost">Mark all read</button></form></div>
<div class="panel notification-list">@forelse($notifications as $n)<article class="{{$n->read_at?'':'unread'}}"><span class="notification-icon">●</span><div><b>{{$n->data['title']??'Notification'}}</b><p>{{$n->data['message']??'Account update.'}}</p><small>{{$n->created_at->diffForHumans()}}</small></div></article>@empty<div class="empty">No notifications yet.</div>@endforelse<div class="pagination">{{$notifications->links()}}</div></div>
@endsection
