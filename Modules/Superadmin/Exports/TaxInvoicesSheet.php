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

class TaxInvoicesSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $dateRange;
    protected $settings;

    public function __construct($dateRange, $settings)
    {
        $this->dateRange = $dateRange;
        $this->settings = $settings;
    }

    public function collection()
    {
        $gstPercentage = floatval($this->settings['superadmin_gst_percentage'] ?? 8);
        
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
            ->whereNotNull('b.tax_number_1')
            ->where('b.tax_number_1', '!=', '')
            ->whereBetween('s.created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->orderBy('s.created_at')
            ->get()
            ->map(function ($item) use ($gstPercentage) {
                // Use MVR amount if available, otherwise convert USD to MVR
                $mvrAmount = $item->mvr_amount;
                if (empty($mvrAmount) && $item->usd_to_mvr_rate) {
                    $mvrAmount = round(floatval($item->package_price) * floatval($item->usd_to_mvr_rate), 2);
                } elseif (empty($mvrAmount)) {
                    // Fallback to current exchange rate if no historical rate stored
                    $currentRate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                    $mvrAmount = round(floatval($item->package_price) * $currentRate, 2);
                }
                
                // Reverse calculate excluding GST from MVR amount
                $taxInclusiveAmount = floatval($mvrAmount);
                $excludingGSTAmount = $taxInclusiveAmount / (1 + ($gstPercentage / 100));
                
                return [
                    $item->customer_tin,
                    $item->customer_name,
                    'SUB-' . str_pad($item->invoice_no, 6, '0', STR_PAD_LEFT),
                    Carbon::parse($item->invoice_date)->format('d/m/Y'),
                    round($excludingGSTAmount, 2),
                    0, // Value of Zero-Rated Supplies
                    0, // Value of Exempt Supplies
                    0, // Value of Out-of-Scope Supplies
                    $this->settings['superadmin_tax_activity_number'] ?? ''
                ];
            });
    }

    public function title(): string
    {
        return 'TaxInvoices';
    }

    public function headings(): array
    {
        return [
            'Customer TIN',
            'Customer Name',
            'Invoice No',
            'Invoice Date',
            'Value of Supplies Subject to GST at 8% or 17% (excluding GST)',
            'Value of Zero-Rated Supplies',
            'Value of Exempt Supplies',
            'Value of Out-of-Scope Supplies',
            'Your Taxable Activity No.'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF366092']
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
                    // Column A (Customer TIN) - Left alignment
                    $sheet->getStyle("A2:A{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    
                    // Column E (GST Values) - Center alignment  
                    $sheet->getStyle("E2:E{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                          
                    // Columns F, G, H (Other supply values) - Center alignment
                    $sheet->getStyle("F2:H{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Remove any existing borders from data rows (keep only header borders)
            }
        ];
    }
}