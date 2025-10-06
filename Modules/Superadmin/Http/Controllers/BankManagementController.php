<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use DB;
use Storage;

class BankManagementController extends BaseController
{
    /**
     * Display a listing of banks
     */
    public function index()
    {
        if (request()->ajax()) {
            $banks = DB::table('system_banks')
                ->orderBy('name', 'asc');

            return DataTables::of($banks)
                ->addColumn('logo_display', function ($row) {
                    if ($row->logo_url) {
                        return '<img src="' . $row->logo_url . '" alt="' . $row->name . '" style="width: 40px; height: 40px; object-fit: contain;">';
                    }
                    return '<span class="text-muted">No logo</span>';
                })
                ->addColumn('status_formatted', function ($row) {
                    $status = $row->is_active ? 'Active' : 'Inactive';
                    $color = $row->is_active ? 'success' : 'danger';
                    return '<span class="label label-' . $color . '">' . $status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button class="btn btn-xs btn-primary edit-bank" data-id="' . $row->id . '" title="Edit"><i class="fa fa-edit"></i></button>';
                    $html .= '<button class="btn btn-xs btn-danger delete-bank" data-id="' . $row->id . '" title="Delete"><i class="fa fa-trash"></i></button>';
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['logo_display', 'status_formatted', 'action'])
                ->make(true);
        }

        return view('superadmin::bank_management.index');
    }

    /**
     * Store a newly created bank
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:system_banks,code',
            'full_name' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        try {
            $bank_data = [
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'full_name' => $request->full_name,
                'country' => strtoupper($request->country),
                'is_active' => $request->has('is_active') ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $filename = 'bank_' . strtolower($request->code) . '_' . time() . '.' . $logo->getClientOriginalExtension();
                $path = $logo->storeAs('bank_logos', $filename, 'public');
                $bank_data['logo_url'] = asset('storage/' . $path);
            }

            DB::table('system_banks')->insert($bank_data);

            return response()->json([
                'success' => true,
                'msg' => 'Bank added successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to add bank: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the specified bank
     */
    public function show($id)
    {
        $bank = DB::table('system_banks')->where('id', $id)->first();
        
        if (!$bank) {
            return response()->json(['success' => false, 'msg' => 'Bank not found']);
        }

        return response()->json(['success' => true, 'bank' => $bank]);
    }

    /**
     * Update the specified bank
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:system_banks,code,' . $id,
            'full_name' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        try {
            $bank = DB::table('system_banks')->where('id', $id)->first();
            
            if (!$bank) {
                return response()->json(['success' => false, 'msg' => 'Bank not found']);
            }

            $bank_data = [
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'full_name' => $request->full_name,
                'country' => strtoupper($request->country),
                'is_active' => $request->has('is_active') ? 1 : 0,
                'updated_at' => now()
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($bank->logo_url) {
                    $old_path = str_replace(asset('storage/'), '', $bank->logo_url);
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $logo = $request->file('logo');
                $filename = 'bank_' . strtolower($request->code) . '_' . time() . '.' . $logo->getClientOriginalExtension();
                $path = $logo->storeAs('bank_logos', $filename, 'public');
                $bank_data['logo_url'] = asset('storage/' . $path);
            }

            DB::table('system_banks')->where('id', $id)->update($bank_data);

            return response()->json([
                'success' => true,
                'msg' => 'Bank updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to update bank: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified bank
     */
    public function destroy($id)
    {
        try {
            $bank = DB::table('system_banks')->where('id', $id)->first();
            
            if (!$bank) {
                return response()->json(['success' => false, 'msg' => 'Bank not found']);
            }

            // Check if bank is being used by any business accounts
            $usage_count = DB::table('business_bank_accounts')->where('bank_id', $id)->count();
            
            if ($usage_count > 0) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Cannot delete bank as it is being used by ' . $usage_count . ' business account(s)'
                ]);
            }

            // Delete logo file if exists
            if ($bank->logo_url) {
                $logo_path = str_replace(asset('storage/'), '', $bank->logo_url);
                if (Storage::disk('public')->exists($logo_path)) {
                    Storage::disk('public')->delete($logo_path);
                }
            }

            DB::table('system_banks')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'msg' => 'Bank deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to delete bank: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle bank status
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $bank = DB::table('system_banks')->where('id', $id)->first();
            
            if (!$bank) {
                return response()->json(['success' => false, 'msg' => 'Bank not found']);
            }

            $new_status = !$bank->is_active;
            
            DB::table('system_banks')
                ->where('id', $id)
                ->update([
                    'is_active' => $new_status,
                    'updated_at' => now()
                ]);

            $status_text = $new_status ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'msg' => 'Bank ' . $status_text . ' successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to update bank status: ' . $e->getMessage()
            ]);
        }
    }
}