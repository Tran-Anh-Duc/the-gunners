<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //$middleware->append(\App\Http\Middleware\JwtMiddleware::class);
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'SetLocale'  => \App\Http\Middleware\SetLocale::class,
            'ForceJson'=>\App\Http\Middleware\ForceJsonResponse::class,
        ]);
        // chạy cho tất cả route nhóm api
        $middleware->group('api', [
            'SetLocale',
            'ForceJson',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            $trace = $e->getTrace();
            $firstTrace = $trace[0] ?? null;

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'file'    => $firstTrace['file'] ?? $e->getFile(),
                'line'    => $firstTrace['line'] ?? $e->getLine(),
                'trace'   => $firstTrace,
            ], 500);
        });
    })->create();

//->withExceptions(function (Exceptions $exceptions) {
//    $exceptions->render(function (Throwable $e, $request) {
//        $trace = $e->getTrace();
//        $firstTrace = $trace[0] ?? null;
//
//        return response()->json([
//            'error'   => true,
//            'message' => $e->getMessage(),
//            'file'    => $firstTrace['file'] ?? $e->getFile(),
//            'line'    => $firstTrace['line'] ?? $e->getLine(),
//            'trace'   => $firstTrace,
//        ], 500);
//    });

//////////
//->withExceptions(function (Exceptions $exceptions) {
//    $exceptions->render(function (Throwable $e, $request) {
//        if ($request->expectsJson()) {
//
//            // Lấy trace đầu tiên (nếu có)
//            $trace = $e->getTrace();
//            $firstTrace = $trace[0] ?? null;
//
//            return response()->json([
//                'error'   => true,
//                'message' => $e->getMessage(),
//                'file'    => $firstTrace['file'] ?? $e->getFile(),
//                'line'    => $firstTrace['line'] ?? $e->getLine(),
//                'trace'   => $firstTrace,
//            ], 500);
//        }
//    });
