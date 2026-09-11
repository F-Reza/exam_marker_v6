<?php

namespace App\Http\Controllers;


use App\Models\PaymentGatewaySetting;
use Illuminate\Http\Request;



class AdminPaymentGatewayController extends Controller
{


public function index()
{

    return view(
        'admin.payment-gateways',
        [

        'gateways'=>PaymentGatewaySetting::
        orderBy('label')
        ->get()

        ]
    );

}





public function update(
Request $request,
PaymentGatewaySetting $gateway
)
{


$data=$request->validate([


'enabled'=>'nullable|boolean',


'environment'=>'required|in:sandbox,live',



// PAYPAL

'client_id'=>'nullable|string',

'client_secret'=>'nullable|string',




// PAYU

'merchant_key'=>'nullable|string',

'salt'=>'nullable|string',




// PHONEPE / PAYTM

'merchant_id'=>'nullable|string',

'authorization'=>'nullable|string',

'checkout_url'=>'nullable|string',




// BKASH

'app_key'=>'nullable|string',

'app_secret'=>'nullable|string',

'username'=>'nullable|string',

'password'=>'nullable|string',

'base_url'=>'nullable|string',




// CARD

'card_provider'=>'nullable|string',

'publishable_key'=>'nullable|string',

'secret_key'=>'nullable|string',



]);






$credentials=$gateway->credentials ?? [];






$fields=[


'client_id',

'client_secret',


'merchant_key',

'salt',


'merchant_id',

'authorization',

'checkout_url',



// bKash

'app_key',

'app_secret',

'username',

'password',

'base_url',



// Card

'card_provider',

'publishable_key',

'secret_key'


];







foreach($fields as $field)

{


if(

isset($data[$field])

&&

$data[$field] !== ''

)

{

$credentials[$field]=$data[$field];

}


}








$gateway->update([


'enabled'=>$request->boolean('enabled'),


'environment'=>$data['environment'],


'credentials'=>$credentials



]);






return back()->with(

'success',

$gateway->label.' settings saved successfully.'

);



}



}