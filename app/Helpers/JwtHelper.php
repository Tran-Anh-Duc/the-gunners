<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Helper thao tác với JWT token.
 *
 * Lớp này gom các thao tác dùng chung cho auth và middleware:
 * - đọc secret và TTL từ env;
 * - tạo token từ thông tin user và membership;
 * - giải mã token;
 * - lấy thời điểm hết hạn từ payload.
 */
class JwtHelper
{
    public static function secret(): string
    {
        // Tách helper để các nơi phát hành hoặc kiểm tra token cùng dùng một nguồn secret.
        return env('JWT_SECRET');
    }

    public static function ttl(): int
    {
        // TTL mặc định 7200 giây, có thể override bằng biến môi trường.
        return (int) env('JWT_TTL', 7200);
    }

    public static function generateToken($user)
    {
        // Token luôn mang theo business hiện tại để middleware và service lấy scope nhanh.
        $membership = $user->relationLoaded('businessMemberships')
            ? $user->businessMemberships->first()
            : $user->activeBusinessMemberships()->first();
        $businessName = null;

        if ($membership) {
            $businessName = $membership->relationLoaded('business')
                ? $membership->business?->name
                : $membership->business()->value('name');
        }

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
                'business_name' => $businessName,
                'role' => $membership?->role,
                'is_owner' => (bool) ($membership?->is_owner ?? false),
            ],
        ];

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function decodeToken(string $token)
    {
        // Giải mã token bằng cùng secret đã dùng lúc ký.
        return JWT::decode($token, new Key(self::secret(), 'HS256'));
    }

    public static function getTokenExpiryFromPayload($payload)
    {
        // Trả về `exp` để logout có thể tính TTL của blacklist token.
        return $payload->exp ?? null;
    }
}
