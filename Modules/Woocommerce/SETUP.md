# WooCommerce Integration Setup Guide

## Overview

The IsleBooks POS WooCommerce integration provides seamless synchronization between your WooCommerce store and IsleBooks POS system. This allows you to manage inventory, orders, and customers across both platforms.

## Features

- ✅ **Secure Connection** - HMAC-SHA256 webhook validation
- ✅ **Real-time Sync** - Automatic updates via webhooks
- ✅ **Batch Processing** - Efficient handling of large catalogs
- ✅ **Progress Tracking** - Real-time sync progress monitoring
- ✅ **Error Handling** - Comprehensive error reporting and recovery
- ✅ **Conflict Resolution** - Intelligent handling of data conflicts

## Prerequisites

### WooCommerce Store Requirements
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- WooCommerce REST API enabled
- SSL certificate (recommended for production)

### IsleBooks POS Requirements
- Active IsleBooks POS account
- WooCommerce module enabled in subscription
- Admin or Manager level access

## Step 1: WooCommerce Store Setup

### 1.1 Enable REST API
1. Login to your WordPress admin dashboard
2. Navigate to **WooCommerce → Settings → Advanced → REST API**
3. Click **Add Key**
4. Fill in the details:
   - **Description**: `IsleBooks POS Integration`
   - **User**: Select an Administrator user
   - **Permissions**: `Read/Write`
5. Click **Generate API Key**
6. **IMPORTANT**: Copy and save both the Consumer Key and Consumer Secret

### 1.2 Configure Webhook Settings (Optional but Recommended)
1. Go to **WooCommerce → Settings → Advanced → Webhooks**
2. Click **Add webhook** for each event type:

#### Order Created Webhook
- **Name**: `IsleBooks Order Created`
- **Status**: `Active`
- **Topic**: `Order created`
- **Delivery URL**: `https://pos.islebooks.mv/webhook/order-created/{business_id}`
- **Secret**: Generate a 32-character random string
- **API Version**: `WP REST API Integration v3`

#### Order Updated Webhook
- **Name**: `IsleBooks Order Updated`
- **Status**: `Active`
- **Topic**: `Order updated`
- **Delivery URL**: `https://pos.islebooks.mv/webhook/order-updated/{business_id}`
- **Secret**: Same as above
- **API Version**: `WP REST API Integration v3`

#### Order Deleted Webhook
- **Name**: `IsleBooks Order Deleted`
- **Status**: `Active`
- **Topic**: `Order deleted`
- **Delivery URL**: `https://pos.islebooks.mv/webhook/order-deleted/{business_id}`
- **Secret**: Same as above
- **API Version**: `WP REST API Integration v3`

**Note**: Replace `{business_id}` with your actual business ID from IsleBooks POS.

## Step 2: IsleBooks POS Configuration

### 2.1 Access WooCommerce Settings
1. Login to your IsleBooks POS account
2. Navigate to **Settings → Integrations**
3. Click on **WooCommerce Configuration**

### 2.2 API Configuration
1. In the **API Configuration** tab, enter:
   - **Store URL**: Your WooCommerce store URL (e.g., `https://yourstore.com`)
   - **Consumer Key**: From Step 1.1
   - **Consumer Secret**: From Step 1.1
   - **API Version**: Select `WC/v3` (recommended)
   - **Auto Sync**: Enable for automatic hourly synchronization

2. Click **Test Connection** to verify the setup
3. If successful, click **Save Configuration**

### 2.3 Webhook Setup (if configured in Step 1.2)
1. Switch to the **Webhook Setup** tab
2. The webhook URLs are automatically generated
3. Enter the **Webhook Secret** from Step 1.2
4. Click **Test Webhooks** to verify the connection

## Step 3: Initial Data Synchronization

### 3.1 Manual Sync
1. Go to the **Synchronization** tab
2. Start with **Category Sync** first
3. Then run **Product Sync**
4. Finally, run **Order Sync**
5. Monitor the progress and check for any errors

