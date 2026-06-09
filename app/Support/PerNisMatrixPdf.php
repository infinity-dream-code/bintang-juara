<?php

namespace App\Support;

class PerNisMatrixPdf
{
    /**
     * Hitung jumlah kolom post (periode x nama akun) dari data tagihan/penerimaan.
     */
    public static function countPostColumns(array $records): int
    {
        $count = 0;
        $periods = collect($records)
            ->sortBy([
                ['BILLAC', 'asc'],
                ['FUrutan', 'asc'],
            ])
            ->groupBy('BILLAC');

        foreach ($periods as $items) {
            $unique = collect($items)
                ->map(fn($item) => ($item['KodePost'] ?? '-') . '|' . ($item['NamaAkun'] ?? ($item['BILLNM'] ?? '-')))
                ->unique();
            $count += max($unique->count(), 1);
        }

        return $count;
    }

    /**
     * Ukuran font & padding menyesuaikan jumlah kolom agar tidak kepotong.
     *
     * @return array{fontSize: float, headerFontSize: float, cellPadding: string, postHeaderMaxWidth: string}
     */
    public static function layoutForColumns(int $postColCount): array
    {
        if ($postColCount > 28) {
            return [
                'fontSize' => 4.5,
                'headerFontSize' => 4,
                'cellPadding' => '1px 2px',
                'postHeaderMaxWidth' => '52px',
            ];
        }
        if ($postColCount > 20) {
            return [
                'fontSize' => 5,
                'headerFontSize' => 4.5,
                'cellPadding' => '2px 2px',
                'postHeaderMaxWidth' => '60px',
            ];
        }
        if ($postColCount > 14) {
            return [
                'fontSize' => 6,
                'headerFontSize' => 5,
                'cellPadding' => '2px 3px',
                'postHeaderMaxWidth' => '72px',
            ];
        }
        if ($postColCount > 8) {
            return [
                'fontSize' => 7,
                'headerFontSize' => 6,
                'cellPadding' => '3px 3px',
                'postHeaderMaxWidth' => '85px',
            ];
        }

        return [
            'fontSize' => 9,
            'headerFontSize' => 7,
            'cellPadding' => '4px 5px',
            'postHeaderMaxWidth' => '110px',
        ];
    }

    /**
     * Ukuran kertas landscape: A4, A3, atau custom width (points).
     *
     * @return array{0: int, 1: int, 2: float, 3: float}|string
     */
    public static function paperSize(int $postColCount): array|string
    {
        if ($postColCount <= 12) {
            return 'a4';
        }

        if ($postColCount <= 20) {
            return 'a3';
        }

        // Lebar dinamis: kolom tetap + kolom post + kolom total (~38pt per kolom post)
        $widthPt = max(1190, 220 + ($postColCount * 36) + 70);
        $heightPt = 842;

        return [0, 0, $widthPt, $heightPt];
    }

    public static function paperOrientation(int $postColCount): string
    {
        return 'landscape';
    }
}
