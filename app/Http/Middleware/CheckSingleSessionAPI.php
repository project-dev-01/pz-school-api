<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Session;
use Illuminate\Support\Facades\DB;

class CheckSingleSessionAPI
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
        // return $next($request);
        // dd(Auth::guard('api')->user()->id);
        // $previous_session = Auth::guard('api')->user()->session_id;
       
        // if ($previous_session != \Session::getId()) {
        //     $accessToken = Auth::guard('api')->user()->token();
        //     $id = Auth::guard('api')->user()->id;
        //     // print_r($accessToken);
        //     // echo "-----";
        //     // print_r(Session::getId());
        //     // exit;
        //     \DB::table('oauth_refresh_tokens')->where('access_token_id', $accessToken->id)->update(['revoked' => 1]);
        //     $request->user()->token()->revoke();
        //     // $accessToken->revoked();

        //     Auth::guard('api')->user()->session_id = Session::getId();
        //     // echo "dsf";exit;    

        //     Auth::guard('api')->user()->save();
        //     // echo "dsf";exit;

        // }
        return $next($request);
    }
}
