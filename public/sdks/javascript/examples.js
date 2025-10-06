/**
 * IsleBooks JavaScript SDK - Usage Examples
 * 
 * This file demonstrates how to use the IsleBooks JavaScript SDK for common operations.
 * Works in both browser and Node.js environments.
 */

// For Node.js, uncomment the following line:
// const { IslebooksAPI, IslebooksAPIError } = require('./islebooks-api');

// Initialize the client
const baseUrl = 'https://pos.islebooks.mv'; // Replace with your IsleBooks URL
const apiKey = 'YOUR_API_KEY_HERE'; // Replace with your actual API key

const client = new IslebooksAPI(baseUrl, apiKey, {
    timeout: 30000, // 30 seconds timeout
    retries: 3 // Retry failed requests 3 times
});

/**
 * Main examples function
 */
async function runExamples() {
    try {
        console.log('=== IsleBooks JavaScript SDK Examples ===\n');
        
        // 1. Test API Connection
        console.log('1. Testing API Connection...');
        const status = await client.getStatus();
        console.log(`   ✓ API Status: ${status.message}`);
        console.log(`   ✓ Version: ${status.version}\n`);
        
        // 2. Get Business Information
        console.log('2. Getting Business Information...');
        const business = await client.business.get();
        console.log(`   ✓ Business: ${business.data.name}`);
        console.log(`   ✓ Currency: ${business.data.currency.code}\n`);
        
        // 3. List Products with Pagination
        console.log('3. Listing Products...');
        const products = await client.products.list({
            per_page: 5,
            is_active: true,
            page: 1
        });
        console.log(`   ✓ Found ${products.meta.total} products`);
        products.data.forEach(product => {
            console.log(`   - ${product.name} (SKU: ${product.sku})`);
        });
        console.log('');
        
        // 4. Create a New Product
        console.log('4. Creating New Product...');
        const newProduct = await client.products.create({
            name: 'JavaScript SDK Test Product',
            sku: `JS-SDK-TEST-${Date.now()}`,
            type: 'single',
            unit_id: 1,
            category_id: 1,
            sub_category_id: null,
            brand_id: null,
            tax_id: null,
            barcode_type: 'C128',
            alert_quantity: 10,
            product_custom_field1: 'Created via JavaScript SDK',
            product_description: 'This product was created using the IsleBooks JavaScript SDK',
            single_dpp: 45.00,
            single_dpp_inc_tax: 49.50,
            single_dsp: 65.00,
            single_dsp_inc_tax: 71.50
        });
        const productId = newProduct.data.id;
        console.log(`   ✓ Created product with ID: ${productId}\n`);
        
        // 5. Get Product Details and Stock
        console.log('5. Getting Product Details and Stock...');
        const [productDetails, stockInfo] = await Promise.all([
            client.products.get(productId),
            client.products.getStock(productId)
        ]);
        console.log(`   ✓ Product: ${productDetails.data.name}`);
        console.log(`   ✓ Current Stock: ${productDetails.data.current_stock}`);
        console.log(`   ✓ Stock Locations: ${stockInfo.data.length}\n`);
        
        // 6. List and Create Customer
        console.log('6. Managing Customers...');
        const customers = await client.contacts.list({
            type: 'customer',
            per_page: 3
        });
        console.log(`   ✓ Found ${customers.meta.total} customers`);
        
        const newCustomer = await client.contacts.create({
            type: 'customer',
            name: 'JS SDK Test Customer',
            mobile: `+960123${Math.floor(Math.random() * 9000) + 1000}`,
            email: `js.sdk.test.${Date.now()}@example.com`,
            city: 'Hulhumale',
            country: 'Maldives',
            address_line_1: '123 SDK Street'
        });
        const customerId = newCustomer.data.id;
        console.log(`   ✓ Created customer with ID: ${customerId}\n`);
        
        // 7. Create a Sale Transaction
        console.log('7. Creating Sale Transaction...');
        const sale = await client.sales.create({
            contact_id: customerId,
            transaction_date: new Date().toISOString().slice(0, 19).replace('T', ' '),
            invoice_no: `JS-SDK-${Date.now()}`,
            status: 'final',
            payment_status: 'paid',
            final_total: 71.50,
            tax_rate_id: null,
            discount_type: 'fixed',
            discount_amount: 0,
            shipping_charges: 0,
            additional_notes: 'Sale created via JavaScript SDK',
            products: [
                {
                    product_id: productId,
                    variation_id: productDetails.data.variations[0].id,
                    quantity: 1,
                    unit_price: 65.00,
                    unit_price_inc_tax: 71.50
                }
            ],
            payment: [
                {
                    amount: 71.50,
                    method: 'cash',
                    paid_on: new Date().toISOString().slice(0, 19).replace('T', ' ')
                }
            ]
        });
        const saleId = sale.data.id;
        console.log(`   ✓ Created sale with ID: ${saleId}`);
        console.log(`   ✓ Invoice No: ${sale.data.invoice_no}\n`);
        
        // 8. Get Customer's Transaction History and Balance
        console.log('8. Getting Customer History...');
        const [transactions, balance] = await Promise.all([
            client.contacts.getTransactions(customerId, { per_page: 5 }),
            client.contacts.getBalance(customerId)
        ]);
        console.log(`   ✓ Customer has ${transactions.meta.total} transactions`);
        console.log(`   ✓ Customer balance: ${balance.data.balance}\n`);
        
        // 9. Get Dashboard Metrics
        console.log('9. Getting Dashboard Metrics...');
        const dashboard = await client.reports.getDashboard({
            date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
            date_to: new Date().toISOString().slice(0, 10)
        });
        console.log(`   ✓ Total Sales: ${dashboard.data.sales.total_sales}`);
        console.log(`   ✓ Total Profit: ${dashboard.data.profit.total_profit}`);
        console.log(`   ✓ Total Customers: ${dashboard.data.customers.total_customers}\n`);
        
        // 10. Get Sales Analytics
        console.log('10. Getting Sales Analytics...');
        const analytics = await client.reports.getSalesAnalytics({
            period: 'this_month'
        });
        console.log(`   ✓ Sales Trend Data Points: ${analytics.data.sales_trend.length}`);
        console.log(`   ✓ Top Products: ${analytics.data.top_products.length}\n`);
        
        console.log('=== All Examples Completed Successfully! ===');
        
    } catch (error) {
        if (error instanceof IslebooksAPIError) {
            console.error(`API Error: ${error.message}`);
            console.error(`HTTP Status: ${error.status}`);
            console.error(`API Error Code: ${error.apiError}`);
            
            if (error.response && Object.keys(error.response).length > 0) {
                console.error('Response Details:', error.response);
            }
        } else {
            console.error(`General Error: ${error.message}`);
            console.error(error.stack);
        }
    }
}

