<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'type' => 'https://example.com/problems/validation-error',
                'title' => 'Validation Error',
                'status' => 422,
                'detail' => 'Data transaksi yang dikirim tidak valid.',
                'errors' => $e->errors(),
            ], 422, [
                'Content-Type' => 'application/problem+json',
            ]);
        });

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'type' => 'https://example.com/problems/not-found',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Transaksi affiliate tidak ditemukan.',
            ], 404, [
                'Content-Type' => 'application/problem+json',
            ]);
        });
    })
    ->create();
