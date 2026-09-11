<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{


public function up(): void
{


DB::table('payment_gateway_settings')
->insertOrIgnore([


[
'provider'=>'bkash',
'label'=>'bKash Bangladesh',
'enabled'=>false,
'environment'=>'sandbox',
'credentials'=>json_encode([]),
'options'=>json_encode([]),
'created_at'=>now(),
'updated_at'=>now()
],



[
'provider'=>'card',
'label'=>'Debit / Credit Card',
'enabled'=>false,
'environment'=>'sandbox',
'credentials'=>json_encode([]),
'options'=>json_encode([]),
'created_at'=>now(),
'updated_at'=>now()
]


]);



}



public function down(): void
{


DB::table('payment_gateway_settings')
->whereIn(
'provider',
[
'bkash',
'card'
]
)
->delete();


}


};