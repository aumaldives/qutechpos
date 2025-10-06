<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PublicLedgerAdminController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display Format 4 admin view (Consolidated invoices with date range)
     */
    public function format4(Request $request)
    {
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get business locations for filter
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations->prepend(__('lang_v1.all_locations'), '');

        // Get contacts for filter
        $contacts = Contact::contactDropdown($business_id, false, false);

        if ($request->ajax()) {
            return $this->getFormat4DataTable($request);
        }

        return view('admin.public_ledger.format_4', compact('business_locations', 'contacts'));
    }

    /**
     * Display Format 5 admin view (Due invoices only)
     */
    public function format5(Request $request)
    {
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get business locations for filter
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations->prepend(__('lang_v1.all_locations'), '');

        // Get contacts for filter
        $contacts = Contact::contactDropdown($business_id, false, false);

        if ($request->ajax()) {
            return $this->getFormat5DataTable($request);
        }

        return view('admin.public_ledger.format_5', compact('business_locations', 'contacts'));
    }

    /**
     * Get Format 4 data for DataTable
     */
    private function getFormat4DataTable($request)
    {
        $business_id = request()->session()->get('user.business_id');
        $contact_id = $request->get('contact_id');
        $location_id = $request->get('location_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $query = DB::table('public_ledger_links as pll')
            ->join('contacts as c', 'pll.contact_id', '=', 'c.id')
            ->join('business as b', 'pll.business_id', '=', 'b.id')
            ->where('pll.business_id', $business_id)
            ->where('pll.format', 'format_4')
            ->select([
                'pll.id',
                'c.name as contact_name',
                'c.id as contact_id',
                'pll.token',
                'pll.is_active',
                'pll.created_at',
                'pll.updated_at'
            ]);

        if ($contact_id) {
            $query->where('c.id', $contact_id);
        }

        return DataTables::of($query)
            ->addColumn('public_url', function ($row) {
                return route('public.ledger.format4', [
                    'business_id' => session('user.business_id'),
                    'contact_id' => $row->contact_id,
                    'token' => $row->token
                ]);
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="btn-group">';
                
                // View Public Link
                $public_url = route('public.ledger.format4', [
                    'business_id' => session('user.business_id'),
                    'contact_id' => $row->contact_id,
                    'token' => $row->token
                ]);
                $actions .= '<a href="' . $public_url . '" target="_blank" class="btn btn-xs btn-info" title="View Public Link">
                    <i class="glyphicon glyphicon-eye-open"></i>
                </a>';

                // Copy Link
                $actions .= '<button class="btn btn-xs btn-warning copy-link" data-url="' . $public_url . '" title="Copy Link">
                    <i class="fa fa-copy"></i>
                </button>';

                // Toggle Active Status
                $status_class = $row->is_active ? 'btn-success' : 'btn-danger';
                $status_title = $row->is_active ? 'Deactivate' : 'Activate';
                $actions .= '<button class="btn btn-xs ' . $status_class . ' toggle-status" 
                    data-id="' . $row->id . '" 
                    data-status="' . ($row->is_active ? 0 : 1) . '" 
                    title="' . $status_title . '">
                    <i class="fa fa-power-off"></i>
                </button>';

                // Delete Link
                $actions .= '<button class="btn btn-xs btn-danger delete-link" data-id="' . $row->id . '" title="Delete Link">
                    <i class="glyphicon glyphicon-trash"></i>
                </button>';

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('status', function ($row) {
                return $row->is_active 
                    ? '<span class="label label-success">Active</span>' 
                    : '<span class="label label-danger">Inactive</span>';
            })
            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    /**
     * Get Format 5 data for DataTable
     */
    private function getFormat5DataTable($request)
    {
        $business_id = request()->session()->get('user.business_id');
        $contact_id = $request->get('contact_id');

        $query = DB::table('public_ledger_links as pll')
            ->join('contacts as c', 'pll.contact_id', '=', 'c.id')
            ->join('business as b', 'pll.business_id', '=', 'b.id')
            ->where('pll.business_id', $business_id)
            ->where('pll.format', 'format_5')
            ->select([
                'pll.id',
                'c.name as contact_name',
                'c.id as contact_id',
                'pll.token',
                'pll.is_active',
                'pll.created_at',
                'pll.updated_at'
            ]);

        if ($contact_id) {
            $query->where('c.id', $contact_id);
        }

        return DataTables::of($query)
            ->addColumn('public_url', function ($row) {
                return route('public.ledger.format5', [
                    'business_id' => session('user.business_id'),
                    'contact_id' => $row->contact_id,
                    'token' => $row->token
                ]);
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="btn-group">';
                
                // View Public Link
                $public_url = route('public.ledger.format5', [
                    'business_id' => session('user.business_id'),
                    'contact_id' => $row->contact_id,
                    'token' => $row->token
                ]);
                $actions .= '<a href="' . $public_url . '" target="_blank" class="btn btn-xs btn-info" title="View Public Link">
                    <i class="glyphicon glyphicon-eye-open"></i>
                </a>';

                // Copy Link
                $actions .= '<button class="btn btn-xs btn-warning copy-link" data-url="' . $public_url . '" title="Copy Link">
                    <i class="fa fa-copy"></i>
                </button>';

                // Toggle Active Status
                $status_class = $row->is_active ? 'btn-success' : 'btn-danger';
                $status_title = $row->is_active ? 'Deactivate' : 'Activate';
                $actions .= '<button class="btn btn-xs ' . $status_class . ' toggle-status" 
                    data-id="' . $row->id . '" 
                    data-status="' . ($row->is_active ? 0 : 1) . '" 
                    title="' . $status_title . '">
                    <i class="fa fa-power-off"></i>
                </button>';

                // Delete Link
                $actions .= '<button class="btn btn-xs btn-danger delete-link" data-id="' . $row->id . '" title="Delete Link">
                    <i class="glyphicon glyphicon-trash"></i>
                </button>';

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('status', function ($row) {
                return $row->is_active 
                    ? '<span class="label label-success">Active</span>' 
                    : '<span class="label label-danger">Inactive</span>';
            })
            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i');
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    /**
     * Toggle link status
     */
    public function toggleStatus(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $id = $request->input('id');
            $status = $request->input('status');

            DB::table('public_ledger_links')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->update(['is_active' => $status]);

            return response()->json([
                'success' => true,
                'msg' => 'Link status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error updating link status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete public link
     */
    public function deleteLink(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $id = $request->input('id');

            DB::table('public_ledger_links')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->delete();

            return response()->json([
                'success' => true,
                'msg' => 'Link deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error deleting link: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get consolidated invoices preview for Format 4
     */
    public function getFormat4Preview(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $contact_id = $request->input('contact_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $location_id = $request->input('location_id');

        if (!$contact_id) {
            return response()->json(['error' => 'Contact ID is required']);
        }

        $contact = Contact::where('business_id', $business_id)->find($contact_id);
        if (!$contact) {
            return response()->json(['error' => 'Contact not found']);
        }

        // Use the existing getLedgerDetails method with format_4
        $ledger_details = $this->transactionUtil->getLedgerDetails($contact_id, $start_date, $end_date, 'format_4', $location_id);

        // Filter for consolidated invoices (all invoices)
        $invoices = collect($ledger_details['ledger'] ?? [])->where('type', 'sell');
        
        $preview_data = [
            'contact_name' => $contact->name,
            'date_range' => $start_date && $end_date ? "from {$start_date} to {$end_date}" : 'All dates',
            'total_invoices' => $invoices->count(),
            'grand_total' => $invoices->sum('final_total'),
            'total_tax' => $invoices->sum('tax'),
        ];

        return response()->json($preview_data);
    }

    /**
     * Get due invoices preview for Format 5
     */
    public function getFormat5Preview(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $contact_id = $request->input('contact_id');
        $location_id = $request->input('location_id');

        if (!$contact_id) {
            return response()->json(['error' => 'Contact ID is required']);
        }

        $contact = Contact::where('business_id', $business_id)->find($contact_id);
        if (!$contact) {
            return response()->json(['error' => 'Contact not found']);
        }

        // Use the existing getLedgerDetails method with format_5
        $ledger_details = $this->transactionUtil->getLedgerDetails($contact_id, null, null, 'format_5', $location_id);

        // Filter for due invoices only
        $due_invoices = collect($ledger_details['ledger'] ?? [])->where('type', 'sell')
            ->whereIn('payment_status', ['due', 'partial']);
        
        $total_due = $due_invoices->sum(function($invoice) {
            return $invoice['final_total'] - $invoice['total_paid'];
        });
        
        $preview_data = [
            'contact_name' => $contact->name,
            'total_due_invoices' => $due_invoices->count(),
            'total_amount_due' => $total_due,
            'total_invoice_amount' => $due_invoices->sum('final_total'),
            'total_paid_amount' => $due_invoices->sum('total_paid'),
        ];

        return response()->json($preview_data);
    }
}