@extends('layouts.app')
@section('title','Student invitation')
@section('content')<div class="panel"><h1>Link your student record</h1><p>Accept the invitation for {{$student->name}} from {{$student->user?->organisation_name?:$student->user?->name}} to view your published results.</p><form method="post" action="{{route('students.invitation.accept',$token)}}">@csrf<button class="btn">Accept invitation</button></form></div>@endsection
