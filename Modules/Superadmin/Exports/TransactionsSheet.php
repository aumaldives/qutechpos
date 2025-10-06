<?php

namespace Modules\Superadmin\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class TransactionsSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $dateRange;

    public function __construct($dateRange)
    {
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        return DB::table('subscriptions as s')
            ->join('business as b', 's.business_id', '=', 'b.id')
            ->select(
                'b.tax_number_1 as customer_tin',
                'b.name as customer_name',
                's.id as invoice_no',
                's.created_at as invoice_date',
                's.package_price',
                's.mvr_amount',
                's.usd_to_mvr_rate'
            )
            ->where('s.status', 'approved')
            ->where('s.package_price', '>', 0) // Exclude free trials
            ->whereBetween('s.created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->orderBy('s.created_at')
            ->get()
            ->map(function ($item) {
                // Use MVR amount if available, otherwise convert USD to MVR
                $mvrAmount = $item->mvr_amount;
                if (empty($mvrAmount) && $item->usd_to_mvr_rate) {
                    $mvrAmount = round(floatval($item->package_price) * floatval($item->usd_to_mvr_rate), 2);
                } elseif (empty($mvrAmount)) {
                    // Fallback to current exchange rate if no historical rate stored
                    $currentRate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                    $mvrAmount = round(floatval($item->package_price) * $currentRate, 2);
                }
                
                return [
                    !empty($item->customer_tin) ? $item->customer_tin : 'NIL',
                    $item->customer_name,
                    'SUB-' . str_pad($item->invoice_no, 6, '0', STR_PAD_LEFT),
                    Carbon::parse($item->invoice_date)->format('d/m/Y'),
                    $mvrAmount,
                    0 // Value of Zero-Rated Supplies - Always 0
                ];
            });
    }

    public function title(): string
    {
        return 'Transactions';
    }

    public function headings(): array
    {
        return [
            'Customer TIN',
            'Customer Name',
            'Invoice No',
            'Invoice Date',
            'Payment Amount (MVR)',
            'Value of Zero-Rated Supplies'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF2E7D32']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Apply specific column alignments
                if ($highestRow > 1) {
                    // Column A (Customer TIN) - Center alignment
                    $sheet->getStyle("A2:A{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    // Column E (Payment Amount) - Right alignment for numbers
                    $sheet->getStyle("E2:E{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                          
                    // Column F (Zero-Rated Supplies) - Center alignment
                    $sheet->getStyle("F2:F{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                          
                    // Format payment amounts as currency
                    $sheet->getStyle("E2:E{$highestRow}")
                          ->getNumberFormat()
                          ->setFormatCode('#,##0.00');
                }
            }
        ];
    }
}