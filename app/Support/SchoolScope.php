<?php

namespace App\Support;

use App\Models\scctcust;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /**
     * Scope Master Kelas per sekolah admin.
     * mst_kelas.kelompok = mst_sekolah.CODE01 (bukan mst_kelas.unit).
     */
    public static function applyKelas($query, ?string $code = null, string $column = 'kelompok'): void
    {
        $code ??= self::codeFromUser();
        if (blank($code)) {
            return;
        }

        $query->where($column, $code);
    }

    /**
     * Scope siswa: CODE01 sekolah + kelas (CODE03) harus milik sekolah yang sama (mst_kelas.kelompok).
     */
    public static function applyStudent($query, string $table = 'scctcust', ?string $code = null): void
    {
        $code ??= self::codeFromUser();
        if (blank($code)) {
            return;
        }

        $query->where($table . '.CODE01', $code)
            ->where(function ($scoped) use ($table, $code) {
                $scoped->whereNull($table . '.CODE03')
                    ->orWhereExists(function ($exists) use ($table, $code) {
                        $exists->select(DB::raw(1))
                            ->from('mst_kelas')
                            ->whereColumn('mst_kelas.id', $table . '.CODE03')
                            ->where('mst_kelas.kelompok', $code);
                    });
            });
    }

    public static function studentAccessible(?scctcust $siswa, ?string $code = null): bool
    {
        $code ??= self::codeFromUser();
        if (blank($code)) {
            return true;
        }

        if (!$siswa) {
            return false;
        }

        if (trim((string) ($siswa->CODE01 ?? '')) !== $code) {
            return false;
        }

        $kelasId = trim((string) ($siswa->CODE03 ?? ''));
        if ($kelasId === '') {
            return true;
        }

        return DB::connection('DATA_MYSQL')
            ->table('mst_kelas')
            ->where('id', $kelasId)
            ->where('kelompok', $code)
            ->exists();
    }

    public static function denyStudentMessage(?scctcust $siswa, string $label = 'Siswa'): ?string
    {
        if (self::studentAccessible($siswa)) {
            return null;
        }

        $authCode = self::codeFromUser();

        return "{$label} bukan dari unit Anda (CODE01: " . ($siswa->CODE01 ?? '-') . ', kelas: ' . ($siswa->CODE03 ?? '-') . ', unit admin: ' . ($authCode ?? '-') . ')';
    }
}
