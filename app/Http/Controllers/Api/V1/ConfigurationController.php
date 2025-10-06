<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\BusinessLocation;
use App\Unit;
use App\Brands;
use App\Category;
use App\TaxRate;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ConfigurationController extends BaseApiController
{
    protected $businessUtil;
    protected $moduleUtil;

    public function __construct(
        BusinessUtil $businessUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Get business locations
     */
    public function locations(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $locations = BusinessLocation::where('business_id', $business_id)
                ->select('id', 'name', 'landmark', 'city', 'state', 'country', 'zip_code', 'mobile', 'email', 'is_active')
                ->get();

            $locations_data = $locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'landmark' => $location->landmark,
                    'city' => $location->city,
                    'state' => $location->state,
                    'country' => $location->country,
                    'zip_code' => $location->zip_code,
                    'mobile' => $location->mobile,
                    'email' => $location->email,
                    'is_active' => $location->is_active,
                    'full_address' => $location->landmark . ', ' . $location->city . ', ' . $location->state . ' ' . $location->zip_code
                ];
            });

            return $this->sendSuccess('Business locations retrieved successfully', [
                'locations' => $locations_data
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve business locations', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get product units
     */
    public function units(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $units = Unit::where('business_id', $business_id)
                ->select('id', 'actual_name', 'short_name', 'allow_decimal')
                ->get();

            return $this->sendSuccess('Product units retrieved successfully', [
                'units' => $units
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve product units', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get product brands
     */
    public function brands(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $brands = Brands::where('business_id', $business_id)
                ->select('id', 'name', 'description')
                ->get();

            return $this->sendSuccess('Product brands retrieved successfully', [
                'brands' => $brands
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve product brands', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get product categories
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $parent_id = $request->get('parent_id');
            
            $query = Category::where('business_id', $business_id);
            
            if ($parent_id !== null) {
                $query->where('parent_id', $parent_id);
            }
            
            $categories = $query->select('id', 'name', 'parent_id', 'category_type', 'description')
                ->get();

            // Organize into hierarchical structure if no parent_id filter
            if ($parent_id === null) {
                $organized_categories = $this->organizeCategoriesHierarchy($categories);
                
                return $this->sendSuccess('Product categories retrieved successfully', [
                    'categories' => $organized_categories
                ]);
            }

            return $this->sendSuccess('Product categories retrieved successfully', [
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve product categories', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get tax rates
     */
    public function taxRates(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $tax_rates = TaxRate::where('business_id', $business_id)
                ->select('id', 'name', 'amount', 'is_tax_group')
                ->get();

            return $this->sendSuccess('Tax rates retrieved successfully', [
                'tax_rates' => $tax_rates
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve tax rates', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get users
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $users = User::where('business_id', $business_id)
                ->select('id', 'first_name', 'last_name', 'username', 'email', 'is_active')
                ->with('roles:name')
                ->get();

            $users_data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->pluck('name')
                ];
            });

            return $this->sendSuccess('Users retrieved successfully', [
                'users' => $users_data
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve users', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get roles
     */
    public function roles(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $roles = Role::where('business_id', $business_id)
                ->select('id', 'name', 'guard_name')
                ->withCount('users')
                ->get();

            return $this->sendSuccess('Roles retrieved successfully', [
                'roles' => $roles
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve roles', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get business settings
     */
    public function businessSettings(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            // Get business settings directly from the business model
            $business = \App\Business::find($business_id);
            $currency = $business && $business->currency_id ? \App\Currency::find($business->currency_id) : null;
            
            // Filter sensitive information  
            $safe_settings = [
                'name' => $business->name ?? '',
                'currency' => $currency->code ?? 'USD',
                'currency_symbol' => $currency->symbol ?? '$',
                'currency_symbol_placement' => $business->currency_symbol_placement ?? 'before',
                'time_zone' => $business->time_zone ?? 'UTC',
                'date_format' => $business->date_format ?? 'm/d/Y',
                'time_format' => $business->time_format ?? '12',
                'financial_year' => $business->fy_start_month ?? 1,
                'accounting_method' => $business->accounting_method ?? 'fifo',
                'transaction_edit_days' => $business->transaction_edit_days ?? 30,
                'stock_expiry_alert_days' => $business->stock_expiry_alert_days ?? 30,
                'keyboard_shortcuts' => json_decode($business->keyboard_shortcuts ?? '{}', true),
                'pos_settings' => json_decode($business->pos_settings ?? '{}', true)
            ];

            return $this->sendSuccess('Business settings retrieved successfully', [
                'settings' => $safe_settings
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve business settings', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get payment methods
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $payment_methods = [
                'cash' => 'Cash',
                'card' => 'Card',
                'cheque' => 'Cheque',
                'bank_transfer' => 'Bank Transfer',
                'other' => 'Other',
                'custom_pay_1' => 'Custom Payment 1',
                'custom_pay_2' => 'Custom Payment 2',
                'custom_pay_3' => 'Custom Payment 3'
            ];

            return $this->sendSuccess('Payment methods retrieved successfully', [
                'payment_methods' => $payment_methods
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve payment methods', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get application configuration
     */
    public function appConfig(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $user = auth()->user();
            $business = \App\Business::find($business_id);
            
            $config = [
                'app_name' => config('app.name', 'IsleBooks POS'),
                'app_version' => '1.0.0',
                'api_version' => 'v1',
                'business_id' => $business_id,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'roles' => $user->getRoleNames()
                ],
                'features' => [
                    'pos' => true,
                    'purchases' => true,
                    'expenses' => true,
                    'stock_management' => true,
                    'reports' => true,
                    'multi_location' => BusinessLocation::where('business_id', $business_id)->count() > 1,
                    'modules' => [] // Module data would need specific implementation
                ],
                'defaults' => [
                    'currency' => $business && $business->currency_id ? \App\Currency::find($business->currency_id) : null,
                    'date_format' => $business->date_format ?? 'm/d/Y',
                    'time_format' => $business->time_format ?? '12'
                ]
            ];

            return $this->sendSuccess('Application configuration retrieved successfully', $config);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve application configuration', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Organize categories into hierarchical structure
     */
    private function organizeCategoriesHierarchy($categories)
    {
        $organized = [];
        
        // First, get all parent categories (parent_id = 0 or null)
        $parents = $categories->whereNull('parent_id')->values();
        
        foreach ($parents as $parent) {
            $parent_data = [
                'id' => $parent->id,
                'name' => $parent->name,
                'category_type' => $parent->category_type,
                'description' => $parent->description,
                'children' => []
            ];
            
            // Get children for this parent
            $children = $categories->where('parent_id', $parent->id)->values();
            
            foreach ($children as $child) {
                $parent_data['children'][] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'category_type' => $child->category_type,
                    'description' => $child->description,
                    'parent_id' => $child->parent_id
                ];
            }
            
            $organized[] = $parent_data;
        }
        
        return $organized;
    }
}