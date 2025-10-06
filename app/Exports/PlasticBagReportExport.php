<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlasticBagReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $data;
    private $business;
    private $start_date;
    private $end_date;

    public function __construct($data, $business, $start_date, $end_date)
    {
        $this->data = $data;
        $this->business = $business;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function view(): View
    {
        return view('report.partials.plastic_bag_export_excel', [
            'data' => $this->data,
            'business' => $this->business,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_bags' => $this->data->sum('quantity'),
            'total_revenue' => $this->data->sum('line_total')
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            6 => ['font' => ['bold' => true]],
            
            // Style the header row
            8 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E2E2E2']]],
        ];
    }
}