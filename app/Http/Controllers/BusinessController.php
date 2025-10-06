<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Currency;
use App\Notifications\TestEmailNotification;
use App\System;
use App\TaxRate;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Mail\OtpEmail;
use Illuminate\Support\Facades\Mail;



class BusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BusinessController
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new business/business as well as their
    | validation and creation.
    |
    */

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $restaurantUtil;

    protected $moduleUtil;

    protected $mailDrivers;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, RestaurantUtil $restaurantUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->theme_colors = [
            'blue' => 'Blue',
            'black' => 'Black',
            'purple' => 'Purple',
            'green' => 'Green',
            'red' => 'Red',
            'yellow' => 'Yellow',
            'blue-light' => 'Blue Light',
            'black-light' => 'Black Light',
            'purple-light' => 'Purple Light',
            'green-light' => 'Green Light',
            'red-light' => 'Red Light',
        ];

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            // 'sendmail' => 'Sendmail',
            // 'mailgun' => 'Mailgun',
            // 'mandrill' => 'Mandrill',
            // 'ses' => 'SES',
            // 'sparkpost' => 'Sparkpost'
        ];
    }

    /**
     * Shows registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        if (!config('constants.allow_registration')) {
            return redirect('/');
        }

        $currencies = $this->businessUtil->allCurrencies();

        $timezone_list = $this->businessUtil->allTimeZones();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.' . $i);
        }

        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $package_id = request()->package;

        $system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);

        return view('business.register', compact(
            'currencies',
            'timezone_list',
            'months',
            'accounting_methods',
            'package_id',
            'system_settings'
        ));
    }

    /**
     * Handles the registration of a new business and it's owner
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        if (!config('constants.allow_registration')) {
            return redirect('/');
        }

        try {
            $validator = $request->validate(
                [
                    'name' => 'required|max:255',
                    'currency_id' => 'required|numeric',
                    'country' => 'required|max:255',
                    'state' => 'required|max:255',
                    'city' => 'required|max:255',
                    'zip_code' => 'required|max:255',
                    'landmark' => 'required|max:255',
                    'time_zone' => 'required|max:255',
                    'surname' => 'max:10',
                    'email' => 'sometimes|nullable|email|unique:users|max:255',
                    'first_name' => 'required|max:255',
                    'username' => 'required|min:4|max:255|unique:users',
                    'password' => 'required|min:4|max:255',
                    'fy_start_month' => 'required',
                    'accounting_method' => 'required',
                ],
                [
                    'name.required' => __('validation.required', ['attribute' => __('business.business_name')]),
                    'name.currency_id' => __('validation.required', ['attribute' => __('business.currency')]),
                    'country.required' => __('validation.required', ['attribute' => __('business.country')]),
                    'state.required' => __('validation.required', ['attribute' => __('business.state')]),
                    'city.required' => __('validation.required', ['attribute' => __('business.city')]),
                    'zip_code.required' => __('validation.required', ['attribute' => __('business.zip_code')]),
                    'landmark.required' => __('validation.required', ['attribute' => __('business.landmark')]),
                    'time_zone.required' => __('validation.required', ['attribute' => __('business.time_zone')]),
                    'email.email' => __('validation.email', ['attribute' => __('business.email')]),
                    'email.email' => __('validation.unique', ['attribute' => __('business.email')]),
                    'first_name.required' => __('validation.required', ['attribute' => __('business.first_name')]),
                    'username.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'username.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'password.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'password.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'fy_start_month.required' => __('validation.required', ['attribute' => __('business.fy_start_month')]),
                    'accounting_method.required' => __('validation.required', ['attribute' => __('business.accounting_method')]),
                ]
            );

            DB::beginTransaction();

            //Create owner.
            $owner_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password', 'language']);

            $owner_details['language'] = empty($owner_details['language']) ? config('app.locale') : $owner_details['language'];

            $user = User::create_user($owner_details);

            $business_details = $request->only([
                'name', 'start_date', 'currency_id', 'time_zone',
                'fy_start_month', 'accounting_method', 'tax_label_1', 'tax_number_1',
                'tax_label_2', 'tax_number_2',
            ]);

            $business_location = $request->only([
                'name', 'country', 'state', 'city', 'zip_code', 'landmark',
                'website', 'mobile', 'alternate_number',
            ]);

            //Create the business
            $business_details['owner_id'] = $user->id;
            if (!empty($business_details['start_date'])) {
                $business_details['start_date'] = Carbon::createFromFormat(config('constants.default_date_format'), $business_details['start_date'])->toDateString();
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (!empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            //default enabled modules
            $business_details['enabled_modules'] = ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'];

            $business = $this->businessUtil->createNewBusiness($business_details);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            //Process payment information if superadmin is installed & package information is present
            $is_installed_superadmin = $this->moduleUtil->isSuperadminInstalled();
            $package_id = $request->get('package_id', null);
            if ($is_installed_superadmin && !empty($package_id) && (config('app.env') != 'demo')) {
                $package = \Modules\Superadmin\Entities\Package::find($package_id);
                if (!empty($package)) {
                    Auth::login($user);

                    return redirect()->route('register-pay', ['package_id' => $package_id]);
                }
            }

            $output = [
                'success' => 1,
                'msg' => __('business.business_created_succesfully'),
            ];

            /** Mofication for otp geneartion */

            $this->generateOtp($user);
            return redirect()->route('otp.verify', ['token' => md5($user->email)]);

            /** End modification for otp generation */

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }

   /** OTP Functionality START */

   public function generateOtp(User $user)
    {
        $otpCode = mt_rand(100000, 999999);
        $user->otp_code = $otpCode;
        $user->save();

        Mail::to($user->email)->send(new OtpEmail($otpCode));
    }

   public function getOtpVerify(Request $request)
    {
        $encryptedToken = $request->input('token');
        $user = User::where(DB::raw('MD5(email)'), '=', $encryptedToken)->firstOrFail();

        return view('layouts.otp_verify', compact('user'));
    }

    public function postOtpVerify(Request $request)
    {
        $request->validate([
            'otp_number' => 'required|digits:6'
        ]);

        $encryptedToken = $request->input('token');
        $user = User::where(DB::raw('MD5(email)'), '=', $encryptedToken)->firstOrFail();

        if ($user->otp_code === $request->input('otp_number')) {
                $user->otp_code = null;
                $user->is_email_verified = 1;
                $user->save();
                Auth::login($user);
                return redirect('/home');
        } else {
            $output = [
                'success' => 0,
                'msg' => __('Invalid OTP code. Please try again.'),
            ];
            return back()->with('status', $output)->withInput();
        }
    }

    public function resendOtp(Request $request)
    {
        $encryptedToken = $request->input('token');
        $user = User::where(DB::raw('MD5(email)'), '=', $encryptedToken)->firstOrFail();
        if ($user->is_email_verified == "0") {
            $this->generateOtp($user);
            $output = [
                'success' => 1,
                'msg' => __('OTP code send successfully.'),
            ];
        }
        return redirect()->route('otp.verify', ['token' => md5($user->email)])->with('status', $output);
    }

   /** OTP Functionality END */

    /**
     * Handles the validation username
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckUsername(Request $request)
    {
        $username = $request->input('username');

        if (!empty($request->input('username_ext'))) {
            $username .= $request->input('username_ext');
        }

        $count = User::where('username', $username)->count();

        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * Shows business settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function getBusinessSettings()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $timezone_list = [];
        foreach ($timezones as $timezone) {
            $timezone_list[$timezone] = $timezone;
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $currencies = $this->businessUtil->allCurrencies();
        $tax_details = TaxRate::forBusinessDropdown($business_id);
        $tax_rates = $tax_details['tax_rates'];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.' . $i);
        }

        $accounting_methods = [
            'fifo' => __('business.fifo'),
            'lifo' => __('business.lifo'),
        ];
        $commission_agent_dropdown = [
            '' => __('lang_v1.disable'),
            'logged_in_user' => __('lang_v1.logged_in_user'),
            'user' => __('lang_v1.select_from_users_list'),
            'cmsn_agnt' => __('lang_v1.select_from_commisssion_agents_list'),
        ];

        $units_dropdown = Unit::forDropdown($business_id, true);

        $date_formats = Business::date_formats();

        $shortcuts = json_decode($business->keyboard_shortcuts, true);

        $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

        $email_settings = empty($business->email_settings) ? $this->businessUtil->defaultEmailSettings() : $business->email_settings;

        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        $modules = $this->moduleUtil->availableModules();
        
        $enabled_modules = !empty($business->enabled_modules) ? (is_string($business->enabled_modules) ? json_decode($business->enabled_modules, true) : $business->enabled_modules) : [];

        $theme_colors = $this->theme_colors;

        $mail_drivers = $this->mailDrivers;

        $allow_superadmin_email_settings = System::getProperty('allow_email_settings_to_businesses');

        $custom_labels = !empty($business->custom_labels) ? json_decode($business->custom_labels, true) : [];

        $common_settings = !empty($business->common_settings) ? $business->common_settings : [];

        $weighing_scale_setting = !empty($business->weighing_scale_setting) ? $business->weighing_scale_setting : [];

        $payment_types = $this->moduleUtil->payment_types(null, false, $business_id);

        // Bank transfer related data
        $system_banks = \DB::table('system_banks')
            ->where('is_active', 1)
            ->where('country', 'MV') // Can be made configurable
            ->orderBy('name')
            ->get();

        $bank_accounts = \DB::table('business_bank_accounts')
            ->leftJoin('system_banks', 'business_bank_accounts.bank_id', '=', 'system_banks.id')
            ->leftJoin('business_locations', 'business_bank_accounts.location_id', '=', 'business_locations.id')
            ->where('business_bank_accounts.business_id', $business_id)
            ->select(
                'business_bank_accounts.*',
                'system_banks.name as bank_name',
                'system_banks.logo_url as bank_logo',
                'business_locations.name as location_name'
            )
            ->orderBy('business_bank_accounts.created_at', 'desc')
            ->get();

        $business_locations_data = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations = $business_locations_data['locations'] ?? $business_locations_data;

        return view('business.settings', compact('business', 'currencies', 'tax_rates', 'timezone_list', 'months', 'accounting_methods', 'commission_agent_dropdown', 'units_dropdown', 'date_formats', 'shortcuts', 'pos_settings', 'modules', 'enabled_modules', 'theme_colors', 'email_settings', 'sms_settings', 'mail_drivers', 'allow_superadmin_email_settings', 'custom_labels', 'common_settings', 'weighing_scale_setting', 'payment_types', 'system_banks', 'bank_accounts', 'business_locations'));
    }

    /**
     * Display bank transfer settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function getBankTransferSettings()
    {
        if (!auth()->user()->can('access_bank_transfer_settings')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        // Get all active system banks
        $system_banks = \DB::table('system_banks')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get();

        // Get business bank accounts
        $bank_accounts = \DB::table('business_bank_accounts')
            ->leftJoin('system_banks', 'business_bank_accounts.bank_id', '=', 'system_banks.id')
            ->leftJoin('business_locations', 'business_bank_accounts.location_id', '=', 'business_locations.id')
            ->where('business_bank_accounts.business_id', $business_id)
            ->select(
                'business_bank_accounts.*',
                'system_banks.name as bank_name',
                'system_banks.logo_url as bank_logo',
                'business_locations.name as location_name'
            )
            ->orderBy('business_bank_accounts.created_at', 'desc')
            ->get();

        $business_locations_data = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations = $business_locations_data['locations'] ?? $business_locations_data;

        return view('business.bank_transfer_settings', compact('business', 'system_banks', 'bank_accounts', 'business_locations'));
    }

    /**
     * Updates business settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postBusinessSettings(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->businessUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            $business_details = $request->only([
                'name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'default_profit_percent', 'default_sales_tax', 'default_sales_discount', 'sell_price_tax', 'sku_prefix', 'time_zone', 'fy_start_month', 'accounting_method', 'transaction_edit_days', 'sales_cmsn_agnt', 'item_addition_method', 'currency_symbol_placement', 'on_product_expiry',
                'stop_selling_before', 'default_unit', 'expiry_type', 'date_format',
                'time_format', 'ref_no_prefixes', 'theme_color', 'email_settings',
                'sms_settings', 'rp_name', 'amount_for_unit_rp',
                'min_order_total_for_rp', 'max_rp_per_order',
                'redeem_amount_per_unit_rp', 'min_order_total_for_redeem',
                'min_redeem_point', 'max_redeem_point', 'rp_expiry_period',
                'rp_expiry_type', 'custom_labels', 'weighing_scale_setting',
                'code_label_1', 'code_1', 'code_label_2', 'code_2', 'currency_precision', 'quantity_precision', 'enable_avl_quantity_pos',
                'max_bank_transfer_amount', 'min_bank_transfer_amount', 'bank_transfer_instructions',
                'enable_bank_transfer_payment', 'auto_approve_bank_payments', 'send_bank_payment_notifications',
            ]);

            if (!empty($request->input('enable_rp')) && $request->input('enable_rp') == 1) {
                $business_details['enable_rp'] = 1;
            } else {
                $business_details['enable_rp'] = 0;
            }

            $business_details['amount_for_unit_rp'] = !empty($business_details['amount_for_unit_rp']) ? $this->businessUtil->num_uf($business_details['amount_for_unit_rp']) : 1;
            $business_details['min_order_total_for_rp'] = !empty($business_details['min_order_total_for_rp']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_rp']) : 1;
            $business_details['redeem_amount_per_unit_rp'] = !empty($business_details['redeem_amount_per_unit_rp']) ? $this->businessUtil->num_uf($business_details['redeem_amount_per_unit_rp']) : 1;
            $business_details['min_order_total_for_redeem'] = !empty($business_details['min_order_total_for_redeem']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_redeem']) : 1;

            $business_details['default_profit_percent'] = !empty($business_details['default_profit_percent']) ? $this->businessUtil->num_uf($business_details['default_profit_percent']) : 0;

            $business_details['default_sales_discount'] = !empty($business_details['default_sales_discount']) ? $this->businessUtil->num_uf($business_details['default_sales_discount']) : 0;

            if (!empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            if (!empty($request->input('enable_tooltip')) && $request->input('enable_tooltip') == 1) {
                $business_details['enable_tooltip'] = 1;
            } else {
                $business_details['enable_tooltip'] = 0;
            }

            $business_details['enable_product_expiry'] = !empty($request->input('enable_product_expiry')) && $request->input('enable_product_expiry') == 1 ? 1 : 0;
            if (!empty($business_details['on_product_expiry']) && $business_details['on_product_expiry'] == 'keep_selling') {
                $business_details['stop_selling_before'] = null;
            }

            // Bank transfer settings
            $business_details['enable_bank_transfer_payment'] = !empty($request->input('enable_bank_transfer_payment')) && $request->input('enable_bank_transfer_payment') == 1 ? 1 : 0;
            $business_details['auto_approve_bank_payments'] = !empty($request->input('auto_approve_bank_payments')) && $request->input('auto_approve_bank_payments') == 1 ? 1 : 0;
            $business_details['send_bank_payment_notifications'] = !empty($request->input('send_bank_payment_notifications')) && $request->input('send_bank_payment_notifications') == 1 ? 1 : 0;

            $business_details['stock_expiry_alert_days'] = !empty($request->input('stock_expiry_alert_days')) ? $request->input('stock_expiry_alert_days') : 30;

            //Check for Purchase currency
            if (!empty($request->input('purchase_in_diff_currency')) && $request->input('purchase_in_diff_currency') == 1) {
                $business_details['purchase_in_diff_currency'] = 1;
                $business_details['purchase_currency_id'] = $request->input('purchase_currency_id');
                $business_details['p_exchange_rate'] = $request->input('p_exchange_rate');
            } else {
                $business_details['purchase_in_diff_currency'] = 0;
                $business_details['purchase_currency_id'] = null;
                $business_details['p_exchange_rate'] = 1;
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (!empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            /* $checkboxes = [
                'enable_editing_product_from_purchase',
                'enable_inline_tax',
                'enable_brand', 'enable_category', 'enable_sub_category', 'enable_price_tax', 'enable_purchase_status',
                'enable_lot_number', 'enable_racks', 'enable_row', 'enable_position', 'enable_sub_units',
            ]; */

            $checkboxes = [
                'enable_editing_product_from_purchase',
                'enable_brand',
                //added inline Exclusing tax
                'enable_exc_tax', 
                'enable_category', 'enable_sub_category', 'enable_price_tax', 'enable_purchase_status',
                'enable_lot_number', 'enable_racks', 'enable_row', 'enable_position', 'enable_sub_units', 'enable_avl_quantity_pos', 
            ];
            foreach ($checkboxes as $value) {
                $business_details[$value] = !empty($request->input($value)) && $request->input($value) == 1 ? 1 : 0;
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();

            //Update business settings
            if (!empty($business_details['logo'])) {
                $business->logo = $business_details['logo'];
            } else {
                unset($business_details['logo']);
            }

            //System settings
            $shortcuts = $request->input('shortcuts');
            $business_details['keyboard_shortcuts'] = json_encode($shortcuts);

            //pos_settings
            $pos_settings = $request->input('pos_settings');
            $default_pos_settings = $this->businessUtil->defaultPosSettings();
            foreach ($default_pos_settings as $key => $value) {
                if (!isset($pos_settings[$key])) {
                    $pos_settings[$key] = $value;
                }
            }
            
            // Merge warranty settings: automatically enable warranty management when product warranty is enabled
            $common_settings = $request->input('common_settings', []);
            if (!empty($common_settings['enable_product_warranty']) && $common_settings['enable_product_warranty'] == 1) {
                $pos_settings['enable_warranty_management'] = 1;
            } else {
                $pos_settings['enable_warranty_management'] = 0;
            }
            
            $business_details['pos_settings'] = json_encode($pos_settings);

            $business_details['custom_labels'] = json_encode($business_details['custom_labels'] ?? []);

            $business_details['common_settings'] = !empty($request->input('common_settings')) ? $request->input('common_settings') : [];

            //Enabled modules - only process if NOT from bank transfer settings page
            if (!$request->has('enable_bank_transfer_payment')) {
                $enabled_modules = $request->input('enabled_modules');
                $business_details['enabled_modules'] = !empty($enabled_modules) ? $enabled_modules : null;
            }
            $business->fill($business_details);
            $business->save();

            //update session data
            $request->session()->put('business', $business);

            //Update Currency details
            $currency = Currency::find($business->currency_id);
            $request->session()->put('currency', [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ]);

            //update current financial year to session
            $financial_year = $this->businessUtil->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);

            $output = [
                'success' => 1,
                'msg' => __('business.settings_updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }
        
        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Handles the validation email
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckEmail(Request $request)
    {
        $email = $request->input('email');

        $query = User::where('email', $email);

        if (!empty($request->input('user_id'))) {
            $user_id = $request->input('user_id');
            $query->where('id', '!=', $user_id);
        }

        $exists = $query->exists();
        if (!$exists) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    public function getEcomSettings()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $settings = Business::where('id', $api_settings->business_id)
                ->value('ecom_settings');

            $settings_array = !empty($settings) ? json_decode($settings, true) : [];

            if (!empty($settings_array['slides'])) {
                foreach ($settings_array['slides'] as $key => $value) {
                    $settings_array['slides'][$key]['image_url'] = !empty($value['image']) ? url('uploads/img/' . $value['image']) : '';
                }
            }
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($settings_array);
    }

    /**
     * Handles the testing of email configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testEmailConfiguration(Request $request)
    {
        try {
            $email_settings = $request->input();

            $data['email_settings'] = $email_settings;
            \Notification::route('mail', $email_settings['mail_from_address'])
                ->notify(new TestEmailNotification($data));

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.email_tested_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Handles the testing of sms configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testSmsConfiguration(Request $request)
    {
        try {
            $sms_settings = $request->input();

            $data = [
                'sms_settings' => $sms_settings,
                'mobile_number' => $sms_settings['test_number'],
                'sms_body' => 'This is a test SMS',
            ];
            if (!empty($sms_settings['test_number'])) {
                $response = $this->businessUtil->sendSms($data);
            } else {
                $response = __('lang_v1.test_number_is_required');
            }

            $output = [
                'success' => 1,
                'msg' => $response,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Get bank account details by ID
     */
    public function getBankAccount($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $account = \DB::table('business_bank_accounts')
            ->leftJoin('system_banks', 'business_bank_accounts.bank_id', '=', 'system_banks.id')
            ->where('business_bank_accounts.id', $id)
            ->where('business_bank_accounts.business_id', $business_id)
            ->select(
                'business_bank_accounts.*',
                'system_banks.name as bank_name'
            )
            ->first();

        if (!$account) {
            return response()->json(['success' => false, 'msg' => 'Bank account not found']);
        }

        return response()->json(['success' => true, 'account' => $account]);
    }

    /**
     * Store new bank account
     */
    public function storeBankAccount(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'bank_id' => 'required|exists:system_banks,id',
            'account_name' => 'required|string|max:191',
            'account_number' => 'required|string|max:191',
            'account_type' => 'required|string|max:191',
            'location_id' => 'nullable|exists:business_locations,id',
            'swift_code' => 'nullable|string|max:191',
            'branch_name' => 'nullable|string|max:191',
            'notes' => 'nullable|string'
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            
            // Check if location belongs to business
            if ($request->location_id) {
                $location = \DB::table('business_locations')
                    ->where('id', $request->location_id)
                    ->where('business_id', $business_id)
                    ->first();
                
                if (!$location) {
                    return response()->json(['success' => false, 'msg' => 'Invalid location selected']);
                }
            }

            \DB::table('business_bank_accounts')->insert([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'bank_id' => $request->bank_id,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'account_type' => $request->account_type,
                'swift_code' => $request->swift_code,
                'branch_name' => $request->branch_name,
                'is_active' => $request->has('is_active') ? 1 : 0,
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'msg' => 'Bank account added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'Failed to add bank account']);
        }
    }

    /**
     * Update existing bank account
     */
    public function updateBankAccount($id, Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'bank_id' => 'required|exists:system_banks,id',
            'account_name' => 'required|string|max:191',
            'account_number' => 'required|string|max:191',
            'account_type' => 'required|string|max:191',
            'location_id' => 'nullable|exists:business_locations,id',
            'swift_code' => 'nullable|string|max:191',
            'branch_name' => 'nullable|string|max:191',
            'notes' => 'nullable|string'
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            
            // Check if account belongs to business
            $account = \DB::table('business_bank_accounts')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->first();
            
            if (!$account) {
                return response()->json(['success' => false, 'msg' => 'Bank account not found']);
            }

            // Check if location belongs to business
            if ($request->location_id) {
                $location = \DB::table('business_locations')
                    ->where('id', $request->location_id)
                    ->where('business_id', $business_id)
                    ->first();
                
                if (!$location) {
                    return response()->json(['success' => false, 'msg' => 'Invalid location selected']);
                }
            }

            \DB::table('business_bank_accounts')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->update([
                    'location_id' => $request->location_id,
                    'bank_id' => $request->bank_id,
                    'account_name' => $request->account_name,
                    'account_number' => $request->account_number,
                    'account_type' => $request->account_type,
                    'swift_code' => $request->swift_code,
                    'branch_name' => $request->branch_name,
                    'is_active' => $request->has('is_active') ? 1 : 0,
                    'notes' => $request->notes,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true, 'msg' => 'Bank account updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'Failed to update bank account']);
        }
    }

    /**
     * Delete bank account
     */
    public function deleteBankAccount($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            
            // Check if account has pending payments
            $pending_payments = \DB::table('pending_bank_payments')
                ->where('bank_account_id', $id)
                ->whereIn('status', ['pending', 'approved'])
                ->count();
            
            if ($pending_payments > 0) {
                return response()->json([
                    'success' => false, 
                    'msg' => 'Cannot delete bank account with pending payments. Please process all pending payments first.'
                ]);
            }

            $deleted = \DB::table('business_bank_accounts')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->delete();

            if ($deleted) {
                return response()->json(['success' => true, 'msg' => 'Bank account deleted successfully']);
            } else {
                return response()->json(['success' => false, 'msg' => 'Bank account not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => 'Failed to delete bank account']);
        }
    }
}
