<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $e)
    {
        if ($e instanceof OAuthServerException && $e->getCode() === 9) {
            $this->logOAuthServerException($e);
            return;
        }

        parent::report($e);
    }

    /**
     * Log OAuth server exception in JSON format.
     *
     * @param  \League\OAuth2\Server\Exception\OAuthServerException  $exception
     * @return void
     */
    private function logOAuthServerException(OAuthServerException $exception)
    {
        $logData = [
            'error' => 'OAuth Server Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            // Add any other relevant information
        ];

        Log::error(json_encode($logData));
    }

    /**
     * Convert an authentication exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'code' => 401,
            'success' => false,
            'message' => $exception->getMessage(),
        ], 401);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function ($request, OAuthServerException $e) {
            return response()->json([
                'code' => $e->getCode(),
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        });
        $this->renderable(function (NotFoundHttpException $e, $request) {
            // if ($request->is('api/*')) {
            $response = [
                'code' => 404,
                'success' => false,
                'message' => 'Object not found',
            ];
            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }
            return response()->json($response, 404);
            // }
        });
    }
}
