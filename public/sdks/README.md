# IsleBooks POS API SDKs

Official Software Development Kits (SDKs) for the IsleBooks POS API, providing easy integration in multiple programming languages.

## Available SDKs

### 🐘 PHP SDK
**File**: `php/IslebooksAPI.php`  
**Requirements**: PHP 7.4+ with cURL extension  
**Features**:
- Object-oriented design with method chaining
- Comprehensive error handling with custom exceptions
- Full coverage of all API endpoints
- Built-in retry logic and timeout handling
- Type hints and detailed documentation

**Quick Start**:
```php
<?php
require_once 'php/IslebooksAPI.php';

use IslebooksAPI\IslebooksClient;

$client = new IslebooksClient('https://yourstore.islebooks.mv', 'your-api-key');

// Get all products
$products = $client->products()->list(['per_page' => 10]);

// Create a new customer
$customer = $client->contacts()->create([
    'type' => 'customer',
    'name' => 'John Doe',
    'mobile' => '+960123456789'
]);
```

### 🟨 JavaScript SDK
**File**: `javascript/islebooks-api.js`  
**Requirements**: Modern browser or Node.js 14+  
**Features**:
- Promise-based async/await API
- Works in both browser and Node.js
- Automatic retry with exponential backoff
- Request timeout and abort signal support
- TypeScript-friendly structure

**Quick Start**:
```javascript
// Browser
const client = new IslebooksAPI('https://yourstore.islebooks.mv', 'your-api-key');

// Node.js
const { IslebooksAPI } = require('./javascript/islebooks-api');
const client = new IslebooksAPI('https://yourstore.islebooks.mv', 'your-api-key');

// Get dashboard metrics
const dashboard = await client.reports.getDashboard();

// Create a new sale
const sale = await client.sales.create({
    contact_id: 123,
    products: [{ product_id: 456, quantity: 2 }],
    payment: [{ amount: 100.00, method: 'cash' }]
});
```

### 🐍 Python SDK
**File**: `python/islebooks_api.py`  
**Requirements**: Python 3.7+ with requests library  
**Features**:
- Clean Pythonic API with type hints
- Comprehensive error handling
- Session-based HTTP client for connection reuse
- Built-in retry mechanisms
- Utility functions for common operations

**Quick Start**:
```python
from islebooks_api import IslebooksAPI

client = IslebooksAPI('https://yourstore.islebooks.mv', 'your-api-key')

# Test connection
status = client.get_status()
print(f"API Status: {status['message']}")

# Get business information
business = client.business.get()

# List products with filtering
products = client.products.list(
    is_active=True,
    category_id=1,
    per_page=20
)
```

## Common Features Across All SDKs

### ✅ Complete API Coverage
- **Products API**: Full CRUD operations, variations, stock management, bulk operations
- **Contacts API**: Customer and supplier management with transaction history
- **Transactions API**: Sales, purchases, payments, and transaction management
- **Reports API**: Dashboard metrics, sales analytics, profit/loss, stock reports
- **Business API**: Business information, locations, and settings

### ✅ Advanced Features
- **Error Handling**: Comprehensive exception handling with detailed error information
- **Retry Logic**: Automatic retry for transient failures with exponential backoff
- **Rate Limiting**: Respect API rate limits with proper headers
- **Pagination**: Easy handling of paginated results
- **Filtering**: Advanced filtering and sorting capabilities
- **Bulk Operations**: Efficient batch processing for large datasets

### ✅ Security & Performance
- **Secure Authentication**: API key-based authentication with proper headers
- **HTTPS Enforcement**: All requests use secure HTTPS connections
- **Connection Reuse**: Efficient HTTP connection management
- **Timeout Handling**: Configurable request timeouts
- **Memory Efficient**: Streaming for large data sets where applicable

## Installation & Setup

### PHP SDK
```bash
# Download the SDK
curl -O https://yourstore.islebooks.mv/sdks/php/IslebooksAPI.php

# Include in your project
require_once 'IslebooksAPI.php';
```

### JavaScript SDK
```bash
# For Node.js projects
npm install node-fetch  # If using Node.js < 18

# Download the SDK
curl -O https://yourstore.islebooks.mv/sdks/javascript/islebooks-api.js

# For browser usage
<script src="islebooks-api.js"></script>
```

### Python SDK
```bash
# Install dependencies
pip install requests

# Download the SDK
curl -O https://yourstore.islebooks.mv/sdks/python/islebooks_api.py

# Import in your project
from islebooks_api import IslebooksAPI
```

