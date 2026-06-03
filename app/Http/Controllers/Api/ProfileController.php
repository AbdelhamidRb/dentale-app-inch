<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // ─── GET /api/profile ─────────────────────────────────────────
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'phone'  => $user->phone,
            'role'   => $user->role,
            // URL complète de l'avatar ou null
            'avatar' => $user->avatar
                ? Storage::url($user->avatar)
                : null,
        ]);
    }

    // ─── PUT /api/profile ─────────────────────────────────────────
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ], [
            'name.required'  => 'Le nom est obligatoire.',
            'name.max'       => 'Le nom ne peut pas dépasser 100 caractères.',
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email'    => "L'adresse e-mail n'est pas valide.",
            'email.unique'   => "Cette adresse e-mail est déjà utilisée par un autre compte.",
            'phone.max'      => 'Le numéro de téléphone est trop long.',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'phone'  => $user->phone,
                'role'   => $user->role,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
            ]
        ]);
    }

    // ─── POST /api/profile/password ───────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'new_password.required'     => 'Le nouveau mot de passe est obligatoire.',
            'new_password.min'          => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'new_password.confirmed'    => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $user = $request->user();

        // Vérifie que l'ancien mot de passe est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json(['message' => 'Mot de passe modifié.']);
    }

    // ─── POST /api/profile/avatar ─────────────────────────────────
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Veuillez sélectionner une photo.',
            'avatar.image'    => 'Le fichier doit être une image.',
            'avatar.mimes'    => 'La photo doit être au format JPG, PNG ou WEBP.',
            'avatar.max'      => 'La photo ne peut pas dépasser 2 Mo.',
        ]);

        $user = $request->user();

        // Supprime l'ancien avatar si existe
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Sauvegarde le nouveau dans storage/app/public/avatars/
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Photo mise à jour.',
            'avatar'  => Storage::url($path)
        ]);
    }
}
