<?php

namespace Modules\Quickbooks\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Module;

class CheckQuickBooksModule
{
    public function handle(Request $request, Closure $next)
    {
        if (!Module::find('Quickbooks') || !Module::find('Quickbooks')->isEnabled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'QuickBooks module is not enabled',
                    'upgrade_required' => true,
                    'message' => 'QuickBooks integration requires an upgraded package. Please contact your administrator or upgrade your subscription.'
                ], 403);
            }

            return redirect()->route('integrations')
                           ->with('error', 'QuickBooks integration requires an upgraded package. Please contact your administrator or upgrade your subscription.');
        }

        return $next($request);
    }
}