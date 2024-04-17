<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only log errors if we're in the local environment
        if (app()->environment('local')) {
            // Check if the response status code indicates an error (4xx or 5xx)
            $statusCode = $response->status();
            if ($statusCode >= 400 && $statusCode <= 599) {
                $log = [
                    'URI' => $request->getUri(),
                    'METHOD' => $request->getMethod(),
                    'REQUEST_BODY' => $request->all(),
                    'RESPONSE' => $response->getContent()
                ];

                Log::error(json_encode($log));
            }
        }
        
        return $response;
    }
}
