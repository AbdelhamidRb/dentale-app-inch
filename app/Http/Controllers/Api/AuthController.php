<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.'
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('dental-token')->plainTextToken;
        return response()->json([
            'user'  => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                // Ajoute ces deux lignes
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'phone'  => $user->phone,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès.'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
