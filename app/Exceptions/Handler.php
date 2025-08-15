<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e)
    {
        // Ép tất cả request API/Postman trả JSON
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => explode("\n", $e->getTraceAsString()),
            ], 500);
        }

        return parent::render($request, $e);
    }
}
