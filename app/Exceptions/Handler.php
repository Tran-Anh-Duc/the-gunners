<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => false,
                'code' => 'login_failed',
                'message' => __('messages.user.user_login_failed'),
                'errors' => $e->errors(),
            ], 422);
        }

        if (!config('app.debug')) {
            return response()->json([
                'status' => false,
                'code' => 'server_error',
                'message' => __('messages.server_error'),
            ], 500);
        }

        return parent::render($request, $e);
    }
}
