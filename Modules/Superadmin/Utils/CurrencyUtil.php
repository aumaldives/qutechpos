<?php

namespace Modules\Superadmin\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyUtil
{
    /**
     * Get current USD to MVR exchange rate
     * Uses cached rate for 1 hour, falls back to default rate if API fails
     *
     * @return float
     */
    public static function getUsdToMvrRate()
    {
        $cacheKey = 'usd_mvr_exchange_rate';
        $defaultRate = 15.42; // Default fallback rate (can be updated)
        
        // Check if there's a manually set rate in system settings first
        $manualRate = \App\System::getProperty('usd_to_mvr_exchange_rate');
        if (!empty($manualRate) && floatval($manualRate) > 0) {
            Cache::put($cacheKey, floatval($manualRate), 3600);
            return floatval($manualRate);
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($defaultRate) {
            try {
                // Try multiple free exchange rate APIs
                $rate = self::fetchFromExchangeRateApi();
                
                if (!$rate) {
                    $rate = self::fetchFromFreeCurrencyApi();
                }
                
                if (!$rate) {
                    $rate = self::fetchFromOpenExchangeRates();
                }
                
                // If all APIs fail, use default rate
                if (!$rate) {
                    Log::warning('All exchange rate APIs failed, using default USD to MVR rate: ' . $defaultRate);
                    return $defaultRate;
                }
                
                Log::info('USD to MVR exchange rate fetched successfully: ' . $rate);
                return $rate;
                
            } catch (\Exception $e) {
                Log::error('Error fetching USD to MVR exchange rate: ' . $e->getMessage());
                return $defaultRate;
            }
        });
    }
    
    /**
     * Fetch rate from exchangerate-api.com (free tier: 1,500 requests/month)
     *
     * @return float|null
     */
    private static function fetchFromExchangeRateApi()
    {
        try {
            $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['rates']['MVR']) ? floatval($data['rates']['MVR']) : null;
            }
        } catch (\Exception $e) {
            Log::warning('ExchangeRate-API failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Fetch rate from fixer.io free tier
     *
     * @return float|null
     */
    private static function fetchFromFreeCurrencyApi()
    {
        try {
            $response = Http::timeout(10)->get('https://api.fixer.io/latest?access_key=YOUR_API_KEY&base=USD&symbols=MVR');
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['rates']['MVR']) ? floatval($data['rates']['MVR']) : null;
            }
        } catch (\Exception $e) {
            Log::warning('Fixer.io API failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Fetch rate from openexchangerates.org
     *
     * @return float|null
     */
    private static function fetchFromOpenExchangeRates()
    {
        try {
            $response = Http::timeout(10)->get('https://openexchangerates.org/api/latest.json?app_id=YOUR_APP_ID&symbols=MVR');
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['rates']['MVR']) ? floatval($data['rates']['MVR']) : null;
            }
        } catch (\Exception $e) {
            Log::warning('OpenExchangeRates API failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Convert USD amount to MVR using given exchange rate
     *
     * @param float $usdAmount
     * @param float $exchangeRate
     * @return float
     */
    public static function convertUsdToMvr($usdAmount, $exchangeRate)
    {
        return round($usdAmount * $exchangeRate, 2);
    }
    
    /**
     * Manually update the exchange rate cache
     *
     * @param float $rate
     * @return void
     */
    public static function setManualExchangeRate($rate)
    {
        $cacheKey = 'usd_mvr_exchange_rate';
        Cache::put($cacheKey, floatval($rate), 3600);
        Log::info('Manual USD to MVR exchange rate set: ' . $rate);
    }
    
    /**
     * Clear the exchange rate cache
     *
     * @return void
     */
    public static function clearExchangeRateCache()
    {
        $cacheKey = 'usd_mvr_exchange_rate';
        Cache::forget($cacheKey);
        Log::info('USD to MVR exchange rate cache cleared');
    }
}