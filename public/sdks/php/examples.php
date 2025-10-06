<?php

/**
 * IsleBooks PHP SDK - Usage Examples
 * 
 * This file demonstrates how to use the IsleBooks PHP SDK for common operations.
 * Make sure to include the SDK file and set your API credentials.
 */

require_once 'IslebooksAPI.php';

use IslebooksAPI\IslebooksClient;
use IslebooksAPI\IslebooksAPIException;

// Initialize the client
$baseUrl = 'https://pos.islebooks.mv'; // Replace with your IsleBooks URL
$apiKey = 'YOUR_API_KEY_HERE'; // Replace with your actual API key

$client = new IslebooksClient($baseUrl, $apiKey, [
    'timeout' => 30 // Optional: Set request timeout
]);

try {
    
    echo "=== IsleBooks PHP SDK Examples ===\n\n";
    
    // 1. Test API Connection
    echo "1. Testing API Connection...\n";
    $status = $client->getStatus();
    echo "   ✓ API Status: {$status['message']}\n";
    echo "   ✓ Version: {$status['version']}\n\n";
    
    // 2. Get Business Information
    echo "2. Getting Business Information...\n";
    $business = $client->business()->get();
    echo "   ✓ Business: {$business['data']['name']}\n";
    echo "   ✓ Currency: {$business['data']['currency']['code']}\n\n";
    
    // 3. List Products
    echo "3. Listing Products...\n";
    $products = $client->products()->list([
        'per_page' => 5,
        'is_active' => true
    ]);
    echo "   ✓ Found {$products['meta']['total']} products\n";
    foreach ($products['data'] as $product) {
        echo "   - {$product['name']} (SKU: {$product['sku']})\n";
    }
    echo "\n";
    
    // 4. Create a New Product
    echo "4. Creating New Product...\n";
    $newProduct = $client->products()->create([
        'name' => 'SDK Test Product',
        'sku' => 'SDK-TEST-' . time(),
        'type' => 'single',
        'unit_id' => 1,
        'category_id' => 1,
        'sub_category_id' => null,
        'brand_id' => null,
        'tax_id' => null,
        'barcode_type' => 'C128',
        'alert_quantity' => 10,
        'product_custom_field1' => 'Created via PHP SDK',
        'product_description' => 'This product was created using the IsleBooks PHP SDK',
        'single_dpp' => 50.00,
        'single_dpp_inc_tax' => 55.00,
        'single_dsp' => 75.00,
        'single_dsp_inc_tax' => 82.50
    ]);
    $productId = $newProduct['data']['id'];
    echo "   ✓ Created product with ID: {$productId}\n\n";
    
    // 5. Get Product Details
    echo "5. Getting Product Details...\n";
    $productDetails = $client->products()->get($productId);
    echo "   ✓ Product: {$productDetails['data']['name']}\n";
    echo "   ✓ Current Stock: {$productDetails['data']['current_stock']}\n\n";
    
    // 6. List Customers
    echo "6. Listing Customers...\n";
    $customers = $client->contacts()->list([
        'type' => 'customer',
        'per_page' => 5
    ]);
    echo "   ✓ Found {$customers['meta']['total']} customers\n";
    foreach ($customers['data'] as $customer) {
        echo "   - {$customer['name']} ({$customer['mobile']})\n";
    }
    echo "\n";
    
    // 7. Create a New Customer
    echo "7. Creating New Customer...\n";
    $newCustomer = $client->contacts()->create([
        'type' => 'customer',
        'name' => 'SDK Test Customer',
        'mobile' => '+960123' . rand(1000, 9999),
        'email' => 'sdk.test.' . time() . '@example.com',
        'city' => 'Male',
        'country' => 'Maldives'
    ]);
    $customerId = $newCustomer['data']['id'];
    echo "   ✓ Created customer with ID: {$customerId}\n\n";
    
    // 8. Create a Sale
    echo "8. Creating Sale Transaction...\n";
    $sale = $client->sales()->create([
        'contact_id' => $customerId,
        'transaction_date' => date('Y-m-d H:i:s'),
        'invoice_no' => 'SDK-' . time(),
        'status' => 'final',
        'payment_status' => 'paid',
        'final_total' => 82.50,
        'tax_rate_id' => null,
        'discount_type' => 'fixed',
        'discount_amount' => 0,
        'shipping_charges' => 0,
        'additional_notes' => 'Sale created via PHP SDK',
        'products' => [
            [
                'product_id' => $productId,
                'variation_id' => $productDetails['data']['variations'][0]['id'],
                'quantity' => 1,
                'unit_price' => 75.00,
                'unit_price_inc_tax' => 82.50
            ]
        ],
        'payment' => [
            [
                'amount' => 82.50,
                'method' => 'cash',
                'paid_on' => date('Y-m-d H:i:s')
            ]
        ]
    ]);
    $saleId = $sale['data']['id'];
    echo "   ✓ Created sale with ID: {$saleId}\n";
    echo "   ✓ Invoice No: {$sale['data']['invoice_no']}\n\n";
    
    // 9. Get Dashboard Report
    echo "9. Getting Dashboard Metrics...\n";
    $dashboard = $client->reports()->getDashboard([
        'date_from' => date('Y-m-01'), // First day of current month
        'date_to' => date('Y-m-d') // Today
    ]);
    echo "   ✓ Total Sales: {$dashboard['data']['sales']['total_sales']}\n";
    echo "   ✓ Total Profit: {$dashboard['data']['profit']['total_profit']}\n";
    echo "   ✓ Total Customers: {$dashboard['data']['customers']['total_customers']}\n\n";
    
    // 10. Get Sales Analytics
    echo "10. Getting Sales Analytics...\n";
    $analytics = $client->reports()->getSalesAnalytics([
        'period' => 'this_month'
    ]);
    echo "   ✓ Sales Trend Data Points: " . count($analytics['data']['sales_trend']) . "\n";
    echo "   ✓ Top Products: " . count($analytics['data']['top_products']) . "\n\n";
    
    echo "=== All Examples Completed Successfully! ===\n";
    
} catch (IslebooksAPIException $e) {
    echo "API Error: {$e->getMessage()}\n";
    echo "HTTP Code: {$e->getCode()}\n";
    echo "API Error Code: {$e->getApiError()}\n";
    
    if (!empty($e->getResponse())) {
        echo "Response Details:\n";
        print_r($e->getResponse());
    }
    
} catch (Exception $e) {
    echo "General Error: {$e->getMessage()}\n";
}

