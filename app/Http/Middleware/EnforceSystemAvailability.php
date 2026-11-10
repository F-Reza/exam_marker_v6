<?php
namespace App\Http\Middleware;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
class EnforceSystemAvailability {
 public function handle(Request $request,Closure $next){
  if(SystemSetting::enabled('maintenance_mode',false) && !$request->user()?->isPlatformAdmin() && !$request->is('login') && !$request->routeIs('login','logout','password.*','payments.callback','verification.*'))
   return response('Exam Marker is undergoing maintenance. Please try again shortly.',503)->header('Retry-After','300');
  return $next($request);
 }
}
