<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Superadmin\Exports\MiraReportExport;
use Carbon\Carbon;

class MiraReportsController extends Controller
{
    /**
     * Generate and export MIRA tax report
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Debug logging
            \Log::info('MIRA Export requested', ['params' => $request->all()]);
            
            // Generate filename
            $filename = $this->generateFilename($request);
            \Log::info('Generated filename: ' . $filename);
            
            // Create and download Excel file
            $export = new MiraReportExport($request);
            \Log::info('Export object created successfully');
            
            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('MIRA Export Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return a user-friendly error page instead of JSON for browser compatibility
            return response('<h1>Error Generating Report</h1><p>' . $e->getMessage() . '</p><p>Please check the logs for more details.</p>', 500)
                ->header('Content-Type', 'text/html');
        }
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
                $quarter = 'Q' . Carbon::now()->quarter;
                return "MIRA_Tax_Report_{$quarter}_{$year}.xlsx";
        }
    }
}