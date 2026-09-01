<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovimientosExport implements FromView, ShouldAutoSize, WithEvents, WithStyles
{
    public function __construct(private readonly array $datos)
    {
    }

    public function view(): View
    {
        return view('exports.movimientos', $this->datos);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 18, 'color' => ['argb' => 'FF0F2A4A']]],
            2 => ['font' => ['color' => ['argb' => 'FF52677D']]],
            4 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF123C69']]],
            7 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF123C69']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A8');
                $sheet->setAutoFilter('A7:H7');
                $sheet->getStyle('A1:H' . $sheet->getHighestRow())->getAlignment()->setVertical('center');
                $sheet->getStyle('E8:F' . $sheet->getHighestRow())->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle('A7:H' . $sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setARGB('FFD7E1EC');
                $sheet->getRowDimension(1)->setRowHeight(34);

                if (! empty($this->datos['logoPath']) && file_exists($this->datos['logoPath'])) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo DIZANY');
                    $drawing->setPath($this->datos['logoPath']);
                    $drawing->setHeight(48);
                    $drawing->setCoordinates('A1');
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }
}
