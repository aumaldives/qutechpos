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

class OtherTransactionsSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
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
        
        // Get subscriptions with MVR amounts
        $subscriptions = DB::table('subscriptions as s')
            ->join('business as b', 's.business_id', '=', 'b.id')
            ->select('s.package_price', 's.mvr_amount', 's.usd_to_mvr_rate')
            ->where('s.status', 'approved')
            ->where('s.package_price', '>', 0)
            ->where(function($query) {
                $query->whereNull('b.tax_number_1')
                      ->orWhere('b.tax_number_1', '');
            })
            ->whereBetween('s.created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->get();

        // Calculate total revenue in MVR
        $totalRevenueMvr = 0;
        foreach ($subscriptions as $subscription) {
            $mvrAmount = $subscription->mvr_amount;
            if (empty($mvrAmount) && $subscription->usd_to_mvr_rate) {
                $mvrAmount = round(floatval($subscription->package_price) * floatval($subscription->usd_to_mvr_rate), 2);
            } elseif (empty($mvrAmount)) {
                // Fallback to current exchange rate if no historical rate stored
                $currentRate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                $mvrAmount = round(floatval($subscription->package_price) * $currentRate, 2);
            }
            $totalRevenueMvr += $mvrAmount;
        }

        // Reverse calculate excluding GST from MVR amount
        $excludingGSTAmount = $totalRevenueMvr / (1 + ($gstPercentage / 100));

        return collect([[
            $this->settings['superadmin_tax_activity_number'] ?? '',
            round($excludingGSTAmount, 2),
            0, // Value of Zero-Rated Supplies
            0, // Value of Exempt Supplies
            0  // Value of Out-of-Scope Supplies
        ]]);
    }

    public function title(): string
    {
        return 'OtherTransactions';
    }

    public function headings(): array
    {
        return [
            'Your Taxable Activity No.',
            'Value of Supplies Subject to GST at 8% or 17% (excluding GST)',
            'Value of Zero-Rated Supplies',
            'Value of Exempt Supplies',
            'Value of Out-of-Scope Supplies'
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
                
                // Apply specific column alignments for data rows (no borders)
                if ($highestRow > 1) {
                    // Column B (GST Values) - Center alignment  
                    $sheet->getStyle("B2:B{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                          
                    // Columns C, D, E (Other supply values) - Center alignment
                    $sheet->getStyle("C2:E{$highestRow}")
                          ->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        ];
    }
}