<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function network()
    {
        $localIp = null;
        $privatePattern = '/^(192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/';

        $vhost = dirname(dirname(base_path()))
               . DIRECTORY_SEPARATOR . 'etc'
               . DIRECTORY_SEPARATOR . 'apache2'
               . DIRECTORY_SEPARATOR . 'sites-enabled'
               . DIRECTORY_SEPARATOR . 'dental-app-inch.conf';

        if (file_exists($vhost)) {
            $content = @file_get_contents($vhost);
            if ($content && preg_match('/ServerAlias[^\n]+?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $content, $m)) {
                $localIp = $m[1];
            }
        }

        if (!$localIp) {
            foreach (@gethostbynamel(gethostname()) ?: [] as $ip) {
                if (preg_match($privatePattern, $ip)) { $localIp = $ip; break; }
            }
        }

        if (!$localIp) {
            $out = [];
            @exec('ipconfig', $out);
            foreach ($out as $line) {
                if (preg_match('/(?:Adresse IPv4|IPv4 Address)[^:]*:\s*([\d.]+)/', $line, $m)) {
                    $ip = trim($m[1]);
                    if (preg_match($privatePattern, $ip)) { $localIp = $ip; break; }
                }
            }
        }

        return response()->json([
            'ip'  => $localIp,
            'url' => $localIp ? "http://{$localIp}" : null,
        ]);
    }

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
