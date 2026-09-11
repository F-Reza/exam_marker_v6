<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
 use HasFactory, Notifiable;
 protected $fillable=['name','email','mobile','password','user_type','organisation_name','avatar_path','notification_preferences','is_admin','plan_id','free_check_used','owner_user_id','account_status','role_title'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed','is_admin'=>'boolean','free_check_used'=>'boolean','notification_preferences'=>'array']; }
 public function plan(){ return $this->belongsTo(Plan::class); }
 public function assessments(){ return $this->hasMany(Assessment::class); }
 public function students(){ return $this->hasMany(Student::class); }
 public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
 public function staffAccounts(){ return $this->hasMany(User::class,'owner_user_id'); }
 public function teamMembers(){ return $this->hasMany(TeamMember::class,'owner_id'); }
 public function supportTickets(){ return $this->hasMany(SupportTicket::class); }
 public function isPlatformAdmin(): bool { return (bool)$this->is_admin || $this->user_type==='platform_admin' || $this->role_title==='platform_admin'; }
 public function hasFeature(string $key): bool { if($this->isPlatformAdmin()) return true; return (bool)($this->plan?->features[$key] ?? false); }
 public function limit(string $key, int $default=0): int { return (int)($this->plan?->limits[$key] ?? $default); }
 public function organisationOwnerId(): int { return (int)($this->owner_user_id ?: $this->id); }
 public function isStudent(): bool { return $this->user_type==='student'; }
 public function isTeacher(): bool { return $this->user_type==='teacher'; }
 public function isCoaching(): bool { return $this->user_type==='coaching'; }
 public function organisationAssessments(){ return Assessment::query()->where('user_id',$this->organisationOwnerId()); }
 public function organisationStudents(){ return Student::query()->where('user_id',$this->organisationOwnerId()); }
 public function canAccessAssessment(Assessment $assessment): bool {
   if($this->is_admin) return true;
   if($this->isStudent()) return $assessment->user_id===$this->id || $assessment->student?->login_user_id===$this->id;
   return $assessment->user_id===$this->organisationOwnerId() || $assessment->assigned_teacher_id===$this->id;
 }
}
