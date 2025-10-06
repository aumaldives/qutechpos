<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;

class TaxInvoicesExport implements WithHeadings, WithTitle, FromCollection
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
            'Customer TIN',
            'Customer Name',
            'Invoice No.',
            'Invoice Date',
            'Value of Supplies Subject to GST at 8% or 16% (excluding GST)',
            'Value of Zero-Rated Supplies',
            'Value of Exempt Supplies',
            'Value of Out-of-Scope Supplies',
            'Your Taxable Activity No.'
        ];
    }

    public function collection()
    {
        return collect($this->data);
    }
}
