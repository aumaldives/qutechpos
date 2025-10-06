<?php

namespace App\Http\Controllers;

use App\Category;
use App\KitchenSetting;
use Illuminate\Http\Request;

class KitchenSettingsController extends Controller
{
    /**
     * Check if kitchen module is enabled
     */
    private function checkKitchenModule()
    {
        $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
        if (is_string($enabled_modules)) {
            $enabled_modules = json_decode($enabled_modules, true) ?? [];
        }
        if (!is_array($enabled_modules)) {
            $enabled_modules = [];
        }
        
        if (!in_array('kitchen', $enabled_modules)) {
            abort(404, 'Kitchen module is not enabled');
        }
    }

    /**
     * Display kitchen settings page
     */
    public function index()
    {
        $this->checkKitchenModule();
        
        $business_id = request()->session()->get('user.business_id');
        
        // Get all categories and subcategories for the business
        $categories = Category::where('business_id', $business_id)
                            ->where('category_type', 'product')
                            ->orderBy('name')
                            ->get();

        // Get auto-cook categories setting
        $auto_cook_categories = KitchenSetting::getAutoCookCategories($business_id);

        return view('kitchen_settings.index', compact('categories', 'auto_cook_categories'));
    }

    /**
     * Update kitchen settings
     */
    public function update(Request $request)
    {
        $this->checkKitchenModule();
        
        $business_id = request()->session()->get('user.business_id');
        
        // Handle category settings
        if ($request->has('auto_cook_categories')) {
            $auto_cook_categories = $request->input('auto_cook_categories', []);
            KitchenSetting::setAutoCookCategories($business_id, $auto_cook_categories);
        }

        $output = [
            'success' => true,
            'msg' => __('lang_v1.updated_success')
        ];

        return redirect()->back()->with('status', $output);
    }

    /**
     * Get categories for select2
     */
    public function getCategories()
    {
        $this->checkKitchenModule();
        
        $business_id = request()->session()->get('user.business_id');
        
        $categories = Category::where('business_id', $business_id)
                            ->where('category_type', 'product')
                            ->select('id', 'name', 'parent_id')
                            ->orderBy('name')
                            ->get();

        $formatted_categories = [];
        
        foreach ($categories as $category) {
            $text = $category->name;
            if ($category->parent_id) {
                $parent = $categories->firstWhere('id', $category->parent_id);
                if ($parent) {
                    $text = $parent->name . ' → ' . $category->name;
                }
            }
            
            $formatted_categories[] = [
                'id' => $category->id,
                'text' => $text
            ];
        }

        return response()->json($formatted_categories);
    }
}
