<?php

use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class ChangeInvoiceTemplateIdDefaultValue
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $invoiceTemplate = DB::table('invoice-settings')->first();;

        /** @var InvoiceSetting $invoices */
        $invoices = Invoice::whereTemplateId(null)->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'template_id' => $invoiceTemplate->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
