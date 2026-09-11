<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentAttachment extends Model {
 protected $fillable=['assessment_id','type','path','original_name'];
 public function assessment(){ return $this->belongsTo(Assessment::class); }
}
