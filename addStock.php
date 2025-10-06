<?php

require_once 'public/sdks/php/IslebooksAPI.php';

use IslebooksAPI\IslebooksClient;
use IslebooksAPI\IslebooksAPIException;

class AddStock
{
    private IslebooksClient $client;
    private int $usdtSwapLocationId = 503;
    private int $usdtProductId = 373;

    public function __construct()
    {
        // Initialize with predefined credentials
        $baseUrl = 'https://epos.qutech.mv';
        $apiKey = 'ib_CxuLZQOVEh0iFKVJASV2z12PYIBbA7BGqIMazFtrk4wZ2ZLckR5gLhfRhoFZWvat';

        $this->client = new IslebooksClient($baseUrl, $apiKey, [
            'timeout' => 30
        ]);
    }

    /**
     * Add stock for USDT product to USDT SWAP location using the new connector API
     *
     * @param float $quantity Quantity to add
     * @param float $costPerUnit Cost per unit
     * @param array $options Additional options
     * @return array API response
     * @throws IslebooksAPIException
     */
    public function addStockToUSDTSwap(float $quantity, float $costPerUnit, array $options = []): array
    {
        $requestData = [
            'quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'supplier_id' => $options['supplier_id'] ?? 16, // Use correct supplier ID
            'ref_no' => $options['reference_no'] ?? 'USDT-STOCK-' . time(),
            'transaction_date' => $options['transaction_date'] ?? date('d/m/Y H:i'),
            'notes' => $options['notes'] ?? 'USDT stock added via AddStock class'
        ];

        try {
            echo "Adding USDT stock to USDT SWAP location...\n";
            echo "Product ID: {$this->usdtProductId}\n";
            echo "Location ID: {$this->usdtSwapLocationId}\n";
            echo "Quantity: {$quantity}\n";
            echo "Cost per unit: {$costPerUnit}\n";
            echo "Total cost: " . ($quantity * $costPerUnit) . "\n\n";

            // Use the new connector API endpoint
            $response = $this->client->request('POST', '/connector/usdt-stock', $requestData);

            echo "✓ USDT stock added successfully!\n";
            if (isset($response['data']['id'])) {
                echo "Purchase ID: {$response['data']['id']}\n";
            }
            if (isset($response['data']['ref_no'])) {
                echo "Reference: {$response['data']['ref_no']}\n";
            }

            return $response;

        } catch (IslebooksAPIException $e) {
            echo "✗ Error adding USDT stock: {$e->getMessage()}\n";

            if ($e->getCode() == 422) {
                echo "Validation errors occurred. Check the response details:\n";
                print_r($e->getResponse());
            }

            throw $e;
        }
    }

    /**
     * Add stock for any product to USDT SWAP location
     *
     * @param int $productId Product ID
     * @param float $quantity Quantity to add
     * @param float $costPerUnit Cost per unit
     * @param array $options Additional options
     * @return array API response
     */
    public function addProductStockToUSDTSwap(int $productId, float $quantity, float $costPerUnit, array $options = []): array
    {
        $totalCost = $quantity * $costPerUnit;

        $purchaseData = [
            'transaction_date' => $options['transaction_date'] ?? date('Y-m-d H:i:s'),
            'ref_no' => $options['reference_no'] ?? 'STOCK-' . $productId . '-' . time(),
            'location_id' => $this->usdtSwapLocationId,
            'status' => 'received',
            'contact_id' => $options['supplier_id'] ?? 1,
            'total_before_tax' => $totalCost,
            'final_total' => $totalCost,
            'additional_notes' => $options['notes'] ?? "Stock added for product {$productId} via AddStock class",
            'products' => [
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_cost' => $costPerUnit,
                    'purchase_price' => $costPerUnit
                ]
            ]
        ];

        try {
            echo "Adding stock for product {$productId} to USDT SWAP location...\n";
            echo "Quantity: {$quantity}, Cost per unit: {$costPerUnit}\n";

            $response = $this->client->request('POST', '/purchases', $purchaseData);

            echo "✓ Stock added successfully!\n";
            echo "Purchase ID: {$response['data']['id']}\n";

            return $response;

        } catch (IslebooksAPIException $e) {
            echo "✗ Error adding stock: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get current stock levels for a product
     */
    public function getProductStock(int $productId): array
    {
        try {
            return $this->client->products()->getStock($productId);
        } catch (IslebooksAPIException $e) {
            echo "Error getting stock: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get product details
     */
    public function getProduct(int $productId): array
    {
        try {
            return $this->client->products()->get($productId);
        } catch (IslebooksAPIException $e) {
            echo "Error getting product: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * List all products with optional filters
     */
    public function listProducts(array $filters = []): array
    {
        try {
            return $this->client->products()->list($filters);
        } catch (IslebooksAPIException $e) {
            echo "Error listing products: {$e->getMessage()}\n";
            throw $e;
        }
    }
}

// Example usage:
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $addStock = new AddStock();

        // Add USDT stock with cost per unit 19.41
        $result = $addStock->addStockToUSDTSwap(
            quantity: 1,
            costPerUnit: 19.41,
            options: [
                'notes' => 'USDT stock addition - automated',
                'reference_no' => 'USDT-STOCK-' . date('YmdHis')
            ]
        );

        echo "\nTransaction completed successfully!\n";

        // Show current stock levels
        echo "\nChecking current stock levels...\n";
        $stock = $addStock->getProductStock(373); // USDT product ID
        if (isset($stock['data']['current_stock'])) {
            echo "Current USDT stock: {$stock['data']['current_stock']}\n";
            echo "Total purchases: {$stock['data']['total_purchase']}\n";
        } else {
            echo "Could not retrieve stock levels.\n";
            print_r($stock);
        }

    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}