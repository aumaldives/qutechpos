<?php

namespace Modules\Superadmin\Exports;

use App\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class TransactionReportExport implements WithMultipleSheets
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        // Get date range based on filter type
        $dateRange = $this->getDateRange();
        
        return [
            new TransactionsSheet($dateRange),
            new OverviewSheet($dateRange)
        ];
    }

    /**
     * Get date range based on request parameters
     */
    private function getDateRange()
    {
        $type = $this->request->get('type', 'quarterly');
        $year = $this->request->get('year', date('Y'));
        
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
                    'start' => Carbon::parse($this->request->get('start_date'))->startOfDay(),
                    'end' => Carbon::parse($this->request->get('end_date'))->endOfDay()
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