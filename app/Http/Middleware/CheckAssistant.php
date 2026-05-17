<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAssistant
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Non authentifié.'
            ], 401);
        }

        // L'assistant ET le dentiste peuvent passer
        if (!in_array(auth()->user()->role, ['DENTIST', 'ASSISTANT'])) {
            return response()->json([
                'message' => 'Accès refusé.'
            ], 403);
        }

        return $next($request);
    }
}