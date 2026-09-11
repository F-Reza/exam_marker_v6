<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AiSetting extends Model
{


protected $fillable=[

'provider',
'model',
'api_key',
'status'

];


protected $casts=[

'api_key'=>'encrypted'

];


}