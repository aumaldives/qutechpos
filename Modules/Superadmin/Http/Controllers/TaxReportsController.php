<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Superadmin\Exports\MiraReportExport;

class TaxReportsController extends Controller
{
    /**
     * Display tax report page (MIRA reports)
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        return view('superadmin::reports.tax_reports');
    }

    /**
     * Generate and export MIRA tax report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        // Generate filename
        $filename = $this->generateFilename($request);
        
        // Create and download Excel file
        return Excel::download(new MiraReportExport($request), $filename);
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
                return "MIRA_Tax_Report_Q1_{$year}.xlsx";
            case 'q2':
                return "MIRA_Tax_Report_Q2_{$year}.xlsx";
            case 'q3':
                return "MIRA_Tax_Report_Q3_{$year}.xlsx";
            case 'q4':
                return "MIRA_Tax_Report_Q4_{$year}.xlsx";
            case 'custom':
                $start = $request->get('start_date');
                $end = $request->get('end_date');
                return "MIRA_Tax_Report_Custom_{$start}_to_{$end}.xlsx";
            default:
                $quarter = 'Q' . \Carbon\Carbon::now()->quarter;
                return "MIRA_Tax_Report_{$quarter}_{$year}.xlsx";
        }
    }
}