/**
 * Additional Examples
 */

// Example: Bulk Product Operations
function bulkProductExample($client) {
    try {
        // Create multiple products at once
        $bulkProducts = [
            [
                'name' => 'Bulk Product 1',
                'sku' => 'BULK-1-' . time(),
                'type' => 'single',
                'unit_id' => 1,
                'category_id' => 1,
                'single_dpp' => 25.00,
                'single_dsp' => 35.00
            ],
            [
                'name' => 'Bulk Product 2',
                'sku' => 'BULK-2-' . time(),
                'type' => 'single',
                'unit_id' => 1,
                'category_id' => 1,
                'single_dpp' => 30.00,
                'single_dsp' => 42.00
            ]
        ];
        
        $result = $client->products()->bulkCreate($bulkProducts);
        echo "Created {$result['data']['created']} products in bulk\n";
        
    } catch (IslebooksAPIException $e) {
        echo "Bulk operation failed: {$e->getMessage()}\n";
    }
}

// Example: Customer Transaction History
function customerHistoryExample($client, $customerId) {
    try {
        // Get customer's transactions
        $transactions = $client->contacts()->getTransactions($customerId, [
            'per_page' => 10
        ]);
        
        echo "Customer has {$transactions['meta']['total']} transactions:\n";
        foreach ($transactions['data'] as $transaction) {
            echo "- Invoice: {$transaction['invoice_no']}, Amount: {$transaction['final_total']}\n";
        }
        
        // Get customer balance
        $balance = $client->contacts()->getBalance($customerId);
        echo "Customer Balance: {$balance['data']['balance']}\n";
        
    } catch (IslebooksAPIException $e) {
        echo "Error getting customer history: {$e->getMessage()}\n";
    }
}

// Example: Stock Management
function stockManagementExample($client, $productId) {
    try {
        // Get current stock levels
        $stock = $client->products()->getStock($productId);
        echo "Current stock levels:\n";
        
        foreach ($stock['data'] as $location) {
            echo "- Location: {$location['location_name']}, Stock: {$location['qty_available']}\n";
        }
        
        // Get product variations
        $variations = $client->products()->getVariations($productId);
        echo "Product has " . count($variations['data']) . " variations\n";
        
    } catch (IslebooksAPIException $e) {
        echo "Error managing stock: {$e->getMessage()}\n";
    }
}

// Example: Advanced Filtering
function advancedFilteringExample($client) {
    try {
        // Get products with advanced filters
        $filteredProducts = $client->products()->list([
            'category_id' => 1,
            'is_active' => true,
            'has_stock' => true,
            'search' => 'test',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 20,
            'page' => 1
        ]);
        
        echo "Filtered products: {$filteredProducts['meta']['total']} found\n";
        
        // Get sales with date filtering
        $salesThisWeek = $client->sales()->list([
            'date_from' => date('Y-m-d', strtotime('monday this week')),
            'date_to' => date('Y-m-d'),
            'status' => 'final',
            'payment_status' => 'paid'
        ]);
        
        echo "Sales this week: {$salesThisWeek['meta']['total']}\n";
        
    } catch (IslebooksAPIException $e) {
        echo "Error with advanced filtering: {$e->getMessage()}\n";
    }
}

// Uncomment to run additional examples:
// bulkProductExample($client);
// customerHistoryExample($client, $customerId);
// stockManagementExample($client, $productId);
// advancedFilteringExample($client);