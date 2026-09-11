@extends('layouts.app') @section('title','Plan Management') @section('page-title','Plan Management') @section('content')
<div class="welcome">
    <div>
        <h1>Plans and user access</h1>
        <p>Change prices, limits and feature permissions without editing code.</p>
    </div>
</div>
<div class="admin-gridX">
    <section class="panel">
        <div class="panel-head">
            <h2>Available Plans</h2>
        </div>@foreach($plans as $plan)<form method="post" action="{{route('admin.plans.update',$plan)}}"
            class="admin-plan">@csrf @method('PUT')<div class="admin-plan-head">
                <div><b>{{$plan->name}}</b><small>{{$plan->slug}}</small></div><span class="status active" style="height: 27px;">Active</span>
            </div>
            <div class="mini-form"><label>Price<input class="input" type="number" name="price"
                        value="{{$plan->price}}"></label><label>Paper limit<input class="input" type="number"
                        name="paper_check_limit"
                        value="{{$plan->limits['paper_check_limit']??1}}"></label><label>Student limit<input
                        class="input" type="number" name="student_limit"
                        value="{{$plan->limits['student_limit']??0}}"></label><label>Teacher limit<input class="input"
                        type="number" name="teacher_limit"
                        value="{{$plan->limits['teacher_limit']??1}}"></label><label>Email limit<input class="input"
                        type="number" name="email_limit"
                        value="{{$plan->limits['email_limit']??0}}"></label><label>Mobile/SMS limit<input class="input"
                        type="number" name="sms_limit" value="{{$plan->limits['sms_limit']??0}}"></label><label>Recheck
                    limit<input class="input" type="number" name="recheck_limit"
                        value="{{$plan->limits['recheck_limit']??0}}"></label></div>
            <div class="feature-checks">@foreach($plan->features as $key=>$on)<label><input type="checkbox"
                        name="features[{{$key}}]" value="1" @checked($on)> {{ucwords(str_replace('_','
                    ',$key))}}</label>@endforeach</div><button class="btn small">Update Plan</button>
        </form>@endforeach
    </section>
    {{-- <aside class="panel assign-card">
        <h2>Assign Plan</h2>
        <form method="post" action="{{route('admin.plans.assign')}}">@csrf<label>User<select class="input"
                    name="user_id">@foreach($users as $u)<option value="{{$u->id}}">{{$u->name}} · {{$u->email}}
                    </option>@endforeach</select></label><label>Plan<select class="input" name="plan_id">@foreach($plans
                    as $p)<option value="{{$p->id}}">{{$p->name}}</option>@endforeach</select></label><button
                class="btn full">Save Assignment</button></form>
    </aside> --}}
</div>@endsection