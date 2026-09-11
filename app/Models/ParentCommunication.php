<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParentCommunication extends Model {
 protected $fillable=['assessment_id','student_id','recipient','channel','status','message','sent_at'];
 protected function casts():array{return ['sent_at'=>'datetime'];}
 public function assessment(){return $this->belongsTo(Assessment::class);} public function student(){return $this->belongsTo(Student::class);}
}