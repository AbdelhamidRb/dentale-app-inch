<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sur les routes API, ne jamais rediriger vers route('login') → retourne 401 JSON
        $middleware->redirectGuestsTo(fn ($request) => null);

        $middleware->alias([
            'dentist'   => \App\Http\Middleware\CheckDentist::class,
            'assistant' => \App\Http\Middleware\CheckAssistant::class,
            'license'   => \App\Http\Middleware\CheckLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toutes les exceptions API → JSON propre
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null; // laisser les routes web gérer leurs propres erreurs
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Données invalides.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Ressource introuvable.'], 404);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json(['message' => 'Accès refusé.'], 403);
            }

            // Toute autre exception → 500 (message générique en prod)
            $debug = config('app.debug');
            return response()->json([
                'message' => $debug ? $e->getMessage() : 'Erreur serveur inattendue.',
                'type'    => $debug ? get_class($e) : null,
            ], 500);
        });
    })->create();
