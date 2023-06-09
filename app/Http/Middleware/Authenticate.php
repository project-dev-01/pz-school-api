<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        $response = [
            'code' => 401,
            'success' => false,
            'message' => "User is not authenticated",
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, 401);
        die();
        // $data = [
        //     "status" => "error",
        //     "code" => "Auth-004",
        //     "message" => "User is not authenticated"
        // ];
        // echo collect($data);
        // die();
    }
}
