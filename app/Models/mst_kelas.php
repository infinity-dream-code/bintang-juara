<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mst_kelas extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = "mst_kelas";

    protected $primaryKey = "id";

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        "kelas",
        "jenjang",
        "unit",
        "kelompok",
    ];

    public static function getMstKelasAttributes(): array|object
    {
        return static::select(["id", "kelas", "jenjang", "unit"])
            ->orderByRaw("
                    CASE
                        WHEN unit LIKE '%SD%' THEN 1
                        WHEN unit LIKE '%SMP%' THEN 2
                        WHEN unit LIKE '%SMA%' THEN 3
                        ELSE 4
                    END
            ")
            ->orderByRaw("CASE WHEN jenjang REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, jenjang")
            ->orderBy("kelas")
            ->get();
    }

    /**
     * Cocokkan baris import Excel (unit, kelas/jenjang, kelompok) ke Master Kelas.
     * Excel: kelas = jenjang (7/VII), kelompok = kelas DB (A/B).
     */
    public static function findForImport(?string $unit, mixed $jenjang, ?string $kelompok): ?self
    {
        $unit = trim((string) $unit);
        $kelompok = trim((string) $kelompok);
        $jenjangText = trim((string) $jenjang);

        if ($unit === '' || $kelompok === '' || $jenjangText === '') {
            return null;
        }

        $jenjangCandidates = self::jenjangCandidates($jenjangText);

        return self::query()
            ->where(function ($query) use ($unit) {
                $query->whereRaw('UPPER(TRIM(unit)) = ?', [strtoupper($unit)])
                    ->orWhere('unit', 'like', '%' . $unit . '%');
            })
            ->where(function ($query) use ($jenjangCandidates) {
                $query->whereIn('jenjang', $jenjangCandidates);
                foreach ($jenjangCandidates as $candidate) {
                    $query->orWhereRaw('UPPER(TRIM(jenjang)) = ?', [strtoupper($candidate)]);
                }
            })
            ->where(function ($query) use ($kelompok) {
                $query->where('kelas', $kelompok)
                    ->orWhereRaw('UPPER(TRIM(kelas)) = ?', [strtoupper($kelompok)]);
            })
            ->first();
    }

    /** @return list<string> */
    public static function jenjangCandidates(mixed $jenjang): array
    {
        $value = trim((string) $jenjang);
        if ($value !== '' && is_numeric($value)) {
            $value = (string) (int) $value;
        }

        $numericToRoman = [
            '1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI',
            '7' => 'VII', '8' => 'VIII', '9' => 'IX', '10' => 'X', '11' => 'XI', '12' => 'XII',
        ];

        $candidates = array_values(array_filter([$value]));
        if (isset($numericToRoman[$value])) {
            $candidates[] = $numericToRoman[$value];
        }

        $romanToNumeric = array_flip($numericToRoman);
        if (isset($romanToNumeric[$value])) {
            $candidates[] = $romanToNumeric[$value];
        }

        return array_values(array_unique($candidates));
    }
}
