<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenSetting extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'setting_key', 'setting_value'];

    /**
     * Get kitchen setting value for a business
     */
    public static function getSetting($business_id, $key, $default = null)
    {
        $setting = self::where('business_id', $business_id)
                      ->where('setting_key', $key)
                      ->first();
        
        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set kitchen setting value for a business
     */
    public static function setSetting($business_id, $key, $value)
    {
        return self::updateOrCreate(
            ['business_id' => $business_id, 'setting_key' => $key],
            ['setting_value' => $value]
        );
    }

    /**
     * Get auto-cook categories for a business
     */
    public static function getAutoCookCategories($business_id)
    {
        $categories = self::getSetting($business_id, 'auto_cook_categories', '[]');
        return json_decode($categories, true) ?: [];
    }

    /**
     * Set auto-cook categories for a business
     */
    public static function setAutoCookCategories($business_id, $categories)
    {
        return self::setSetting($business_id, 'auto_cook_categories', json_encode($categories));
    }
}
