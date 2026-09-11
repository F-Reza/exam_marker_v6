<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PaymentGatewaySetting extends Model
{


protected $fillable = [

    'provider',
    'label',
    'enabled',
    'environment',
    'credentials',
    'options',

];





protected function casts(): array
{

    return [

        'enabled'=>'boolean',

        'credentials'=>'encrypted:array',

        'options'=>'array',

    ];

}



}