## Authentication Setup

1. **Get Your API Key**:
   - Log into your IsleBooks dashboard
   - Navigate to Settings → API Keys
   - Create a new API key with required permissions

2. **Initialize SDK**:
   ```php
   // PHP
   $client = new IslebooksClient('https://yourstore.islebooks.mv', 'your-api-key');
   ```
   ```javascript
   // JavaScript
   const client = new IslebooksAPI('https://yourstore.islebooks.mv', 'your-api-key');
   ```
   ```python
   # Python
   client = IslebooksAPI('https://yourstore.islebooks.mv', 'your-api-key')
   ```

## Usage Examples

Each SDK comes with comprehensive examples:

- **PHP**: `php/examples.php` - Complete walkthrough with 10+ examples
- **JavaScript**: `javascript/examples.js` - Browser and Node.js examples
- **Python**: `python/examples.py` - Command-line examples with utilities

### Example Operations

```php
// PHP - Create a complete sale transaction
$sale = $client->sales()->create([
    'contact_id' => 123,
    'invoice_no' => 'INV-001',
    'status' => 'final',
    'products' => [
        [
            'product_id' => 456,
            'quantity' => 2,
            'unit_price' => 50.00
        ]
    ],
    'payment' => [
        [
            'amount' => 100.00,
            'method' => 'cash'
        ]
    ]
]);
```

```javascript
// JavaScript - Get sales analytics
const analytics = await client.reports.getSalesAnalytics({
    period: 'this_month',
    group_by: 'day'
});

console.log(`Total sales: ${analytics.data.total_sales}`);
```

```python
# Python - Bulk product operations
products_data = [
    {'name': 'Product 1', 'sku': 'SKU001', 'single_dsp': 25.00},
    {'name': 'Product 2', 'sku': 'SKU002', 'single_dsp': 30.00}
]

result = client.products.bulk_create(products_data)
print(f"Created {result['data']['created']} products")
```

## Error Handling

All SDKs provide comprehensive error handling:

```php
// PHP
try {
    $product = $client->products()->get(123);
} catch (IslebooksAPIException $e) {
    echo "API Error: " . $e->getMessage();
    echo "Status: " . $e->getCode();
}
```

```javascript
// JavaScript
try {
    const product = await client.products.get(123);
} catch (error) {
    if (error instanceof IslebooksAPIError) {
        console.log(`API Error: ${error.message}`);
        console.log(`Status: ${error.status}`);
    }
}
```

```python
# Python
try:
    product = client.products.get(123)
except IslebooksAPIError as e:
    print(f"API Error: {e}")
    print(f"Status: {e.status_code}")
```

## Advanced Usage

### Pagination Handling
```python
# Python example - Process all products
page = 1
while True:
    products = client.products.list(page=page, per_page=100)
    
    for product in products['data']:
        # Process each product
        print(f"Processing: {product['name']}")
    
    # Check if we have more pages
    if page >= products['meta']['last_page']:
        break
    page += 1
```

### Custom Timeouts and Retries
```javascript
// JavaScript - Custom configuration
const client = new IslebooksAPI('https://yourstore.islebooks.mv', 'api-key', {
    timeout: 45000,  // 45 second timeout
    retries: 5       // Retry up to 5 times
});
```

### Concurrent Operations
```php
// PHP - Multiple operations (would require async library like ReactPHP)
// For now, sequential operations are supported
$products = $client->products()->list();
$customers = $client->contacts()->list(['type' => 'customer']);
$dashboard = $client->reports()->getDashboard();
```

## Rate Limiting

All SDKs respect API rate limits:
- Automatic retry on 429 (Too Many Requests) responses
- Exponential backoff strategy
- Rate limit headers parsing
- Configurable retry behavior

## Support & Documentation

- **Interactive Documentation**: https://yourstore.islebooks.mv/api-docs/interactive
- **API Playground**: https://yourstore.islebooks.mv/api-docs/playground
- **OpenAPI Specification**: https://yourstore.islebooks.mv/api/openapi.yaml
- **Code Examples**: Each SDK includes comprehensive examples

## SDK Versions

- **PHP SDK**: v1.0.0
- **JavaScript SDK**: v1.0.0  
- **Python SDK**: v1.0.0

All SDKs are maintained and updated alongside the API. Check for updates regularly.

## License

MIT License - See individual SDK files for complete license text.

## Contributing

To contribute improvements or report issues with the SDKs:
1. Test your changes against the live API
2. Ensure examples work correctly
3. Update documentation as needed
4. Follow the coding standards of each language