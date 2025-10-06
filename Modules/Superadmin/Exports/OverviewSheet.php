<?php

namespace Modules\Superadmin\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class OverviewSheet implements FromCollection, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected $dateRange;

    public function __construct($dateRange)
    {
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        // Get all subscription data for the period
        $subscriptions = DB::table('subscriptions as s')
            ->join('business as b', 's.business_id', '=', 'b.id')
            ->select(
                's.package_price', 
                's.mvr_amount', 
                's.usd_to_mvr_rate',
                's.created_at',
                'b.tax_number_1',
                's.paid_via'
            )
            ->where('s.status', 'approved')
            ->where('s.package_price', '>', 0)
            ->whereBetween('s.created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->get();

        // Calculate statistics
        $totalSubscriptions = $subscriptions->count();
        $totalRevenueMvr = 0;
        $businessesWithTin = 0;
        $businessesWithoutTin = 0;
        $paymentMethods = [];

        foreach ($subscriptions as $subscription) {
            // Calculate MVR amount
            $mvrAmount = $subscription->mvr_amount;
            if (empty($mvrAmount) && $subscription->usd_to_mvr_rate) {
                $mvrAmount = round(floatval($subscription->package_price) * floatval($subscription->usd_to_mvr_rate), 2);
            } elseif (empty($mvrAmount)) {
                $currentRate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                $mvrAmount = round(floatval($subscription->package_price) * $currentRate, 2);
            }
            $totalRevenueMvr += $mvrAmount;

            // Count businesses with/without TIN
            if (!empty($subscription->tax_number_1)) {
                $businessesWithTin++;
            } else {
                $businessesWithoutTin++;
            }

            // Count payment methods
            $method = $subscription->paid_via ?? 'Unknown';
            if (!isset($paymentMethods[$method])) {
                $paymentMethods[$method] = 0;
            }
            $paymentMethods[$method]++;
        }

        $averageTransaction = $totalSubscriptions > 0 ? ($totalRevenueMvr / $totalSubscriptions) : 0;

        // Format period
        $periodLabel = Carbon::parse($this->dateRange['start'])->format('d/m/Y') . ' - ' . 
                      Carbon::parse($this->dateRange['end'])->format('d/m/Y');

        // Build the overview data
        $data = collect([
            ['Transaction Report Overview', ''],
            ['Report Period', $periodLabel],
            ['Generated On', Carbon::now()->format('d/m/Y H:i:s')],
            ['', ''],
            ['SUMMARY STATISTICS', ''],
            ['Total Transactions', number_format($totalSubscriptions)],
            ['Total Revenue (MVR)', number_format($totalRevenueMvr, 2)],
            ['Average Transaction (MVR)', number_format($averageTransaction, 2)],
            ['', ''],
            ['BUSINESS BREAKDOWN', ''],
            ['Businesses with TIN', number_format($businessesWithTin)],
            ['Businesses without TIN', number_format($businessesWithoutTin)],
            ['TIN Coverage %', $totalSubscriptions > 0 ? number_format(($businessesWithTin / $totalSubscriptions) * 100, 1) . '%' : '0%'],
            ['', ''],
            ['PAYMENT METHODS', ''],
        ]);

        // Add payment method breakdown
        foreach ($paymentMethods as $method => $count) {
            $percentage = $totalSubscriptions > 0 ? (($count / $totalSubscriptions) * 100) : 0;
            $data->push([
                ucfirst($method), 
                number_format($count) . ' (' . number_format($percentage, 1) . '%)'
            ]);
        }

        $data->push(['', '']);
        $data->push(['PERIOD ANALYSIS', '']);
        $data->push(['First Transaction', $totalSubscriptions > 0 ? Carbon::parse($subscriptions->min('created_at'))->format('d/m/Y') : 'N/A']);
        $data->push(['Last Transaction', $totalSubscriptions > 0 ? Carbon::parse($subscriptions->max('created_at'))->format('d/m/Y') : 'N/A']);
        
        // Calculate daily average
        $daysDiff = Carbon::parse($this->dateRange['start'])->diffInDays(Carbon::parse($this->dateRange['end'])) + 1;
        $dailyAverage = $daysDiff > 0 ? ($totalSubscriptions / $daysDiff) : 0;
        $data->push(['Daily Average', number_format($dailyAverage, 1) . ' transactions/day']);

        return $data;
    }

    public function title(): string
    {
        return 'Overview';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF1976D2']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Section headers
            'A5' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF2E7D32']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE8F5E8']
                ]
            ],
            'A10' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF2E7D32']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE8F5E8']
                ]
            ],
            'A15' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF2E7D32']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE8F5E8']
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge cells for the main title
                $sheet->mergeCells('A1:B1');
                
                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(20);
                
                // Apply borders to the whole data range
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:B{$highestRow}")
                      ->getBorders()
                      ->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);
                
                // Right align the value column
                $sheet->getStyle("B1:B{$highestRow}")
                      ->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                      
                // Left align the label column
                $sheet->getStyle("A1:A{$highestRow}")
                      ->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}