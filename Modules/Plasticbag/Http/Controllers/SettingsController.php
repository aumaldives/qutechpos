<?php

namespace Modules\Plasticbag\Http\Controllers;

use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SettingsController extends Controller
{
    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = session()->get('user.business_id');
        $is_demo = (config('app.env') == 'demo');

        $business_id = request()->session()->get('user.business_id');
        $settingRow = DB::table('plasticbag_settings')->where('business_id', $business_id)->count() > 0 ? (array) DB::table('plasticbag_settings')->where('business_id', $business_id)->first() : [];
        return view('plasticbag::settings.index')->with(compact('settingRow', 'is_demo'));
    }

    /**
     * Store
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $settingRow = DB::table('plasticbag_settings')->where('business_id', $business_id)->count() > 0 ? (array) DB::table('plasticbag_settings')->where('business_id', $business_id)->first() : [];
        $currentDateTime = Carbon::now();
        if(empty($settingRow)) {
            DB::table('plasticbag_settings')->insert([
                'plasticbag_per_piece' => $request->plasticbag_per_piece,
                'business_id' => $business_id,
                'created_at' => $currentDateTime,
                'updated_at' => $currentDateTime
            ]);
        } else {
            DB::table('plasticbag_settings')
                ->where('id', $settingRow['id'])
                ->where('business_id', $business_id)
                ->update([
                    'plasticbag_per_piece' => $request->plasticbag_per_piece,
                    'updated_at' => $currentDateTime
                ]);
        }
        $output = ['success' => 1,
                'msg' => 'Plasticbag settings saved succesfully',
        ];
        return redirect()->back()->with('status', $output);
    }
}