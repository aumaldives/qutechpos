<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;

class OtherTransactionsExport implements WithHeadings, WithTitle, FromCollection
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'OtherTransactions';
    }

    public function headings(): array
    {
        return [
            'Your Taxable Activity No.',
            'Value of Supplies Subject to GST at 8% or 16% (excluding GST)',
            'Value of Zero-Rated Supplies',
            'Value of Exempt Supplies',
            'Value of Out-of-Scope Supplies',
        ];
    }

    public function collection()
    {
        return collect($this->data);
    }
}
