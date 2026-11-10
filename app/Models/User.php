<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail {
 use HasFactory, Notifiable;
 protected $fillable=['name','email','mobile','password','user_type','organisation_name','avatar_path','notification_preferences','is_admin','plan_id','free_check_used','owner_user_id','account_status','role_title'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['email_verified_at'=>'datetime','subscription_started_at'=>'datetime','subscription_ends_at'=>'datetime','subscription_cancelled'=>'boolean','password'=>'hashed','is_admin'=>'boolean','free_check_used'=>'boolean','notification_preferences'=>'array']; }
 public function hasVerifiedEmail(): bool { return $this->isPlatformAdmin() || !is_null($this->email_verified_at); }
 public function plan(){ return $this->belongsTo(Plan::class); }
 public function assessments(){ return $this->hasMany(Assessment::class); }
 public function students(){ return $this->hasMany(Student::class); }
 public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
 public function staffAccounts(){ return $this->hasMany(User::class,'owner_user_id'); }
 public function teamMembers(){ return $this->hasMany(TeamMember::class,'owner_id'); }
 public function supportTickets(){ return $this->hasMany(SupportTicket::class); }
 public function isPlatformAdmin(): bool { return (bool)$this->is_admin || $this->user_type==='platform_admin'; }
 public function effectivePlan(): ?Plan {
   $owner=$this->owner_user_id && !$this->isStudent() ? $this->owner : $this;
   if($owner?->subscription_ends_at?->isPast() && !$owner->isPlatformAdmin()) return Plan::where('slug','free')->first();
   return $owner?->plan ?? Plan::where('slug','free')->first();
 }
 public function hasFeature(string $key): bool { if($this->isPlatformAdmin()) return true; return (bool)($this->effectivePlan()?->features[$key] ?? false); }
 public function limit(string $key, int $default=0): int { return (int)($this->effectivePlan()?->limits[$key] ?? $default); }
 public function canAccessModule(string $module): bool {
   if($this->isPlatformAdmin()) return true;

   return match($module) {
     'dashboard', 'assessments', 'settings', 'notifications', 'support', 'billing' => true,
     'admin' => false,
     'student-portal' => $this->isStudent(),
     'teacher-workspace' => $this->isTeacher(),
     'students' => !$this->isStudent() && $this->hasFeature('student_records'),
     'student-import' => !$this->isStudent() && $this->hasFeature('student_records') && $this->hasFeature('bulk_student_import'),
     'bulk' => !$this->isStudent() && $this->hasFeature('bulk_answer_upload'),
     'analytics' => !$this->isStudent() && $this->hasFeature('graphs'),
     'reports' => $this->hasFeature('result_report_download'),
     'corrected-paper' => $this->hasFeature('corrected_paper_download'),
     'communications' => !$this->isStudent() && ($this->hasFeature('parent_email') || $this->hasFeature('parent_sms')),
     'parent-email' => !$this->isStudent() && $this->hasFeature('parent_email'),
     'parent-sms' => !$this->isStudent() && $this->hasFeature('parent_sms'),
     'team' => $this->isCoaching() && $this->hasFeature('multiple_teachers'),
     'teacher-assignment' => $this->isCoaching() && $this->hasFeature('teacher_assignment'),
     'result-edit' => !$this->isStudent() && ($this->hasFeature('manual_mark_editing') || $this->hasFeature('feedback_editing')),
     'result-finalise' => !$this->isStudent() && $this->hasFeature('manual_mark_editing'),
     'result-recheck' => !$this->isStudent() && $this->hasFeature('question_recheck'),
     default => false,
   };
 }
 public function organisationOwnerId(): int { return (int)($this->owner_user_id ?: $this->id); }
 public function isStudent(): bool { return $this->user_type==='student'; }
 public function isTeacher(): bool { return $this->user_type==='teacher'; }
 public function isCoaching(): bool { return $this->user_type==='coaching'; }
 public function organisationAssessments(){ return Assessment::query()->where('user_id',$this->organisationOwnerId()); }
 public function organisationStudents(){ return Student::query()->where('user_id',$this->organisationOwnerId()); }
 public function accessibleAssessments(): \Illuminate\Database\Eloquent\Builder {
   $query = Assessment::query();
   if($this->isPlatformAdmin()) return $query;
   if($this->isStudent()) {
     return $query->where(function($query) {
       $query->where('user_id', $this->id)
         ->orWhere(fn($linked) => $linked->whereIn('status', ['finalised', 'reported'])
           ->whereHas('student', fn($student) => $student->where('login_user_id', $this->id)));
     });
   }
   if($this->isTeacher()) {
     return $query->where(fn($query) => $query->where('user_id', $this->id)->orWhere('assigned_teacher_id', $this->id));
   }
   return $query->where('user_id', $this->organisationOwnerId());
 }
 public function canAccessAssessment(Assessment $assessment): bool {
   if($this->isPlatformAdmin()) return true;
   if($this->isStudent()) return $assessment->user_id===$this->id || (in_array($assessment->status, ['finalised', 'reported'], true) && $assessment->student?->login_user_id===$this->id);
   if($this->isTeacher()) return $assessment->user_id===$this->id || $assessment->assigned_teacher_id===$this->id;
   return $assessment->user_id===$this->organisationOwnerId();
 }
 public function canDeleteAssessment(Assessment $assessment): bool {
   return $this->isPlatformAdmin() || $assessment->user_id === $this->id;
 }
}
