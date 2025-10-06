# QuickBooks Sync Module

A comprehensive QuickBooks integration module for IsleBooks POS that provides location-specific synchronization of sales, inventory, and financial data with QuickBooks Online.

## Features

### ✅ Core Functionality
- **Location-Specific Integration**: Each business location can connect to its own QuickBooks company
- **OAuth2 Authentication**: Secure authorization flow with automatic token refresh
- **Bi-directional Synchronization**: Sync data from POS to QuickBooks and vice versa
- **Real-time & Scheduled Sync**: Manual sync operations with optional automatic scheduling

### ✅ Data Synchronization
- **Customers**: Sync customer information and contact details
- **Suppliers/Vendors**: Sync supplier information for purchase management
- **Products/Items**: Sync product catalog with pricing and descriptions
- **Sales Invoices**: Export POS sales as QuickBooks invoices
- **Payments**: Sync payment records linked to invoices
- **Purchase Bills**: Export purchase transactions as QuickBooks bills
- **Inventory Levels**: Keep stock quantities synchronized (optional)

### ✅ Security & Access Control
- **Package-Based Access**: Integration requires enabled QuickBooks module
- **Business Isolation**: Complete data isolation between different businesses
- **Secure Credential Storage**: Encrypted storage of API credentials and tokens
- **Upgrade Prompts**: Clear messaging for businesses without QuickBooks access

### ✅ User Experience
- **Modern Interface**: Clean, intuitive configuration interface
- **Real-time Progress**: Live sync progress tracking with detailed status
- **Error Handling**: Comprehensive error reporting and recovery
- **Connection Testing**: Built-in connection validation tools

## Installation & Setup

### Prerequisites
1. QuickBooks Online account
2. QuickBooks Developer App with OAuth2 credentials
3. IsleBooks POS with Quickbooks module enabled

### Configuration Steps

1. **Access Integration**
   ```
   Navigate to: Integrations → QuickBooks Integration → Configure
   ```

2. **Location Setup**
   - Select the business location to configure
   - Enter QuickBooks App credentials (Client ID & Secret)
   - Choose environment (Sandbox for testing, Production for live)

3. **Connect to QuickBooks**
   - Click "Connect to QuickBooks"
   - Authorize the application in QuickBooks
   - Verify successful connection

4. **Configure Synchronization**
   - Enable desired sync operations (customers, products, invoices, etc.)
   - Set auto-sync interval if desired
   - Test sync with sample data

## API Structure

### Authentication Flow
```php
// OAuth2 Authorization URL Generation
$authUrl = $oauthService->getAuthorizationUrl($businessId, $locationId, $clientId);

// Handle OAuth Callback
$result = $oauthService->handleCallback($code, $state, $realmId, $clientId, $clientSecret);
```

### Sync Operations
```php
// Full Synchronization
$results = $syncService->syncAll();

// Specific Entity Sync
$results = $syncService->syncCustomers();
$results = $syncService->syncProducts();
$results = $syncService->syncInvoices();
```

### API Client Usage
```php
// Test Connection
$result = $apiClient->testConnection();

// Create Customer
$response = $apiClient->createCustomer($customerData);

// Sync with Error Handling
try {
    $result = $syncService->syncInvoices();
} catch (Exception $e) {
    Log::error('Sync failed: ' . $e->getMessage());
}
```

## Database Schema

### Primary Configuration Table
```sql
quickbooks_location_settings
├── business_id (FK)
├── location_id (FK) 
├── company_id (QuickBooks)
├── client_id, client_secret
├── access_token, refresh_token
├── sync_configuration_flags
├── sync_statistics_counters
└── error_tracking_fields
```

### Entity Mapping Fields
- `contacts.quickbooks_customer_id`
- `contacts.quickbooks_vendor_id` 
- `products.quickbooks_item_id`
- `transactions.quickbooks_invoice_id`
- `transactions.quickbooks_bill_id`
- `transaction_payments.quickbooks_payment_id`

## Sync Process Flow

1. **Authentication Check**: Verify valid OAuth2 tokens
2. **Data Extraction**: Retrieve POS data for synchronization
3. **Data Transformation**: Map POS data to QuickBooks format
4. **API Communication**: Send data to QuickBooks via REST API
5. **Response Processing**: Handle API responses and update local records
6. **Error Handling**: Log failures and retry mechanisms
7. **Statistics Update**: Track sync counts and success rates

## Error Handling

### Connection Errors
- Invalid credentials → Prompt for reconfiguration
- Expired tokens → Automatic refresh attempt
- Network failures → Retry with exponential backoff

### Data Validation Errors
- Missing required fields → Skip with detailed logging
- Data format mismatches → Transform or report error
- Business rule violations → Log for manual review

### Recovery Mechanisms
- Failed sync items tracked for retry
- Partial sync completion supported
- Manual intervention tools available

## Package Integration

### Module Status Check
```php
if (!\Module::find('Quickbooks')?->isEnabled()) {
    return response()->json(['error' => 'Module not enabled'], 403);
}
```

### Access Control Middleware
```php
Route::middleware(['CheckQuickBooksModule'])->group(function() {
    // QuickBooks routes
});
```

### Upgrade Prompts
- Integration page shows upgrade requirement for disabled modules
- Clear messaging about package limitations
- Contact information for plan upgrades

## Architecture Highlights

### Modular Design
- Separate services for OAuth, API communication, and synchronization
- Clean separation between business logic and API integration
- Extensible architecture for additional QuickBooks features

### Business Isolation
- Complete data separation between businesses
- Location-specific configurations supported
- No cross-tenant data access possible

### Performance Optimization
- Background job processing for large sync operations
- Efficient batch processing with adaptive sizing
- Connection pooling and request optimization

### Security Features
- HTTPS-only communication with QuickBooks
- Secure token storage with encryption
- Business context validation on every request

## Support & Troubleshooting

### Common Issues

1. **Connection Failed**
   - Verify Client ID/Secret accuracy
   - Check sandbox vs production environment
   - Ensure callback URL is whitelisted

2. **Sync Failures**
   - Review sync error logs
   - Check required field mappings  
   - Verify QuickBooks permissions

3. **Performance Issues**
   - Monitor sync batch sizes
   - Check network connectivity
   - Review API rate limit status

### Debug Information
- Sync logs available in application logs
- Connection test tools in interface
- Detailed error messages with context

## Future Enhancements

### Planned Features
- Advanced field mapping configuration
- Custom sync rules and filters
- Multi-currency support
- Enhanced reporting and analytics
- Webhook-based real-time updates

### Extensibility Points
- Custom data transformations
- Additional QuickBooks API endpoints
- Integration with other accounting systems
- Advanced error handling strategies

---

**Module Status**: ✅ Production Ready  
**API Version**: QuickBooks v3 REST API  
**Authentication**: OAuth2 with PKCE  
**Last Updated**: December 2024