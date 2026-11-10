<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class EnforceAccountStatus {
 public function handle(Request $request,Closure $next){
  if($request->routeIs('payments.callback'))return $next($request);
  $user=$request->user();
  if($user && ($user->account_status!=='active' || ($user->owner_user_id && $user->owner?->account_status!=='active'))){
   Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();
   if($request->expectsJson())return response()->json(['message'=>'This account or its organisation is inactive. Contact support.'],403);
   return redirect()->route('login')->withErrors(['email'=>'This account or its organisation is inactive. Contact support.']);
  }
  return $next($request);
 }
}
