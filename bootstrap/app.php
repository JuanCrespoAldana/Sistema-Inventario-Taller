<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            // Solo interviene en peticiones AJAX/JSON (fetch con Accept: application/json).
            // Las peticiones normales de navegación siguen su curso normal y usan las
            // vistas de resources/views/errors/ (404, 419, 500, etc.).
            if (!$request->expectsJson()) {
                return null;
            }

            // Los errores de validación ya los maneja Laravel de forma correcta
            // (devuelve los mensajes campo por campo, ya en español gracias a
            // lang/es/validation.php). No los tocamos aquí.
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException,
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 404,
                $e instanceof \Illuminate\Session\TokenMismatchException => 419,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException,
                $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => 403,
                default => 500,
            };

            $mensajes = [
                403 => 'No tienes permiso para realizar esta acción.',
                404 => 'El registro solicitado no está disponible.',
                419 => 'La sesión ha expirado. Recarga la página e inténtalo nuevamente.',
                500 => 'No fue posible completar la operación. Intenta nuevamente.',
            ];

            if ($status === 500) {
                report($e);
            }

            return response()->json(['mensaje' => $mensajes[$status]], $status);
        });
    })->create();
