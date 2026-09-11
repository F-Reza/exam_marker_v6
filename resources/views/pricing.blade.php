@extends('layouts.app') @section('title','Plans & Pricing') @section('content')
<section class="page-hero"><span class="eyebrow">PLANS & PRICING</span>
    <h1>Choose the power you need</h1>
    <p>Start free, then upgrade from marks-only checking to full coaching-class communication.</p>
</section>
<section class="pricing-grid">
    @php $plans=\App\Models\Plan::where('active',true)->orderBy('price')->get(); @endphp
    @foreach($plans as $plan)
        <article class="price-card {{$plan->slug==='mode-2'?'popular':''}}">
            @if($plan->slug==='mode-2')<span class="ribbon">Most Popular</span>@endif
            <span class="plan-kicker">{{$plan->slug==='free'?'TRY ONCE':strtoupper($plan->slug)}}</span>
            <h2>{{$plan->name}}</h2>
            <div class="price">₹{{number_format((float)$plan->price)}}<small>/{{$plan->billing_period}}</small></div>
            <ul>
                <li>✓ {{$plan->limits['paper_check_limit']??1}} paper checks</li>
                <li>✓ Question-wise marks</li>
                <li>✓ Total marks and feedback</li>
                @if($plan->features['graphs']??false)<li>✓ Percentage, grade and graphs</li>@endif
                @if($plan->features['manual_mark_editing']??false)<li>✓ Manual mark editing</li>@endif
                @if($plan->features['result_report_download']??false)
                    <li>✓ Download result report</li>
                @else
                    <li class="off">× No report downloads</li>
                @endif
                @if($plan->features['corrected_paper_download']??false)
                    <li>✓ Download corrected paper</li>
                @else
                    <li class="off">× No corrected-paper download</li>
                @endif
                @if($plan->features['parent_email']??false)
                    <li>✓ Parent communication</li>
                @else
                    <li class="off">× No parent communication</li>
                @endif
                @if($plan->features['multiple_teachers']??false)
                    <li>✓ Multiple teachers</li>
                @else
                    <li class="off">× Single user/teacher</li>
                @endif
            </ul>
            <a style="{{ $plan->slug === 'free' ? '' : 'color: ghostwhite; font-weight: 600;' }}" 
               class="btn {{$plan->slug==='free'?'ghost':''}} full"
               href="{{auth()->check()?($plan->slug==='free'?route('dashboard'):route('payments.checkout',$plan)):route('register')}}">
               {{$plan->slug==='free'?'Try Free':'Choose Plan'}}
            </a>
        </article>
    @endforeach
</section>
@endsection