/**
 * Additional Examples
 */

// Example: Bulk Operations
async function bulkOperationsExample() {
    try {
        console.log('\n=== Bulk Operations Example ===');
        
        // Create multiple products at once
        const bulkProducts = [
            {
                name: 'Bulk JS Product 1',
                sku: `BULK-JS-1-${Date.now()}`,
                type: 'single',
                unit_id: 1,
                category_id: 1,
                single_dpp: 20.00,
                single_dsp: 30.00
            },
            {
                name: 'Bulk JS Product 2',
                sku: `BULK-JS-2-${Date.now()}`,
                type: 'single',
                unit_id: 1,
                category_id: 1,
                single_dpp: 25.00,
                single_dsp: 35.00
            }
        ];
        
        const result = await client.products.bulkCreate(bulkProducts);
        console.log(`✓ Created ${result.data.created} products in bulk`);
        
    } catch (error) {
        console.error('Bulk operations failed:', error.message);
    }
}

// Example: Advanced Filtering and Search
async function advancedFilteringExample() {
    try {
        console.log('\n=== Advanced Filtering Example ===');
        
        // Advanced product search
        const filteredProducts = await client.products.list({
            search: 'test',
            category_id: 1,
            is_active: true,
            has_stock: true,
            sort_by: 'name',
            sort_direction: 'asc',
            per_page: 10,
            page: 1
        });
        console.log(`✓ Found ${filteredProducts.meta.total} products matching filters`);
        
        // Get recent sales with filters
        const recentSales = await client.sales.getRecent({
            limit: 10,
            status: 'final'
        });
        console.log(`✓ Found ${recentSales.data.length} recent sales`);
        
        // Get trending products
        const trending = await client.reports.getTrendingProducts({
            period: 'last_30_days',
            limit: 5
        });
        console.log(`✓ Top ${trending.data.length} trending products retrieved`);
        
    } catch (error) {
        console.error('Advanced filtering failed:', error.message);
    }
}

