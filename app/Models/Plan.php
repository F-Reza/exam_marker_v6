<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Plan extends Model
{


protected $fillable=[

'name',
'slug',
'price',
'billing_period',
'features',
'limits',
'active'

];



protected function casts(): array
{

return [

'features'=>'array',

'limits'=>'array',

'active'=>'boolean',

'price'=>'decimal:2'

];

}



public function users()
{

return $this->hasMany(User::class);

}


}