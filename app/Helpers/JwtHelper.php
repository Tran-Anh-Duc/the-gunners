<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class JwtHelper
{
    public static function secret(): string
    {
        return env('JWT_SECRET');
    }

    public static function ttl(): int
    {
        return (int) env('JWT_TTL', 3600);
    }

    public static function generateToken($user)
    {
        $now = time();
        $exp = $now + self::ttl();

        $payload = [
            'iss' => config('app.url'), // issuer
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => $user->id,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function decodeToken(string $token)
    {
        return JWT::decode($token, new Key(self::secret(), 'HS256'));
    }

    public static function getTokenExpiryFromPayload($payload)
    {
        return $payload->exp ?? null;
    }
}
