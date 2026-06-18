<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SaldoVirtualAccountDetailExport implements FromArray, WithEvents, WithTitle
{
    private const LAST_COL = 'G';

    private array $rows = [];

    private int $infoStartRow = 0;

    private int $infoEndRow = 0;

    private int $sectionTitleRow = 0;

    private int $headerRow = 0;

    private int $firstDataRow = 0;

    private int $lastDataRow = 0;

    private int $totalRow = 0;

    public function __construct(
        private array $siswa,
        Collection $transactions,
        private int $totalDebet,
        private int $totalKredit,
        private int $saldo,
    ) {
        $this->buildRows($transactions);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Transaksi Saldo VA';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = self::LAST_COL;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$this->infoStartRow}:A{$this->infoEndRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->getStyle("B{$this->infoStartRow}:B{$this->infoEndRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->mergeCells("A{$this->sectionTitleRow}:{$lastCol}{$this->sectionTitleRow}");
                $sheet->getStyle("A{$this->sectionTitleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->getStyle("A{$this->headerRow}:{$lastCol}{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2F5597'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                if ($this->lastDataRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:{$lastCol}{$this->lastDataRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }

                $sheet->getStyle("A{$this->headerRow}:{$lastCol}{$this->lastDataRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A{$this->firstDataRow}:A{$this->lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("B{$this->firstDataRow}:C{$this->lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("D{$this->firstDataRow}:E{$this->lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("F{$this->firstDataRow}:{$lastCol}{$this->lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("A{$this->totalRow}:{$lastCol}{$this->totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA'],
                    ],
                ]);

                $sheet->getStyle("A{$this->totalRow}:{$lastCol}{$this->totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("C{$this->totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("D{$this->totalRow}:E{$this->totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $vaRow = $this->infoStartRow + 5;
                $nisRow = $this->infoStartRow;
                $sheet->setCellValueExplicit("B{$nisRow}", (string) ($this->siswa['nis'] ?? '-'), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$vaRow}", (string) ($this->siswa['nova'] ?? '-'), DataType::TYPE_STRING);

                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(24);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(22);
            },
        ];
    }

    private function buildRows(Collection $transactions): void
    {
        $this->rows = [
            ['Detail Saldo Virtual Account'],
            ['', '', '', '', '', '', ''],
        ];

        $this->infoStartRow = count($this->rows) + 1;

        $this->rows[] = ['NIS', (string) ($this->siswa['nis'] ?? '-')];
        $this->rows[] = ['Nama', (string) ($this->siswa['nama'] ?? '-')];
        $this->rows[] = ['Unit', (string) ($this->siswa['unit'] ?? '-')];
        $this->rows[] = ['Kelas', (string) ($this->siswa['kelas'] ?? '-')];
        $this->rows[] = ['Kelompok', (string) ($this->siswa['kelompok'] ?? '-')];
        $this->rows[] = ['No Virtual Account', (string) ($this->siswa['nova'] ?? '-')];
        $this->rows[] = ['Total Saldo', $this->formatRupiah($this->saldo)];

        $this->infoEndRow = count($this->rows);

        $this->rows[] = ['', '', '', '', '', '', ''];

        $this->rows[] = ['Riwayat Transaksi'];
        $this->sectionTitleRow = count($this->rows);

        $this->rows[] = ['No', 'Metode', 'Tanggal Transaksi', 'Debet', 'Kredit', 'No Ref', 'Trans No'];
        $this->headerRow = count($this->rows);

        $this->firstDataRow = $this->headerRow + 1;

        foreach ($transactions as $index => $trx) {
            $debet = (int) ($trx->DEBET ?? 0);
            $kredit = (int) ($trx->KREDIT ?? 0);

            $this->rows[] = [
                $index + 1,
                (string) ($trx->METODE ?? '-'),
                $trx->TRXDATE ? date('d-m-Y H:i:s', strtotime($trx->TRXDATE)) : '-',
                $debet > 0 ? $this->formatRupiah($debet) : '-',
                $kredit > 0 ? $this->formatRupiah($kredit) : '-',
                (string) ($trx->NOREFF ?? '-'),
                (string) ($trx->TRANSNO ?? '-'),
            ];
        }

        $this->lastDataRow = count($this->rows);

        $this->rows[] = [
            '',
            '',
            'Total',
            $this->formatRupiah($this->totalDebet),
            $this->formatRupiah($this->totalKredit),
            '',
            '',
        ];

        $this->totalRow = count($this->rows);
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
