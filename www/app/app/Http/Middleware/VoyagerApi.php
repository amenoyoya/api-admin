<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VoyagerApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // post された token と Session.voyager_api_token が一致しない場合は 403 Response
        if ($request->get('token') !== \Session::get('voyager_api_token')) {
            return response()->json(['response' => 'invalid token'], 403);
        }
        return $next($request);
    }
}
