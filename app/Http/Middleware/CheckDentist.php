<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckDentist
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isDentist()) {
            return response()->json([
                'message' => 'Accès réservé au dentiste.'
            ], 403);
        }

        return $next($request);
    }
}