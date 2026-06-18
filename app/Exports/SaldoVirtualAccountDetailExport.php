<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SaldoVirtualAccountDetailExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
  private const LAST_COL = 'G';

  public function __construct(
    private array $rows,
    private int $sectionTitleRow,
    private int $columnHeaderRow,
    private int $tableDataEndRow,
    private int $totalRow,
  ) {
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

        $sheet->getStyle('A3:A9')->applyFromArray([
          'font' => ['bold' => true],
        ]);

        $sheet->mergeCells("A{$this->sectionTitleRow}:{$lastCol}{$this->sectionTitleRow}");
        $sheet->getStyle("A{$this->sectionTitleRow}")->applyFromArray([
          'font' => ['bold' => true, 'size' => 11],
        ]);

        $sheet->getStyle("A{$this->columnHeaderRow}:{$lastCol}{$this->columnHeaderRow}")->applyFromArray([
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

        if ($this->tableDataEndRow >= $this->columnHeaderRow) {
          $sheet->getStyle("A{$this->columnHeaderRow}:{$lastCol}{$this->tableDataEndRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->getStyle("D{$this->columnHeaderRow}:E{$this->tableDataEndRow}")
          ->getNumberFormat()
          ->setFormatCode('"Rp" #,##0');

        $sheet->getStyle('B9')
          ->getNumberFormat()
          ->setFormatCode('"Rp" #,##0');

        $sheet->getStyle("D{$this->totalRow}:E{$this->totalRow}")
          ->getNumberFormat()
          ->setFormatCode('"Rp" #,##0');

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

        $sheet->getStyle("A{$this->columnHeaderRow}:{$lastCol}{$this->tableDataEndRow}")
          ->getAlignment()
          ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("D{$this->columnHeaderRow}:E{$this->tableDataEndRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("A{$this->columnHeaderRow}:A{$this->tableDataEndRow}")
          ->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(22);
      },
    ];
  }
}
