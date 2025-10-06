<?php

namespace Modules\Quickbooks\Http\Controllers;

use App\System;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Install the QuickBooks module.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        return view('quickbooks::install.index');
    }

    /**
     * Install/Update the QuickBooks module.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Run QuickBooks migrations
            Artisan::call('migrate', [
                '--path' => 'Modules/Quickbooks/Database/Migrations',
                '--force' => true,
            ]);

            // Set module version
            System::addProperty('quickbooks_version', '1.0');

            DB::commit();

            $output = Artisan::output();

            return redirect()
                ->route('quickbooks.install.index')
                ->with('status', [
                    'success' => 1,
                    'msg' => 'QuickBooks module installed/updated successfully!',
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('quickbooks.install.index')
                ->with('status', [
                    'success' => 0,
                    'msg' => 'Error installing QuickBooks module: ' . $e->getMessage(),
                ]);
        }
    }

    /**
     * Uninstall the QuickBooks module.
     * Note: This doesn't remove the database tables to preserve data.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Remove module version to mark as uninstalled
            System::removeProperty('quickbooks_version');

            return redirect()
                ->route('quickbooks.install.index')
                ->with('status', [
                    'success' => 1,
                    'msg' => 'QuickBooks module uninstalled successfully! Database tables preserved.',
                ]);

        } catch (\Exception $e) {
            return redirect()
                ->route('quickbooks.install.index')
                ->with('status', [
                    'success' => 0,
                    'msg' => 'Error uninstalling QuickBooks module: ' . $e->getMessage(),
                ]);
        }
    }
}