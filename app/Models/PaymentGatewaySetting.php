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





public function getCredentialsAttribute($value): array
{
    // Older gateway migrations stored an unconfigured account as plain empty JSON.
    if ($value === null || in_array($value, ['', '[]', '{}'], true)) {
        return [];
    }

    return json_decode($this->fromEncryptedString($value), true, 512, JSON_THROW_ON_ERROR);
}

protected function casts(): array
{

    return [

        'enabled'=>'boolean',

        'credentials'=>'encrypted:array',

        'options'=>'array',

    ];

}



}
