<?php
namespace App\Http\Middleware;
use Closure;
class RequireRole {
 public function handle($request, Closure $next, ...$roles){
  abort_unless(auth()->check() && in_array(auth()->user()->user_type,$roles,true),403,'This area is not available for your account role.');
  return $next($request);
 }
}
