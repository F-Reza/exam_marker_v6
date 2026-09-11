<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AuditLog extends Model
{


protected $fillable=[

'user_id',
'organisation_user_id',
'action',
'subject_type',
'subject_id',
'meta',
'ip_address'

];



protected function casts(): array
{

return [

'meta'=>'array'

];

}




public function user()
{
    return $this->belongsTo(User::class);
}



public function subject()
{
    return $this->morphTo();
}



}