<?php

namespace App\Http\Controllers;


use App\Models\Plan;
use App\Models\User;
use App\Models\PaymentGatewaySetting;
use App\Models\PaymentTransaction;

use App\Services\Payments\GatewayManager;

use Illuminate\Http\Request;
use Illuminate\Support\Str;



class PaymentController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Checkout Page
    |--------------------------------------------------------------------------
    */

    public function checkout(Request $request, Plan $plan)
    {


        abort_if(
            $request->user()->owner_user_id,
            403,
            'Only the organisation owner can purchase a plan.'
        );


        abort_unless(
            $plan->active,
            404
        );



        $gateways = PaymentGatewaySetting::where(
            'enabled',
            true
        )->get();



        return view(
            'billing.checkout',
            compact(
                'plan',
                'gateways'
            )
        );


    }





    /*
    |--------------------------------------------------------------------------
    | Start Payment
    |--------------------------------------------------------------------------
    */

    public function pay(
        Request $request,
        Plan $plan,
        GatewayManager $manager
    )
    {


        abort_if(
            $request->user()->owner_user_id,
            403
        );



        $data = $request->validate([

            'gateway'=>'required|string'

        ]);





        $gateway = PaymentGatewaySetting::where(
            'provider',
            $data['gateway']
        )
        ->where(
            'enabled',
            true
        )
        ->firstOrFail();






        /*
        |--------------------------------------------------------------------------
        | Create Transaction
        |--------------------------------------------------------------------------
        */


        $transaction = PaymentTransaction::create([


            'user_id'=>$request->user()->id,


            'plan_id'=>$plan->id,


            'gateway'=>$gateway->provider,


            'reference'=>

                'EM'
                .now()->format('YmdHis')
                .Str::upper(
                    Str::random(7)
                ),



            'amount'=>$plan->price,


            'currency'=>'INR',


            'status'=>'pending'


        ]);





        $transaction->load([
            'user',
            'plan'
        ]);






        try{


            $start = $manager->begin(
                $transaction,
                $gateway
            );





            $transaction->update([


                'provider_reference'=>
                    $start['provider_reference']
                    ??
                    null,



                'provider_payload'=>
                    $start['payload']
                    ??
                    null


            ]);







            if(
                ($start['type'] ?? null)
                ===
                'form'
            )
            {

                return view(
                    'billing.gateway-form',
                    [
                        'start'=>$start,
                        'tx'=>$transaction,
                        'gateway'=>$gateway
                    ]
                );

            }






            if(empty($start['url']))
            {

                throw new \RuntimeException(
                    'Gateway did not return checkout URL.'
                );

            }






            return redirect()->away(
                $start['url']
            );



        }


        catch(\Throwable $e)
        {


            report($e);



            $transaction->update([


                'status'=>'failed',


                'provider_payload'=>[

                    'error'=>$e->getMessage()

                ]


            ]);




            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    $e->getMessage()
                );


        }



    }







    /*
    |--------------------------------------------------------------------------
    | Payment Callback
    |--------------------------------------------------------------------------
    */

    public function callback(
        Request $request,
        string $provider,
        string $reference,
        GatewayManager $manager
    )
    {



        $transaction =
            PaymentTransaction::where(
                'reference',
                $reference
            )
            ->firstOrFail();







        $gateway =
            PaymentGatewaySetting::where(
                'provider',
                $provider
            )
            ->firstOrFail();






        try{


            $result =
                $manager->verify(
                    $provider,
                    $request->all(),
                    $gateway
                );







            $transaction->update([


                'status'=>
                    $result['paid']
                    ?
                    'paid'
                    :
                    'failed',




                'provider_reference'=>

                    $result['reference']
                    ??
                    $transaction->provider_reference,




                'provider_payload'=>

                    $result['payload']
                    ??
                    $request->all(),




                'paid_at'=>

                    $result['paid']
                    ?
                    now()
                    :
                    null



            ]);









            if($result['paid'])
            {


                $owner =
                    User::findOrFail(
                        $transaction->user_id
                    );





                $owner->update([

                    'plan_id'=>$transaction->plan_id

                ]);







                if(
                    $owner->isCoaching()
                )
                {


                    $owner
                    ->staffAccounts()
                    ->update([

                        'plan_id'=>$transaction->plan_id

                    ]);


                }






                return redirect()
                    ->route('billing.index')
                    ->with(
                        'success',
                        'Payment confirmed. Your plan has been upgraded.'
                    );


            }





            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Payment was not confirmed.'
                );



        }



        catch(\Throwable $e)
        {


            report($e);



            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Payment verification failed: '
                    .$e->getMessage()
                );


        }



    }







    /*
    |--------------------------------------------------------------------------
    | Cancel Payment
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        string $reference
    )
    {



        PaymentTransaction::where(
            'reference',
            $reference
        )
        ->where(
            'user_id',
            $request->user()->organisationOwnerId()
        )
        ->update([

            'status'=>'cancelled'

        ]);





        return redirect()
            ->route('billing.index')
            ->with(
                'error',
                'Payment cancelled.'
            );


    }



}