// Example: Error Handling and Retry Logic
async function errorHandlingExample() {
    try {
        console.log('\n=== Error Handling Example ===');
        
        // Try to get a non-existent product (will throw 404 error)
        try {
            await client.products.get(999999);
        } catch (error) {
            if (error instanceof IslebooksAPIError && error.status === 404) {
                console.log('✓ Properly handled 404 error for non-existent product');
            }
        }
        
        // Example of handling validation errors
        try {
            await client.products.create({
                name: '', // Invalid empty name
                type: 'single'
                // Missing required fields
            });
        } catch (error) {
            if (error instanceof IslebooksAPIError && error.status === 422) {
                console.log('✓ Properly handled validation error');
                console.log('   Validation errors:', error.response.errors);
            }
        }
        
    } catch (error) {
        console.error('Error handling example failed:', error.message);
    }
}

// Example: Concurrent Operations with Promise.all
async function concurrentOperationsExample() {
    try {
        console.log('\n=== Concurrent Operations Example ===');
        
        // Execute multiple API calls concurrently
        const startTime = Date.now();
        const [products, customers, recentSales, dashboard] = await Promise.all([
            client.products.list({ per_page: 5 }),
            client.contacts.list({ type: 'customer', per_page: 5 }),
            client.sales.getRecent({ limit: 5 }),
            client.reports.getDashboard()
        ]);
        const endTime = Date.now();
        
        console.log(`✓ Completed 4 concurrent API calls in ${endTime - startTime}ms`);
        console.log(`   - Products: ${products.meta.total}`);
        console.log(`   - Customers: ${customers.meta.total}`);
        console.log(`   - Recent Sales: ${recentSales.data.length}`);
        console.log(`   - Dashboard loaded successfully`);
        
    } catch (error) {
        console.error('Concurrent operations failed:', error.message);
    }
}

// Example: Real-time Data Updates (polling pattern)
async function realTimeUpdatesExample() {
    console.log('\n=== Real-time Updates Example (5 iterations) ===');
    
    let iteration = 0;
    const maxIterations = 5;
    
    const interval = setInterval(async () => {
        try {
            iteration++;
            const dashboard = await client.reports.getDashboard();
            console.log(`Update ${iteration}: Total Sales = ${dashboard.data.sales.total_sales}`);
            
            if (iteration >= maxIterations) {
                clearInterval(interval);
                console.log('✓ Real-time updates example completed');
            }
        } catch (error) {
            console.error('Real-time update failed:', error.message);
            clearInterval(interval);
        }
    }, 2000); // Update every 2 seconds
}

// Run main examples
if (typeof window !== 'undefined') {
    // Browser environment - run examples when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runExamples);
    } else {
        runExamples();
    }
} else {
    // Node.js environment - run examples immediately
    runExamples().then(() => {
        console.log('\nRunning additional examples...');
        return Promise.all([
            bulkOperationsExample(),
            advancedFilteringExample(),
            errorHandlingExample(),
            concurrentOperationsExample()
        ]);
    }).then(() => {
        console.log('\nStarting real-time updates example...');
        return realTimeUpdatesExample();
    }).catch(console.error);
}