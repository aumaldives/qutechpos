<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;

class InputExport implements WithHeadings, WithTitle, FromCollection
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'TaxInvoices';
    }
    
    public function headings(): array
    {
        return [
            '#',
            'Supplier TIN',
            'Supplier Name',
            'Supplier Invoice Number',
            'Invoice Date',
            'Invoice Total (excluding GST)',
            'GST Charged at 6%',
            'GST Charged at 8%',
            'GST Charged at 12%',
            'GST Charged at 16%',
            'Your Taxable Activity Number',
            'Revenue / Capital'
        ];
    }

    public function collection()
    {
        return collect($this->data);
    }
}
