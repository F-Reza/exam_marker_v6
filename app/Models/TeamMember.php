<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeamMember extends Model {
 protected $fillable=['owner_id','name','email','mobile','role','active'];
 protected function casts():array{return ['active'=>'boolean'];}
 public function owner(){ return $this->belongsTo(User::class,'owner_id'); }
}
