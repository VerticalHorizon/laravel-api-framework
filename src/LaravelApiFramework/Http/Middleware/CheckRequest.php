<?php

namespace Karellens\LAF\Http\Middleware;

use Closure;
use Karellens\LAF\ApiResponse;

class CheckRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!$request->accepts(config('api.acceptable_headers')))
        {
            return (new ApiResponse())->error('Not Acceptable! No data received.', 400);
        }

        if(
            ($request->isMethod('post') || $request->isMethod('put'))
            &&
            !($request->isJson() || count($request->allFiles()))
        )
        {
            return (new ApiResponse())->error('Bad request! No data received.', 400);
        }

        return $next($request);
    }
}
