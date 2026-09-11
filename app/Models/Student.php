<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Student extends Model {
 protected $fillable=['user_id','name','roll_number','grade','parent_name','parent_email','parent_mobile','alternate_contact','login_user_id'];
 public function user(){ return $this->belongsTo(User::class); }
 public function loginUser(){ return $this->belongsTo(User::class,'login_user_id'); }
 public function assessments(){ return $this->hasMany(Assessment::class); }
}
