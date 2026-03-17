<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    public static function secret(): string
    {
        return env('JWT_SECRET');
    }

    public static function ttl(): int
    {
        return (int) env('JWT_TTL', 7200);
    }

    public static function generateToken($user)
    {
        $membership = $user->relationLoaded('businessMemberships')
            ? $user->businessMemberships->first()
            : $user->activeBusinessMemberships()->first();

        $now = time();
        $exp = $now + self::ttl();

        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => $user->id,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'business_id' => $membership?->business_id,
                'role' => $membership?->role,
                'is_owner' => (bool) ($membership?->is_owner ?? false),
            ],
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
