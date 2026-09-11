@extends('layouts.app')


@section('title','Payment Gateways')

@section('page-title','Payment Gateways')



@section('content')



<div class="welcome">

<div>

<span class="eyebrow">
PLATFORM ADMINISTRATOR
</span>


<h1>
Payment Gateway Setup
</h1>


<p>
Configure online payment providers securely.
</p>


</div>

</div>





<div class="gateway-admin-grid">



@foreach($gateways as $g)


<form method="POST"
class="panel gateway-admin-card"
action="{{route('admin.payment-gateways.update',$g)}}">


@csrf
@method('PUT')



<div class="gateway-card-head">


<div class="gateway-logo">

{{strtoupper(substr($g->label,0,2))}}

</div>


<div>

<h2>
{{$g->label}}
</h2>

<small>
{{$g->provider}}
</small>

</div>



<label class="switch">

<input
type="checkbox"
name="enabled"
value="1"
@checked($g->enabled)
>

<span></span>

</label>


</div>





<label>

Environment

<select
class="input"
name="environment">


<option value="sandbox"
@selected($g->environment=='sandbox')>

Sandbox

</option>


<option value="live"
@selected($g->environment=='live')>

Live

</option>


</select>

</label>





@php

$c=$g->credentials ?? [];

@endphp







@if($g->provider=='paypal')


<h3>PayPal</h3>


<input class="input"
name="client_id"
placeholder="Client ID">


<input class="input"
type="password"
name="client_secret"
placeholder="Client Secret">



@endif







@if($g->provider=='payu')


<h3>PayU</h3>


<input class="input"
name="merchant_key"
placeholder="Merchant Key">


<input class="input"
type="password"
name="salt"
placeholder="Salt">


@endif







@if($g->provider=='phonepe')


<h3>PhonePe</h3>


<input class="input"
name="merchant_id"
placeholder="Merchant ID">


<input class="input"
name="authorization"
placeholder="Authorization">


<input class="input"
name="checkout_url"
placeholder="Checkout URL">


@endif







@if($g->provider=='paytm')


<h3>Paytm</h3>


<input class="input"
name="merchant_id"
placeholder="Merchant ID">


<input class="input"
name="checkout_url"
placeholder="Checkout URL">


@endif







@if($g->provider=='bkash')


<h3>
bKash Bangladesh
</h3>


<input class="input"
name="app_key"
placeholder="App Key">


<input class="input"
type="password"
name="app_secret"
placeholder="App Secret">


<input class="input"
name="username"
placeholder="Username">


<input class="input"
type="password"
name="password"
placeholder="Password">


<input class="input"
name="base_url"
value="https://tokenized.pay.bka.sh/v1.2.0-beta">


@endif







@if($g->provider=='card')


<h3>
Debit / Credit Card
</h3>



<select class="input"
name="card_provider">


<option value="stripe">
Stripe
</option>


<option value="sslcommerz">
SSLCommerz Bangladesh
</option>


<option value="razorpay">
Razorpay
</option>


</select>




<input class="input"
name="publishable_key"
placeholder="Publishable Key">


<input class="input"
type="password"
name="secret_key"
placeholder="Secret Key">


@endif





<button class="btn full">

Save {{$g->label}}

</button>




</form>


@endforeach



</div>



@endsection