<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Recaptcha
{
    public static function verify(?string $token, string $action, ?string $ip = null): bool
    {
        if (empty($token)) {
            return false;
        }

        $secret = config('services.recaptcha.secret_key');
        if (empty($secret)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (! $response->ok()) {
            return false;
        }

        $data = $response->json();

        return ($data['success'] ?? false) === true
            && ($data['action'] ?? '') === $action
            && ($data['score'] ?? 0) >= 0.5;
    }
}
