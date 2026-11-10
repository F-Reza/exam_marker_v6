<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('inspire', function () { $this->comment(Illuminate\Foundation\Inspiring::quote()); })->purpose('Display an inspiring quote');

Artisan::command('assessments:recover-stalled',function(){
 $count=\App\Models\Assessment::where('status','processing')->where('processing_started_at','<',now()->subMinutes(10))->update(['status'=>'failed','processing_token'=>(string)\Illuminate\Support\Str::uuid(),'processing_claim_token'=>null,'processing_completed_at'=>now(),'error_message'=>'The checking worker stopped. Please retry this paper.']);
 $this->info($count.' stalled paper(s) are ready to retry.');
})->purpose('Recover abandoned processing attempts without changing approved results');
\Illuminate\Support\Facades\Schedule::command('assessments:recover-stalled')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('exam-marker:create-admin {email} {--name=Platform Admin}',function(){
 $email=$this->argument('email');if(!filter_var($email,FILTER_VALIDATE_EMAIL)||\App\Models\User::where('email',$email)->exists()){$this->error('Supply an unused valid email address.');return 1;}
 $password=$this->secret('Choose an administrator password (at least 12 characters)');if(strlen((string)$password)<12){$this->error('Password must have at least 12 characters.');return 1;}
 $user=\App\Models\User::create(['name'=>$this->option('name'),'email'=>$email,'password'=>$password,'user_type'=>'platform_admin','is_admin'=>true,'account_status'=>'active','plan_id'=>\App\Models\Plan::where('slug','mode-3')->value('id')]);
 $user->forceFill(['email_verified_at'=>now()])->save();$this->info('Administrator created.');
})->purpose('Provision an administrator without demo credentials');
