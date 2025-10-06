<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;

class PublicLedgerController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display public ledger for Format 4 (Consolidated invoices with date range)
     */
    public function format4($business_id, $contact_id, $token)
    {
        // Validate token and get public ledger link
        $publicLink = $this->validatePublicLedgerToken($business_id, $contact_id, $token, 'format_4');
        if (!$publicLink) {
            abort(404);
        }

        $business = Business::findOrFail($business_id);
        $contact = Contact::where('business_id', $business_id)->findOrFail($contact_id);

        // Get date range from request or use defaults
        $start_date = request('start_date');
        $end_date = request('end_date');
        $location_id = request('location_id');

        // Get business locations for filter
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations->prepend(__('lang_v1.all_locations'), '');

        // Get ledger data
        $ledger_details = $this->getConsolidatedInvoices($business_id, $contact_id, $start_date, $end_date, $location_id);

        return view('public.ledger_format_4', compact(
            'business',
            'contact',
            'ledger_details',
            'start_date',
            'end_date',
            'location_id',
            'business_locations'
        ));
    }

    /**
     * Display public ledger for Format 5 (Due invoices only)
     */
    public function format5($business_id, $contact_id, $token)
    {
        // Validate token and get public ledger link
        $publicLink = $this->validatePublicLedgerToken($business_id, $contact_id, $token, 'format_5');
        if (!$publicLink) {
            abort(404);
        }

        $business = Business::findOrFail($business_id);
        $contact = Contact::where('business_id', $business_id)->findOrFail($contact_id);

        // Get location filter
        $location_id = request('location_id');

        // Get business locations for filter
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations->prepend(__('lang_v1.all_locations'), '');

        // Get due invoices data (no date filter)
        $ledger_details = $this->getDueInvoicesOnly($business_id, $contact_id, $location_id);

        return view('public.ledger_format_5', compact(
            'business',
            'contact',
            'ledger_details',
            'location_id',
            'business_locations'
        ));
    }

    /**
     * Get consolidated invoices data for Format 4
     */
    private function getConsolidatedInvoices($business_id, $contact_id, $start_date = null, $end_date = null, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $contact_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        if ($start_date && $end_date) {
            $query->whereBetween('t.transaction_date', [$start_date, $end_date]);
        }

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        $invoices = $query->select(
            't.id',
            't.transaction_date as date',
            't.invoice_no',
            't.final_total',
            't.tax_amount',
            't.total_before_tax as subtotal',
            DB::raw('COALESCE(SUM(tp.amount), 0) as paid_amount'),
            DB::raw('(t.final_total - COALESCE(SUM(tp.amount), 0)) as due_amount'),
            'bl.name as location_name'
        )
        ->groupBy('t.id', 't.transaction_date', 't.invoice_no', 't.final_total', 't.tax_amount', 't.total_before_tax', 'bl.name')
        ->orderBy('t.transaction_date', 'desc')
        ->get();

        // Calculate plastic bag fees
        $plastic_bag_total = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $contact_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', 'like', '%plastic bag%')
            ->when($start_date && $end_date, function($q) use ($start_date, $end_date) {
                return $q->whereBetween('t.transaction_date', [$start_date, $end_date]);
            })
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->sum(DB::raw('tsl.quantity * tsl.unit_price_inc_tax'));

        // Calculate totals
        $grand_subtotal = $invoices->sum('subtotal');
        $total_tax = $invoices->sum('tax_amount');
        $final_total = $invoices->sum('final_total');

        return [
            'invoices' => $invoices,
            'grand_subtotal' => $grand_subtotal,
            'total_tax' => $total_tax,
            'plastic_bag_total' => $plastic_bag_total,
            'final_total' => $final_total,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    /**
     * Get due invoices data for Format 5
     */
    private function getDueInvoicesOnly($business_id, $contact_id, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $contact_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final');

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        $invoices = $query->select(
            't.id',
            't.transaction_date as date',
            't.invoice_no',
            't.final_total',
            't.tax_amount',
            't.total_before_tax as subtotal',
            DB::raw('COALESCE(SUM(tp.amount), 0) as paid_amount'),
            DB::raw('(t.final_total - COALESCE(SUM(tp.amount), 0)) as due_amount'),
            'bl.name as location_name'
        )
        ->groupBy('t.id', 't.transaction_date', 't.invoice_no', 't.final_total', 't.tax_amount', 't.total_before_tax', 'bl.name')
        ->havingRaw('(t.final_total - COALESCE(SUM(tp.amount), 0)) > 0')
        ->orderBy('t.transaction_date', 'desc')
        ->get();

        // Calculate plastic bag fees for due invoices only
        $due_invoice_ids = $invoices->pluck('id');
        $plastic_bag_total = 0;
        
        if ($due_invoice_ids->count() > 0) {
            $plastic_bag_total = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.contact_id', $contact_id)
                ->whereIn('t.id', $due_invoice_ids)
                ->where('p.name', 'like', '%plastic bag%')
                ->sum(DB::raw('tsl.quantity * tsl.unit_price_inc_tax'));
        }

        // Calculate totals for due invoices only
        $grand_subtotal = $invoices->sum('subtotal');
        $total_tax = $invoices->sum('tax_amount');
        $final_total = $invoices->sum('final_total');

        return [
            'invoices' => $invoices,
            'grand_subtotal' => $grand_subtotal,
            'total_tax' => $total_tax,
            'plastic_bag_total' => $plastic_bag_total,
            'final_total' => $final_total
        ];
    }

    /**
     * Validate public ledger token
     */
    private function validatePublicLedgerToken($business_id, $contact_id, $token, $format)
    {
        // Check if public ledger link exists in database
        $publicLink = DB::table('public_ledger_links')
            ->where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('format', $format)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        return $publicLink;
    }

    /**
     * Generate or get existing public ledger link
     */
    public function generatePublicLink($business_id, $contact_id, $format)
    {
        // Check if link already exists
        $existingLink = DB::table('public_ledger_links')
            ->where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('format', $format)
            ->first();

        if ($existingLink) {
            return $existingLink->token;
        }

        // Generate new token
        $token = bin2hex(random_bytes(32));

        // Insert new public ledger link
        DB::table('public_ledger_links')->insert([
            'business_id' => $business_id,
            'contact_id' => $contact_id,
            'format' => $format,
            'token' => $token,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $token;
    }
}