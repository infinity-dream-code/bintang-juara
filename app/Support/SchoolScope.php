<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Scope sekolah dari cyber_key.fid (= scctcust.CODE01).
 * fid kosong = akses semua sekolah.
 */
class SchoolScope
{
    public static function codeFromUser(mixed $user = null): ?string
    {
        $user ??= Auth::user();
        if (!$user) {
            return null;
        }

        $code = $user->sekolah ?? $user->unit ?? null;
        if ($code === null) {
            return null;
        }

        $code = trim((string) $code);

        return $code !== '' ? $code : null;
    }

    public static function codesFromUser(mixed $user = null): array
    {
        $code = self::codeFromUser($user);

        return $code !== null ? [$code] : [];
    }

    public static function apply($query, string $table = 'scctcust', ?string $code = null): void
    {
        $code ??= self::codeFromUser();
        if (blank($code)) {
            return;
        }

        $query->where($table . '.CODE01', $code);
    }
}
