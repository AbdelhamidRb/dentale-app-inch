<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'license' => ['required', 'file', 'max:64'],
        ], [
            'license.required' => 'Aucun fichier sélectionné.',
            'license.file'     => 'Le fichier est invalide.',
            'license.max'      => 'Le fichier est trop volumineux.',
        ]);

        file_put_contents(
            base_path('dental-app.lic'),
            file_get_contents($request->file('license')->getRealPath())
        );

        cache()->forget('license_check');

        return response()->json(['success' => true]);
    }
}
