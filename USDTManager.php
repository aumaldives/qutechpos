<?php

require_once 'public/sdks/php/IslebooksAPI.php';

use IslebooksAPI\IslebooksClient;
use IslebooksAPI\IslebooksAPIException;

/**
 * USDT Manager Class
 *
 * Complete solution for USDT stock management and sales
 * Handles both adding stock and creating sales for USDT
 */
class USDTManager
{
    private IslebooksClient $client;
    private int $usdtSwapLocationId = 503;
    private int $usdtProductId = 373;
    private int $defaultSupplierId = 16;
    private int $defaultCustomerId = 13; // Cash customer

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
     * Add USDT stock to USDT SWAP location
     *
     * @param float $quantity Quantity to add
     * @param float $costPerUnit Cost per unit
     * @param array $options Additional options
     * @return array API response
     */
    public function addStock(float $quantity, float $costPerUnit, array $options = []): array
    {
        $requestData = [
            'quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'supplier_id' => $options['supplier_id'] ?? $this->defaultSupplierId,
            'ref_no' => $options['reference_no'] ?? 'USDT-STOCK-' . time(),
            'notes' => $options['notes'] ?? 'USDT stock added via USDTManager'
        ];

        try {
            echo "=== Adding USDT Stock ===\n";
            echo "Quantity: {$quantity}\n";
            echo "Cost per unit: {$costPerUnit}\n";
            echo "Total cost: " . ($quantity * $costPerUnit) . "\n";
            echo "Location: USDT SWAP (ID: {$this->usdtSwapLocationId})\n\n";

            $response = $this->client->request('POST', '/connector/usdt-stock', $requestData);

            echo "✓ USDT stock added successfully!\n";
            if (isset($response['data']['id'])) {
                echo "Purchase ID: {$response['data']['id']}\n";
            }
            if (isset($response['data']['ref_no'])) {
                echo "Reference: {$response['data']['ref_no']}\n";
            }
            echo "\n";

            return $response;

        } catch (IslebooksAPIException $e) {
            echo "✗ Error adding USDT stock: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Create USDT sale transaction using POS endpoint
     *
     * @param float $quantity Quantity to sell
     * @param float $pricePerUnit Selling price per unit
     * @param array $options Additional options
     * @return array API response
     */
    public function createSale(float $quantity, float $pricePerUnit, array $options = []): array
    {
        $totalAmount = $quantity * $pricePerUnit;

        $saleData = [
            'location_id' => $this->usdtSwapLocationId,
            'contact_id' => $options['customer_id'] ?? $this->defaultCustomerId,
            'products' => [
                [
                    'variation_id' => $this->usdtProductId, // Use variation_id as required
                    'quantity' => $quantity,
                    'unit_price' => $pricePerUnit
                ]
            ],
            'payment' => [
                [
                    'method' => $options['payment_method'] ?? 'cash',
                    'amount' => $totalAmount
                ]
            ],
            'discount_amount' => $options['discount_amount'] ?? 0,
            'discount_type' => 'fixed',
            'shipping_charges' => $options['shipping_charges'] ?? 0,
            'sale_note' => $options['notes'] ?? 'USDT sale via USDTManager'
        ];

        try {
            echo "=== Creating USDT Sale ===\n";
            echo "Quantity: {$quantity}\n";
            echo "Price per unit: {$pricePerUnit}\n";
            echo "Total amount: {$totalAmount}\n";
            echo "Customer ID: " . ($options['customer_id'] ?? $this->defaultCustomerId) . "\n\n";

            // Use the POS sale endpoint
            $response = $this->client->request('POST', '/pos/sale', $saleData);

            echo "✓ USDT sale created successfully!\n";
            if (isset($response['data']['id'])) {
                echo "Sale ID: {$response['data']['id']}\n";
            }
            if (isset($response['data']['invoice_no'])) {
                echo "Invoice: {$response['data']['invoice_no']}\n";
            }
            echo "\n";

            return $response;

        } catch (IslebooksAPIException $e) {
            echo "✗ Error creating USDT sale: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get current USDT stock levels
     *
     * @return array Stock information
     */
    public function getStockLevels(): array
    {
        try {
            $response = $this->client->products()->getStock($this->usdtProductId);

            echo "=== USDT Stock Levels ===\n";
            if (isset($response['data']['current_stock'])) {
                echo "Current Stock: {$response['data']['current_stock']}\n";
                echo "Total Purchases: {$response['data']['total_purchase']}\n";
                echo "Total Sold: {$response['data']['total_sold']}\n";
            }
            echo "\n";

            return $response;

        } catch (IslebooksAPIException $e) {
            echo "✗ Error getting stock levels: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get business locations
     *
     * @return array Locations
     */
    public function getLocations(): array
    {
        try {
            return $this->client->business()->getLocations();
        } catch (IslebooksAPIException $e) {
            echo "✗ Error getting locations: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Get contacts (customers/suppliers)
     *
     * @param string $type Type of contact ('customer', 'supplier', or 'both')
     * @return array Contacts
     */
    public function getContacts(string $type = 'both'): array
    {
        try {
            $filters = [];
            if ($type !== 'both') {
                $filters['type'] = $type;
            }
            return $this->client->contacts()->list($filters);
        } catch (IslebooksAPIException $e) {
            echo "✗ Error getting contacts: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Complete workflow: Add stock then create sale
     *
     * @param float $stockQuantity Quantity to add to stock
     * @param float $stockCost Cost per unit for stock
     * @param float $saleQuantity Quantity to sell
     * @param float $salePrice Price per unit for sale
     * @param array $options Additional options
     * @return array Results of both operations
     */
    public function addStockAndSell(
        float $stockQuantity,
        float $stockCost,
        float $saleQuantity,
        float $salePrice,
        array $options = []
    ): array {
        $results = [];

        try {
            // Step 1: Add stock
            echo "STEP 1: Adding stock...\n";
            $results['stock'] = $this->addStock($stockQuantity, $stockCost, $options);

            // Step 2: Create sale
            echo "STEP 2: Creating sale...\n";
            $results['sale'] = $this->createSale($saleQuantity, $salePrice, $options);

            // Step 3: Show final stock levels
            echo "STEP 3: Final stock levels...\n";
            $results['final_stock'] = $this->getStockLevels();

            echo "=== Workflow Completed Successfully! ===\n";
            echo "Stock Added: {$stockQuantity} USDT at {$stockCost} each\n";
            echo "Sale Created: {$saleQuantity} USDT at {$salePrice} each\n";
            echo "Profit: " . (($salePrice - $stockCost) * $saleQuantity) . "\n";

            return $results;

        } catch (Exception $e) {
            echo "✗ Workflow failed: {$e->getMessage()}\n";
            throw $e;
        }
    }
}

// Example usage
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $usdtManager = new USDTManager();

        echo "=== USDT Manager Demo ===\n\n";

        // Example 1: Just add stock
        echo "EXAMPLE 1: Adding 1 USDT at cost 19.41\n";
        $stockResult = $usdtManager->addStock(1, 19.41, [
            'notes' => 'Demo stock addition',
            'reference_no' => 'DEMO-STOCK-' . date('YmdHis')
        ]);

        // Example 2: Create a sale (comment out if no stock available)
        /*
        echo "EXAMPLE 2: Selling 0.5 USDT at price 20.00\n";
        $saleResult = $usdtManager->createSale(0.5, 20.00, [
            'notes' => 'Demo sale transaction',
            'invoice_no' => 'DEMO-SALE-' . date('YmdHis')
        ]);
        */

        // Example 3: Complete workflow (add stock + sell)
        /*
        echo "EXAMPLE 3: Complete workflow\n";
        $workflowResult = $usdtManager->addStockAndSell(
            stockQuantity: 2,
            stockCost: 19.41,
            saleQuantity: 1,
            salePrice: 20.50,
            options: [
                'notes' => 'Complete demo workflow'
            ]
        );
        */

        // Show current stock levels
        $usdtManager->getStockLevels();

    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}