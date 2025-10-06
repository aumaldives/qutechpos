<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Superadmin\Exports\TransactionReportExport;
use Modules\Superadmin\Exports\TransactionsSheet;
use Carbon\Carbon;

class TransactionReportsController extends Controller
{
    /**
     * Display transaction report page
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        return view('superadmin::reports.transaction_reports');
    }

    /**
     * Get transaction data for table preview
     */
    public function getData(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $dateRange = $this->getDateRange($request);
        
        // Get transaction data
        $transactions = DB::table('subscriptions as s')
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
            ->whereBetween('s.created_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('s.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                // Calculate MVR amount using same logic as export
                $mvrAmount = $item->mvr_amount;
                if (empty($mvrAmount) && $item->usd_to_mvr_rate) {
                    $mvrAmount = round(floatval($item->package_price) * floatval($item->usd_to_mvr_rate), 2);
                } elseif (empty($mvrAmount)) {
                    $currentRate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                    $mvrAmount = round(floatval($item->package_price) * $currentRate, 2);
                }
                
                return [
                    'customer_tin' => !empty($item->customer_tin) ? $item->customer_tin : 'NIL',
                    'customer_name' => $item->customer_name,
                    'invoice_no' => 'SUB-' . str_pad($item->invoice_no, 6, '0', STR_PAD_LEFT),
                    'invoice_date' => Carbon::parse($item->invoice_date)->format('d/m/Y'),
                    'payment_amount' => number_format($mvrAmount, 2),
                    'zero_rated' => '0.00'
                ];
            });

        // Calculate summary stats
        $totalTransactions = $transactions->count();
        $totalRevenue = $transactions->sum(function($item) {
            return (float) str_replace(',', '', $item['payment_amount']);
        });
        
        $stats = [
            'total_transactions' => number_format($totalTransactions),
            'total_revenue' => number_format($totalRevenue, 2),
            'average_transaction' => $totalTransactions > 0 ? number_format($totalRevenue / $totalTransactions, 2) : '0.00',
            'period' => Carbon::parse($dateRange['start'])->format('d/m/Y') . ' - ' . Carbon::parse($dateRange['end'])->format('d/m/Y')
        ];

        return response()->json([
            'success' => true,
            'data' => $transactions->values(),
            'stats' => $stats
        ]);
    }

    /**
     * Export transaction report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        // Generate filename
        $filename = $this->generateFilename($request);
        
        // Create and download Excel file
        return Excel::download(new TransactionReportExport($request), $filename);
    }

    /**
     * Generate filename based on request
     */
    private function generateFilename($request)
    {
        $type = $request->get('type', 'quarterly');
        $year = $request->get('year', date('Y'));
        
        switch ($type) {
            case 'q1':
                return "Transaction_Report_Q1_{$year}.xlsx";
            case 'q2':
                return "Transaction_Report_Q2_{$year}.xlsx";
            case 'q3':
                return "Transaction_Report_Q3_{$year}.xlsx";
            case 'q4':
                return "Transaction_Report_Q4_{$year}.xlsx";
            case 'custom':
                $start = $request->get('start_date');
                $end = $request->get('end_date');
                return "Transaction_Report_Custom_{$start}_to_{$end}.xlsx";
            default:
                $quarter = 'Q' . Carbon::now()->quarter;
                return "Transaction_Report_{$quarter}_{$year}.xlsx";
        }
    }

    /**
     * Get date range based on request parameters
     */
    private function getDateRange($request)
    {
        $type = $request->get('type', 'quarterly');
        $year = $request->get('year', date('Y'));
        
        switch ($type) {
            case 'q1':
                return [
                    'start' => Carbon::create($year, 1, 1)->startOfDay(),
                    'end' => Carbon::create($year, 3, 31)->endOfDay()
                ];
            case 'q2':
                return [
                    'start' => Carbon::create($year, 4, 1)->startOfDay(),
                    'end' => Carbon::create($year, 6, 30)->endOfDay()
                ];
            case 'q3':
                return [
                    'start' => Carbon::create($year, 7, 1)->startOfDay(),
                    'end' => Carbon::create($year, 9, 30)->endOfDay()
                ];
            case 'q4':
                return [
                    'start' => Carbon::create($year, 10, 1)->startOfDay(),
                    'end' => Carbon::create($year, 12, 31)->endOfDay()
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($request->get('start_date'))->startOfDay(),
                    'end' => Carbon::parse($request->get('end_date'))->endOfDay()
                ];
            default: // quarterly (current quarter)
                $now = Carbon::now();
                $currentQuarter = $now->quarter;
                
                $quarterMonths = [
                    1 => [1, 3], 2 => [4, 6], 3 => [7, 9], 4 => [10, 12]
                ];
                
                return [
                    'start' => Carbon::create($now->year, $quarterMonths[$currentQuarter][0], 1)->startOfDay(),
                    'end' => Carbon::create($now->year, $quarterMonths[$currentQuarter][1], 1)->endOfMonth()->endOfDay()
                ];
        }
    }
}