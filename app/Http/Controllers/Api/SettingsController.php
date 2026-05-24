<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(string $key)
    {
        return response()->json([
            'key'   => $key,
            'value' => Setting::get($key),
        ]);
    }

    public function update(Request $request, string $key)
    {
        $request->validate(['value' => 'required|string|max:1000']);
        Setting::set($key, $request->value);

        return response()->json([
            'message' => 'Paramètre mis à jour.',
            'key'     => $key,
            'value'   => $request->value,
        ]);
    }
}
