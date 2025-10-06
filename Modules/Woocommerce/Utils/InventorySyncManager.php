<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Product;
use App\Variation;
use App\VariationLocationDetails;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Exception;

class InventorySyncManager
{
    /**
     * Automatically sync POS inventory levels to WooCommerce
     * This runs after POS transactions that affect stock levels
     */
    public static function syncInventoryToWooCommerce(int $businessId, int $locationId, array $options = []): array
    {
        try {
            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->first();

            if (!$locationSetting || !$locationSetting->is_active || !$locationSetting->sync_inventory) {
                return [
                    'success' => false,
                    'message' => 'Inventory sync not enabled for this location'
                ];
            }

            // Get WooCommerce client
            $client = $locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client');
            }

            // Get products that need inventory sync
            $productsToSync = self::getProductsNeedingInventorySync($businessId, $locationId, $options);
            
            if (empty($productsToSync)) {
                return [
                    'success' => true,
                    'message' => 'No products need inventory sync',
                    'synced_count' => 0
                ];
            }

            $syncedCount = 0;
            $errorCount = 0;
            $errors = [];

            Log::info('Starting automatic inventory sync to WooCommerce', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'products_to_sync' => count($productsToSync)
            ]);

            foreach ($productsToSync as $inventoryItem) {
                try {
                    $result = self::syncSingleProductInventory($inventoryItem, $client, $locationSetting);
                    
                    if ($result['success']) {
                        $syncedCount++;
                    } else {
                        $errorCount++;
                        $errors[] = [
                            'product_id' => $inventoryItem['product_id'],
                            'error' => $result['error'] ?? 'Unknown error'
                        ];
                    }

                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'product_id' => $inventoryItem['product_id'],
                        'error' => $e->getMessage()
                    ];

                    Log::error('Failed to sync individual product inventory', [
                        'product_id' => $inventoryItem['product_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Automatic inventory sync completed', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'synced_count' => $syncedCount,
                'error_count' => $errorCount
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'error_count' => $errorCount,
                'total_processed' => count($productsToSync),
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error('Automatic inventory sync failed', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync inventory for specific products that were affected by a transaction
     */
    public static function syncTransactionInventory(\App\Transaction $transaction): array
    {
        try {
            if (!$transaction->woocommerce_order_id) {
                // Only sync inventory for transactions linked to WooCommerce orders
                return [
                    'success' => false,
                    'message' => 'Transaction not linked to WooCommerce'
                ];
            }

            // Get affected products from transaction sell lines
            $affectedProducts = $transaction->sell_lines()
                                          ->with(['product', 'variation'])
                                          ->get()
                                          ->map(function($sellLine) {
                                              return [
                                                  'product_id' => $sellLine->product_id,
                                                  'variation_id' => $sellLine->variation_id
                                              ];
                                          })
                                          ->filter(function($item) {
                                              return $item['product_id'] && $item['variation_id'];
                                          })
                                          ->unique()
                                          ->values()
                                          ->toArray();

            if (empty($affectedProducts)) {
                return [
                    'success' => true,
                    'message' => 'No products to sync',
                    'synced_count' => 0
                ];
            }

            // Sync inventory for these specific products
            return self::syncInventoryToWooCommerce(
                $transaction->business_id,
                $transaction->location_id,
                [
                    'specific_products' => $affectedProducts,
                    'transaction_id' => $transaction->id
                ]
            );

        } catch (Exception $e) {
            Log::error('Failed to sync transaction inventory', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get products that need inventory sync to WooCommerce
     */
    private static function getProductsNeedingInventorySync(int $businessId, int $locationId, array $options = []): array
    {
        $query = DB::table('variation_location_details as vld')
                  ->join('products as p', 'vld.product_id', '=', 'p.id')
                  ->join('variations as v', 'vld.variation_id', '=', 'v.id')
                  ->where('vld.location_id', $locationId)
                  ->where('p.business_id', $businessId)
                  ->whereNotNull('p.woocommerce_product_id')
                  ->where('p.enable_stock', 1)
                  ->select([
                      'vld.id as vld_id',
                      'vld.product_id',
                      'vld.variation_id',
                      'vld.qty_available',
                      'vld.updated_at',
                      'p.woocommerce_product_id',
                      'v.woocommerce_variation_id',
                      'p.name as product_name',
                      'v.name as variation_name'
                  ]);

        // If specific products are requested (e.g., from a transaction)
        if (isset($options['specific_products']) && !empty($options['specific_products'])) {
            $productIds = array_column($options['specific_products'], 'product_id');
            $variationIds = array_column($options['specific_products'], 'variation_id');
            
            $query->whereIn('vld.product_id', $productIds)
                  ->whereIn('vld.variation_id', $variationIds);
        }

        // Only sync recently updated inventory (within last hour by default)
        $updatedSince = $options['updated_since'] ?? now()->subHour();
        if ($updatedSince) {
            $query->where('vld.updated_at', '>=', $updatedSince);
        }

        return $query->get()->toArray();
    }

    /**
     * Sync a single product's inventory to WooCommerce
     */
    private static function syncSingleProductInventory(array $inventoryItem, $client, WoocommerceLocationSetting $locationSetting): array
    {
        try {
            // Validate and sanitize stock quantity
            $stockQuantity = max(0, intval($inventoryItem['qty_available'] ?? 0));
            
            // Prepare stock data for WooCommerce
            $stockData = [
                'manage_stock' => true,
                'stock_quantity' => $stockQuantity,
                'in_stock' => $stockQuantity > 0,
                'stock_status' => $stockQuantity > 0 ? 'instock' : 'outofstock',
                'meta_data' => [
                    [
                        'key' => '_pos_sync_timestamp',
                        'value' => now()->toISOString()
                    ],
                    [
                        'key' => '_pos_location_id',
                        'value' => $locationSetting->location_id
                    ]
                ]
            ];

            // Determine if this is a variation or simple product
            if (!empty($inventoryItem['woocommerce_variation_id']) && $inventoryItem['woocommerce_variation_id'] != 0) {
                // Update variation stock
                $response = $client->put(
                    'products/' . $inventoryItem['woocommerce_product_id'] . '/variations/' . $inventoryItem['woocommerce_variation_id'],
                    $stockData
                );
                
                $entityType = 'variation';
                $entityId = $inventoryItem['woocommerce_variation_id'];
            } else {
                // Update simple product stock
                $response = $client->put(
                    'products/' . $inventoryItem['woocommerce_product_id'],
                    $stockData
                );
                
                $entityType = 'product';
                $entityId = $inventoryItem['woocommerce_product_id'];
            }

            // Validate response
            if (empty($response) || !is_array($response)) {
                throw new Exception('Invalid response from WooCommerce API');
            }

            // Log successful sync
            Log::debug('Product inventory synced to WooCommerce', [
                'product_name' => $inventoryItem['product_name'],
                'variation_name' => $inventoryItem['variation_name'] ?? null,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'pos_quantity' => $inventoryItem['qty_available'],
                'woo_quantity' => $response['stock_quantity'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'synced_quantity' => $stockQuantity
            ];

        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            // Handle specific WooCommerce API errors
            $contextualError = match($errorCode) {
                404 => 'Product or variation not found in WooCommerce',
                401 => 'WooCommerce API authentication failed',
                403 => 'WooCommerce API access forbidden',
                429 => 'WooCommerce API rate limit exceeded',
                default => 'WooCommerce API error: ' . $errorMessage
            };

            Log::warning('WooCommerce inventory sync API error', [
                'product_name' => $inventoryItem['product_name'],
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'contextual_error' => $contextualError
            ]);

            return [
                'success' => false,
                'error' => $contextualError,
                'error_code' => $errorCode
            ];

        } catch (Exception $e) {
            Log::error('General inventory sync error', [
                'product_name' => $inventoryItem['product_name'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Schedule automatic inventory sync after stock changes
     * This should be called from transaction events or stock adjustment events
     */
    public static function scheduleInventorySync(int $businessId, int $locationId, array $options = []): void
    {
        try {
            // Check if inventory sync is enabled
            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->where('is_active', true)
                                                        ->where('sync_inventory', true)
                                                        ->first();

            if (!$locationSetting) {
                return; // Inventory sync not enabled
            }

            // For immediate sync (real-time), process directly
            if ($options['immediate'] ?? false) {
                self::syncInventoryToWooCommerce($businessId, $locationId, $options);
                return;
            }

            // For deferred sync, you could dispatch a job here
            // \Modules\Woocommerce\Jobs\SyncInventoryJob::dispatch($businessId, $locationId, $options)
            //     ->delay(now()->addMinutes(2)); // Small delay to batch multiple changes

            Log::debug('Inventory sync scheduled', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'options' => $options
            ]);

        } catch (Exception $e) {
            Log::error('Failed to schedule inventory sync', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);
        }
    }
}