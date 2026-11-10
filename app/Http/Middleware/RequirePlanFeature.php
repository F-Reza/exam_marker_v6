<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class RequirePlanFeature
{


public function handle(
    Request $request,
    Closure $next,
    string $feature
): Response
{


$user = $request->user();


if(!$user)
{
    abort(403);
}



if(!$user->hasFeature($feature))
{

    if ($request->isMethod('GET') && !$request->expectsJson()) {
        return redirect()->route('billing.index')->with(
            'error',
            'This feature is not included in your current plan. Compare the available plans below.'
        );
    }

    abort(
        403,
        'This feature is not included in your current plan.'
    );

}



return $next($request);


}


}
