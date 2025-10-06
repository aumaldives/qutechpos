<?php

namespace App\Http\Controllers;

use App\PlasticBagType;
use App\PlasticBagPurchase;
use App\PlasticBagPurchaseLine;
use App\PlasticBagStockAdjustment;
use App\PlasticBagStockTransfer;
use App\PlasticBagUsage;
use App\Contact;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PlasticBagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display plastic bag types management
     */
    public function types(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $types = PlasticBagType::forBusiness($business_id)
                ->select(['id', 'name', 'description', 'price', 'stock_quantity', 'alert_quantity', 'is_active']);

            return DataTables::of($types)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">
                            <li><a href="#" class="edit-type" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'editType'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                            <li><a href="#" class="delete-type" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'deleteType'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                        </ul>
                    </div>';
                    return $html;
                })
                ->editColumn('price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->price . '</span>';
                })
                ->editColumn('stock_quantity', function ($row) {
                    $alert_class = '';
                    if ($row->alert_quantity && $row->stock_quantity <= $row->alert_quantity) {
                        $alert_class = 'text-danger';
                    }
                    return '<span class="' . $alert_class . '">' . number_format($row->stock_quantity, 0) . '</span>';
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? __('messages.yes') : __('messages.no');
                })
                ->rawColumns(['action', 'price', 'stock_quantity'])
                ->make(true);
        }

        return view('plastic_bag.types.index');
    }

    /**
     * Show form to create plastic bag type
     */
    public function createType()
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('plastic_bag.types.create');
    }

    /**
     * Store plastic bag type
     */
    public function storeType(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|max:255',
                'price' => 'required|numeric|min:0',
                'alert_quantity' => 'nullable|numeric|min:0'
            ]);

            PlasticBagType::create([
                'business_id' => $business_id,
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'alert_quantity' => $request->alert_quantity,
                'is_active' => $request->has('is_active') ? 1 : 0
            ]);

            $output = ['success' => 1, 'msg' => __('plastic_bag.type_created_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Show form to edit plastic bag type
     */
    public function editType($id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $type = PlasticBagType::forBusiness($business_id)->findOrFail($id);

        return view('plastic_bag.types.edit', compact('type'));
    }

    /**
     * Update plastic bag type
     */
    public function updateType(Request $request, $id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|max:255',
                'price' => 'required|numeric|min:0',
                'alert_quantity' => 'nullable|numeric|min:0'
            ]);

            $type = PlasticBagType::forBusiness($business_id)->findOrFail($id);
            $type->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'alert_quantity' => $request->alert_quantity,
                'is_active' => $request->has('is_active') ? 1 : 0
            ]);

            $output = ['success' => 1, 'msg' => __('plastic_bag.type_updated_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Delete plastic bag type
     */
    public function deleteType($id)
    {
        if (!auth()->user()->can('plastic_bag.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $type = PlasticBagType::forBusiness($business_id)->findOrFail($id);
            $type->delete();

            $output = ['success' => 1, 'msg' => __('plastic_bag.type_deleted_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Display plastic bag purchases
     */
    public function purchases(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $purchases = PlasticBagPurchase::forBusiness($business_id)
                ->with(['supplier', 'createdBy'])
                ->select(['id', 'invoice_number', 'purchase_date', 'supplier_id', 'total_amount', 'invoice_file', 'created_by']);

            return DataTables::of($purchases)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">
                            <li><a href="#" class="view-purchase" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'showPurchase'], [$row->id]) . '"><i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '</a></li>
                            <li><a href="#" class="edit-purchase" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'editPurchase'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                            <li><a href="#" class="delete-purchase" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'deletePurchase'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                        </ul>
                    </div>';
                    return $html;
                })
                ->editColumn('total_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_amount . '</span>';
                })
                ->editColumn('supplier_id', function ($row) {
                    return $row->supplier ? $row->supplier->name : '-';
                })
                ->editColumn('purchase_date', function ($row) {
                    return $row->purchase_date->format('d/m/Y');
                })
                ->editColumn('invoice_file', function ($row) {
                    if ($row->invoice_file) {
                        return '<a href="' . asset('storage/' . $row->invoice_file) . '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-download"></i> Download</a>';
                    }
                    return '-';
                })
                ->rawColumns(['action', 'total_amount', 'invoice_file'])
                ->make(true);
        }

        return view('plastic_bag.purchases.index');
    }

    /**
     * Show form to create plastic bag purchase
     */
    public function createPurchase()
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $plastic_bag_types = PlasticBagType::forBusiness($business_id)->active()->get();
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('plastic_bag.purchases.create', compact('plastic_bag_types', 'suppliers'));
    }

    /**
     * Store plastic bag purchase
     */
    public function storePurchase(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'invoice_number' => 'required|max:255',
                'purchase_date' => 'required|string',
                'plastic_bag_types' => 'required|array|min:1',
                'plastic_bag_types.*.type_id' => 'required|exists:plastic_bag_types,id',
                'plastic_bag_types.*.quantity' => 'required|numeric|min:1',
                'plastic_bag_types.*.price_per_bag' => 'required|numeric|min:0',
                'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            // Convert date format from d/m/Y to Y-m-d
            $purchase_date = Carbon::createFromFormat('d/m/Y', $request->purchase_date)->format('Y-m-d');

            DB::beginTransaction();

            // Handle file upload
            $invoice_file = null;
            if ($request->hasFile('invoice_file')) {
                $invoice_file = $request->file('invoice_file')->store('plastic_bag_invoices', 'public');
            }

            // Calculate total
            $total_amount = 0;
            foreach ($request->plastic_bag_types as $line) {
                $total_amount += $line['quantity'] * $line['price_per_bag'];
            }

            // Create purchase
            $purchase = PlasticBagPurchase::create([
                'business_id' => $business_id,
                'invoice_number' => $request->invoice_number,
                'purchase_date' => $purchase_date,
                'supplier_id' => $request->supplier_id,
                'total_amount' => $total_amount,
                'invoice_file' => $invoice_file,
                'notes' => $request->notes,
                'created_by' => auth()->user()->id
            ]);

            // Create purchase lines and update stock
            foreach ($request->plastic_bag_types as $line) {
                $line_total = $line['quantity'] * $line['price_per_bag'];
                
                PlasticBagPurchaseLine::create([
                    'plastic_bag_purchase_id' => $purchase->id,
                    'plastic_bag_type_id' => $line['type_id'],
                    'quantity' => $line['quantity'],
                    'price_per_bag' => $line['price_per_bag'],
                    'line_total' => $line_total
                ]);

                // Update plastic bag type stock
                $plastic_bag_type = PlasticBagType::find($line['type_id']);
                $plastic_bag_type->increment('stock_quantity', $line['quantity']);
            }

            DB::commit();
            $output = ['success' => 1, 'msg' => __('plastic_bag.purchase_created_successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Show purchase details
     */
    public function showPurchase($id)
    {
        if (!auth()->user()->can('plastic_bag.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $purchase = PlasticBagPurchase::forBusiness($business_id)->with(['supplier', 'purchaseLines.plasticBagType', 'createdBy'])->findOrFail($id);

        return view('plastic_bag.purchases.show', compact('purchase'));
    }

    /**
     * Show form to edit purchase
     */
    public function editPurchase($id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $purchase = PlasticBagPurchase::forBusiness($business_id)->with('purchaseLines')->findOrFail($id);
        $plastic_bag_types = PlasticBagType::forBusiness($business_id)->active()->get();
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('plastic_bag.purchases.edit', compact('purchase', 'plastic_bag_types', 'suppliers'));
    }

    /**
     * Update purchase
     */
    public function updatePurchase(Request $request, $id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $purchase = PlasticBagPurchase::forBusiness($business_id)->findOrFail($id);

            $request->validate([
                'invoice_number' => 'required|max:255',
                'purchase_date' => 'required|string',
                'plastic_bag_types' => 'required|array|min:1',
                'plastic_bag_types.*.type_id' => 'required|exists:plastic_bag_types,id',
                'plastic_bag_types.*.quantity' => 'required|numeric|min:1',
                'plastic_bag_types.*.price_per_bag' => 'required|numeric|min:0',
                'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            // Convert date format from d/m/Y to Y-m-d
            $purchase_date = Carbon::createFromFormat('d/m/Y', $request->purchase_date)->format('Y-m-d');

            DB::beginTransaction();

            // Handle file upload
            $invoice_file = $purchase->invoice_file;
            if ($request->hasFile('invoice_file')) {
                $invoice_file = $request->file('invoice_file')->store('plastic_bag_invoices', 'public');
            }

            // Calculate total
            $total_amount = 0;
            foreach ($request->plastic_bag_types as $line) {
                $total_amount += $line['quantity'] * $line['price_per_bag'];
            }

            // Reverse old stock changes
            foreach ($purchase->purchaseLines as $old_line) {
                $plastic_bag_type = PlasticBagType::find($old_line->plastic_bag_type_id);
                $plastic_bag_type->decrement('stock_quantity', $old_line->quantity);
            }

            // Delete old lines
            $purchase->purchaseLines()->delete();

            // Update purchase
            $purchase->update([
                'invoice_number' => $request->invoice_number,
                'purchase_date' => $purchase_date,
                'supplier_id' => $request->supplier_id,
                'total_amount' => $total_amount,
                'invoice_file' => $invoice_file,
                'notes' => $request->notes
            ]);

            // Create new purchase lines and update stock
            foreach ($request->plastic_bag_types as $line) {
                $line_total = $line['quantity'] * $line['price_per_bag'];
                
                PlasticBagPurchaseLine::create([
                    'plastic_bag_purchase_id' => $purchase->id,
                    'plastic_bag_type_id' => $line['type_id'],
                    'quantity' => $line['quantity'],
                    'price_per_bag' => $line['price_per_bag'],
                    'line_total' => $line_total
                ]);

                // Update plastic bag type stock
                $plastic_bag_type = PlasticBagType::find($line['type_id']);
                $plastic_bag_type->increment('stock_quantity', $line['quantity']);
            }

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Purchase updated successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Delete purchase
     */
    public function deletePurchase($id)
    {
        if (!auth()->user()->can('plastic_bag.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $purchase = PlasticBagPurchase::forBusiness($business_id)->with('purchaseLines')->findOrFail($id);

            DB::beginTransaction();

            // Reverse stock changes
            foreach ($purchase->purchaseLines as $line) {
                $plastic_bag_type = PlasticBagType::find($line->plastic_bag_type_id);
                $plastic_bag_type->decrement('stock_quantity', $line->quantity);
            }

            // Delete purchase lines
            $purchase->purchaseLines()->delete();

            // Delete purchase
            $purchase->delete();

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Purchase deleted successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Display stock adjustments
     */
    public function stockAdjustments(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $adjustments = PlasticBagStockAdjustment::forBusiness($business_id)
                ->with(['plasticBagType', 'location', 'createdBy'])
                ->select(['id', 'plastic_bag_type_id', 'location_id', 'adjustment_type', 'quantity', 'reason', 'adjustment_date', 'created_by']);

            return DataTables::of($adjustments)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">
                            <li><a href="#" class="delete-adjustment" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'deleteAdjustment'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                        </ul>
                    </div>';
                    return $html;
                })
                ->editColumn('plastic_bag_type_id', function ($row) {
                    return $row->plasticBagType->name;
                })
                ->editColumn('location_id', function ($row) {
                    return $row->location ? $row->location->name : 'All Locations';
                })
                ->editColumn('adjustment_type', function ($row) {
                    $class = $row->adjustment_type == 'increase' ? 'text-success' : 'text-danger';
                    $icon = $row->adjustment_type == 'increase' ? 'fa-plus' : 'fa-minus';
                    return '<span class="' . $class . '"><i class="fa ' . $icon . '"></i> ' . ucfirst($row->adjustment_type) . '</span>';
                })
                ->editColumn('quantity', function ($row) {
                    return number_format($row->quantity, 0);
                })
                ->editColumn('adjustment_date', function ($row) {
                    return $row->adjustment_date->format('d/m/Y');
                })
                ->rawColumns(['action', 'adjustment_type'])
                ->make(true);
        }

        return view('plastic_bag.adjustments.index');
    }

    /**
     * Show form to create stock adjustment
     */
    public function createAdjustment()
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $plastic_bag_types = PlasticBagType::forBusiness($business_id)->active()->get();
        $locations = BusinessLocation::where('business_id', $business_id)->get();

        return view('plastic_bag.adjustments.create', compact('plastic_bag_types', 'locations'));
    }

    /**
     * Store stock adjustment
     */
    public function storeAdjustment(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'plastic_bag_type_id' => 'required|exists:plastic_bag_types,id',
                'adjustment_type' => 'required|in:increase,decrease',
                'quantity' => 'required|numeric|min:1',
                'reason' => 'required|string|max:255',
                'adjustment_date' => 'required|date'
            ]);

            DB::beginTransaction();

            // Create adjustment record
            PlasticBagStockAdjustment::create([
                'business_id' => $business_id,
                'plastic_bag_type_id' => $request->plastic_bag_type_id,
                'location_id' => $request->location_id,
                'adjustment_type' => $request->adjustment_type,
                'quantity' => $request->quantity,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'adjustment_date' => $request->adjustment_date,
                'created_by' => auth()->user()->id
            ]);

            // Update stock
            $plastic_bag_type = PlasticBagType::find($request->plastic_bag_type_id);
            if ($request->adjustment_type == 'increase') {
                $plastic_bag_type->increment('stock_quantity', $request->quantity);
            } else {
                $plastic_bag_type->decrement('stock_quantity', $request->quantity);
            }

            DB::commit();
            $output = ['success' => 1, 'msg' => __('plastic_bag.adjustment_created_successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Delete stock adjustment
     */
    public function deleteAdjustment($id)
    {
        if (!auth()->user()->can('plastic_bag.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $adjustment = PlasticBagStockAdjustment::forBusiness($business_id)->findOrFail($id);
            
            // Reverse the stock adjustment
            $plastic_bag_type = PlasticBagType::find($adjustment->plastic_bag_type_id);
            if ($adjustment->adjustment_type == 'increase') {
                $plastic_bag_type->decrement('stock_quantity', $adjustment->quantity);
            } else {
                $plastic_bag_type->increment('stock_quantity', $adjustment->quantity);
            }
            
            $adjustment->delete();

            $output = ['success' => 1, 'msg' => __('plastic_bag.adjustment_deleted_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Display stock transfers
     */
    public function stockTransfers(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            try {
                $transfers = PlasticBagStockTransfer::forBusiness($business_id)
                    ->with(['plasticBagType', 'fromLocation', 'toLocation', 'createdBy'])
                    ->select(['id', 'transfer_number', 'plastic_bag_type_id', 'from_location_id', 'to_location_id', 'quantity', 'transfer_date', 'status', 'created_by']);

                return DataTables::of($transfers)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    
                    if ($row->status == 'pending') {
                        $html .= '<li><a href="#" class="receive-transfer" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'receiveTransfer'], [$row->id]) . '"><i class="glyphicon glyphicon-ok"></i> ' . __("messages.receive") . '</a></li>';
                        $html .= '<li><a href="#" class="cancel-transfer" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'cancelTransfer'], [$row->id]) . '"><i class="glyphicon glyphicon-remove"></i> Cancel</a></li>';
                    }
                    
                    $html .= '<li><a href="#" class="delete-transfer" data-href="' . action([\App\Http\Controllers\PlasticBagController::class, 'deleteTransfer'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                        </ul>
                    </div>';
                    return $html;
                })
                ->editColumn('plastic_bag_type_id', function ($row) {
                    return $row->plasticBagType ? $row->plasticBagType->name : 'N/A';
                })
                ->editColumn('from_location_id', function ($row) {
                    return $row->fromLocation ? $row->fromLocation->name : 'N/A';
                })
                ->editColumn('to_location_id', function ($row) {
                    return $row->toLocation ? $row->toLocation->name : 'N/A';
                })
                ->editColumn('quantity', function ($row) {
                    return number_format($row->quantity, 0);
                })
                ->editColumn('status', function ($row) {
                    $class = '';
                    switch($row->status) {
                        case 'pending':
                            $class = 'label-warning';
                            break;
                        case 'in_transit':
                            $class = 'label-info';
                            break;
                        case 'completed':
                            $class = 'label-success';
                            break;
                        case 'cancelled':
                            $class = 'label-danger';
                            break;
                    }
                    return '<span class="label ' . $class . '">' . ucfirst($row->status) . '</span>';
                })
                ->editColumn('transfer_date', function ($row) {
                    return $row->transfer_date ? $row->transfer_date->format('d/m/Y') : 'N/A';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
            } catch (\Exception $e) {
                \Log::error('Error loading plastic bag stock transfers: ' . $e->getMessage());
                return response()->json(['error' => 'Error loading stock transfers: ' . $e->getMessage()], 500);
            }
        }

        return view('plastic_bag.transfers.index');
    }

    /**
     * Show form to create stock transfer
     */
    public function createTransfer()
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $plastic_bag_types = PlasticBagType::forBusiness($business_id)->active()->get();
        $locations = BusinessLocation::where('business_id', $business_id)->get();

        return view('plastic_bag.transfers.create', compact('plastic_bag_types', 'locations'));
    }

    /**
     * Store stock transfer
     */
    public function storeTransfer(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'plastic_bag_type_id' => 'required|exists:plastic_bag_types,id',
                'from_location_id' => 'required|exists:business_locations,id',
                'to_location_id' => 'required|exists:business_locations,id|different:from_location_id',
                'quantity' => 'required|numeric|min:1',
                'transfer_date' => 'required|string'
            ]);

            // Convert date format from d/m/Y to Y-m-d
            $transfer_date = Carbon::createFromFormat('d/m/Y', $request->transfer_date)->format('Y-m-d');

            // Generate transfer number
            $transfer_number = 'PBT-' . date('Ymd') . '-' . str_pad(PlasticBagStockTransfer::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            DB::beginTransaction();

            // Check if enough stock available
            $plastic_bag_type = PlasticBagType::find($request->plastic_bag_type_id);
            if ($plastic_bag_type->stock_quantity < $request->quantity) {
                throw new \Exception('Insufficient stock quantity. Available: ' . $plastic_bag_type->stock_quantity);
            }

            // Create transfer
            PlasticBagStockTransfer::create([
                'business_id' => $business_id,
                'transfer_number' => $transfer_number,
                'plastic_bag_type_id' => $request->plastic_bag_type_id,
                'from_location_id' => $request->from_location_id,
                'to_location_id' => $request->to_location_id,
                'quantity' => $request->quantity,
                'transfer_date' => $transfer_date,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_by' => auth()->user()->id
            ]);

            // Deduct from stock
            $plastic_bag_type->decrement('stock_quantity', $request->quantity);

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Transfer created successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * Receive transfer
     */
    public function receiveTransfer($id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer = PlasticBagStockTransfer::forBusiness($business_id)->findOrFail($id);
            
            if ($transfer->status !== 'pending') {
                throw new \Exception('Transfer cannot be received. Current status: ' . $transfer->status);
            }

            DB::beginTransaction();

            // Update transfer status
            $transfer->update([
                'status' => 'completed',
                'received_by' => auth()->user()->id,
                'received_at' => now()
            ]);

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Transfer received successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * Cancel transfer
     */
    public function cancelTransfer($id)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer = PlasticBagStockTransfer::forBusiness($business_id)->findOrFail($id);
            
            if ($transfer->status !== 'pending') {
                throw new \Exception('Transfer cannot be cancelled. Current status: ' . $transfer->status);
            }

            DB::beginTransaction();

            // Update transfer status
            $transfer->update(['status' => 'cancelled']);

            // Add back to stock
            $plastic_bag_type = PlasticBagType::find($transfer->plastic_bag_type_id);
            $plastic_bag_type->increment('stock_quantity', $transfer->quantity);

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Transfer cancelled successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * Delete transfer
     */
    public function deleteTransfer($id)
    {
        if (!auth()->user()->can('plastic_bag.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer = PlasticBagStockTransfer::forBusiness($business_id)->findOrFail($id);
            
            // If transfer is pending, add back to stock
            if ($transfer->status == 'pending') {
                $plastic_bag_type = PlasticBagType::find($transfer->plastic_bag_type_id);
                $plastic_bag_type->increment('stock_quantity', $transfer->quantity);
            }
            
            $transfer->delete();

            $output = ['success' => 1, 'msg' => __('Transfer deleted successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Get plastic bag stock for AJAX
     */
    public function getPlasticBagStock($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $plastic_bag_type = PlasticBagType::forBusiness($business_id)->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'stock_quantity' => number_format($plastic_bag_type->stock_quantity, 0)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error fetching stock information'
            ]);
        }
    }

    /**
     * Show form to adjust plastic bag selling prices
     */
    public function adjustPrices()
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $plastic_bag_types = PlasticBagType::forBusiness($business_id)->active()->get();

        return view('plastic_bag.adjust_prices', compact('plastic_bag_types'));
    }

    /**
     * Update plastic bag selling prices
     */
    public function updatePrices(Request $request)
    {
        if (!auth()->user()->can('plastic_bag.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'prices' => 'required|array',
                'prices.*' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            foreach ($request->prices as $type_id => $price) {
                $plastic_bag_type = PlasticBagType::forBusiness($business_id)->findOrFail($type_id);
                $plastic_bag_type->update(['price' => $price]);
            }

            DB::commit();
            $output = ['success' => 1, 'msg' => __('Prices updated successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Get plastic bag types for POS
     */
    public function getPlasticBagTypesForPos()
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'msg' => 'User not authenticated'
                ]);
            }
            
            $business_id = request()->session()->get('user.business_id');
            
            if (!$business_id) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Business ID not found in session'
                ]);
            }
            
            // Check if user has permission
            if (!auth()->user()->can('plastic_bag.access')) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Permission denied'
                ]);
            }
            
            $plastic_bag_types = PlasticBagType::forBusiness($business_id)
                ->active()
                ->where('stock_quantity', '>', 0)
                ->select(['id', 'name', 'price', 'stock_quantity'])
                ->get();
            
            return response()->json([
                'success' => true,
                'plastic_bag_types' => $plastic_bag_types
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading plastic bag types for POS:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => 'Error fetching plastic bag types: ' . $e->getMessage()
            ]);
        }
    }
}
