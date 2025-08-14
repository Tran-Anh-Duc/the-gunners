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
        ]);
        // chạy cho tất cả route nhóm api
        $middleware->group('api', [
            'SetLocale',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
