<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UsageRecord extends Model {
 protected $fillable=['user_id','type','quantity','reference_type','reference_id','meta'];
 protected function casts(): array { return ['meta'=>'array']; }
 public function user(){ return $this->belongsTo(User::class); }
}