### 3.2 Full Sync (Alternative)
- Click **Full Sync** to run all synchronization operations in sequence
- This may take several minutes for large catalogs

## Step 4: Verification

### 4.1 Check Statistics
- Review the statistics cards at the top of the WooCommerce configuration page
- Verify that products, orders, and categories show the expected counts

### 4.2 Test Data Flow
1. Create a test product in WooCommerce
2. Run a manual product sync or wait for automatic sync
3. Verify the product appears in IsleBooks POS
4. Test order synchronization by placing a test order in WooCommerce

## Troubleshooting

### Common Issues

#### Connection Failed
- **Check Store URL**: Ensure it's accessible and includes `https://`
- **Verify API Keys**: Regenerate keys if necessary
- **SSL Issues**: Contact your hosting provider for SSL certificate problems
- **Firewall**: Ensure WooCommerce REST API endpoints are not blocked

#### Sync Errors
- **Check Logs**: View sync logs in the configuration interface
- **Permissions**: Verify the API user has adequate permissions
- **Data Issues**: Check for required fields in products/orders
- **Rate Limits**: Large catalogs may need multiple sync runs

#### Webhook Issues
- **Secret Mismatch**: Ensure webhook secret matches in both systems
- **URL Accessibility**: Verify webhook URLs are publicly accessible
- **Delivery Failures**: Check WooCommerce webhook logs for errors

### Performance Optimization

#### For Large Catalogs (1000+ products)
- Run synchronization during low-traffic hours
- Use manual sync instead of auto-sync for better control
- Monitor server resources during sync operations
- Consider syncing in smaller batches

#### Regular Maintenance
- Review sync logs weekly
- Clear old sync logs monthly
- Update API keys annually for security
- Monitor webhook delivery success rates

## Security Best Practices

1. **Use Strong Secrets**: Generate 32+ character webhook secrets
2. **Regular Key Rotation**: Update API keys every 6-12 months
3. **Monitor Access**: Review API access logs regularly
4. **SSL Enforcement**: Always use HTTPS for production stores
5. **Limit Permissions**: Use dedicated API users with minimal required permissions

## Support

### Getting Help
- **Documentation**: Check this guide first
- **Logs**: Review sync logs for specific error messages
- **Support**: Contact IsleBooks support with log details
- **Community**: Join the IsleBooks user community for tips

### Reporting Issues
When reporting issues, please include:
- Store URL (without credentials)
- Error messages from sync logs
- Steps to reproduce the issue
- Screenshot of the error (if applicable)
- WooCommerce and WordPress versions

## API Endpoints Reference

### Configuration Endpoints
- `GET /woocommerce/api/settings` - Get current settings
- `POST /woocommerce/api/settings` - Update settings
- `POST /woocommerce/api/test-connection` - Test API connection
- `GET /woocommerce/api/stats` - Get synchronization statistics

### Synchronization Endpoints  
- `POST /woocommerce/api/sync` - Start synchronization
- `GET /woocommerce/api/sync-logs` - Get sync history

### Webhook Endpoints
- `POST /webhook/order-created/{business_id}` - Order created
- `POST /webhook/order-updated/{business_id}` - Order updated
- `POST /webhook/order-deleted/{business_id}` - Order deleted

## Version History

### v2.0 - Enhanced Integration (Current)
- Complete UI/UX redesign with AdminLTE theme
- Enhanced error handling and progress tracking
- Batch processing for large catalogs
- Real-time webhook validation with HMAC-SHA256
- Conflict resolution for simultaneous updates
- Performance optimization with caching
- Comprehensive logging and monitoring

### v1.0 - Legacy Integration
- Basic product and order synchronization
- Simple webhook support
- Manual sync operations

---

**Last Updated**: December 2024
**Version**: 2.0
**Compatibility**: WooCommerce 5.0+, WordPress 5.0+