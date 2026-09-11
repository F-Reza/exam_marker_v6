<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Transaction extends Model
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



    protected $casts = [

        'amount' => 'decimal:2',

        'provider_payload' => 'array',

        'paid_at' => 'datetime',

        'created_at' => 'datetime',

        'updated_at' => 'datetime'

    ];




    /*
|--------------------------------------------------------------------------
| Auto Generate Unique Transaction ID
|--------------------------------------------------------------------------
*/

protected static function boot()
{

    parent::boot();



    static::creating(function ($transaction) {


        if (!$transaction->transaction_id) {


            do {

                $transaction->transaction_id =
                    'TXN-'
                    . now()->format('YmdHis')
                    . '-'
                    . strtoupper(
                        str()->random(6)
                    );


                $exists = self::where(
                    'transaction_id',
                    $transaction->transaction_id
                )->exists();


            } while ($exists);



        }


    });


}



    /*
    |--------------------------------------------------------------------------
    | User Relationship
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }





    /*
    |--------------------------------------------------------------------------
    | Plan Relationship
    |--------------------------------------------------------------------------
    */

    public function plan()
    {
        return $this->belongsTo(
            Plan::class
        );
    }


}