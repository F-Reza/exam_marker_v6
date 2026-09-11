<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class PaymentTransaction extends Model
{

    use HasFactory;


    protected $table = 'payment_transactions';



    protected $fillable = [

        'transaction_id',

        'user_id',

        'plan_id',

        'gateway',

        'reference',

        'provider_reference',

        'amount',

        'currency',

        'status',

        'provider_payload',

        'paid_at'

    ];



    protected function casts(): array
    {

        return [

            'provider_payload'=>'array',

            'paid_at'=>'datetime',

            'amount'=>'decimal:2'

        ];

    }




    /*
    |--------------------------------------------------------------------------
    | Auto Generate Transaction ID
    |--------------------------------------------------------------------------
    */


    protected static function boot()
    {

        parent::boot();


        static::creating(function($transaction){


            if(!$transaction->transaction_id)
            {


                do{


                    $transaction->transaction_id =
                        'TXN-'
                        .now()->format('YmdHis')
                        .'-'
                        .strtoupper(
                            Str::random(8)
                        );



                    $exists = self::where(
                        'transaction_id',
                        $transaction->transaction_id
                    )->exists();



                }
                while($exists);



            }


        });


    }





    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }



    public function plan()
    {

        return $this->belongsTo(
            Plan::class
        );

    }


}