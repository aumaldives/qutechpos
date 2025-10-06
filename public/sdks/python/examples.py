#!/usr/bin/env python3
"""
IsleBooks Python SDK - Usage Examples

This file demonstrates how to use the IsleBooks Python SDK for common operations.
Make sure to install the required dependencies: pip install requests

Usage:
    python examples.py
"""

import asyncio
import time
from datetime import datetime, date
from islebooks_api import IslebooksAPI, IslebooksAPIError, create_client, test_connection


def main():
    """Main examples function"""
    
    # Configuration
    base_url = 'https://pos.islebooks.mv'  # Replace with your IsleBooks URL
    api_key = 'YOUR_API_KEY_HERE'  # Replace with your actual API key
    
    print('=== IsleBooks Python SDK Examples ===\n')
    
    # Test connection first
    print('0. Testing Connection...')
    if not test_connection(base_url, api_key):
        print('   ❌ Connection failed! Please check your URL and API key.')
        return
    print('   ✓ Connection successful!\n')
    
    # Initialize client
    client = create_client(base_url, api_key, timeout=30, retries=3)
    
    try:
        # 1. Get API Status
        print('1. Getting API Status...')
        status = client.get_status()
        print(f'   ✓ API Status: {status["message"]}')
        print(f'   ✓ Version: {status["version"]}\n')
        
        # 2. Get Business Information
        print('2. Getting Business Information...')
        business = client.business.get()
        print(f'   ✓ Business: {business["data"]["name"]}')
        print(f'   ✓ Currency: {business["data"]["currency"]["code"]}')
        print(f'   ✓ Locations: {len(business["data"]["locations"])}\n')
        
        # 3. List Products
        print('3. Listing Products...')
        products = client.products.list(per_page=5, is_active=True, page=1)
        print(f'   ✓ Found {products["meta"]["total"]} total products')
        print('   Products:')
        for product in products['data'][:3]:
            print(f'   - {product["name"]} (SKU: {product["sku"]})')
        print()
        
        # 4. Create New Product
        print('4. Creating New Product...')
        timestamp = int(time.time())
        new_product = client.products.create({
            'name': 'Python SDK Test Product',
            'sku': f'PY-SDK-TEST-{timestamp}',
            'type': 'single',
            'unit_id': 1,
            'category_id': 1,
            'barcode_type': 'C128',
            'alert_quantity': 5,
            'product_custom_field1': 'Created via Python SDK',
            'product_description': 'This product was created using the IsleBooks Python SDK',
            'single_dpp': 40.00,
            'single_dpp_inc_tax': 44.00,
            'single_dsp': 60.00,
            'single_dsp_inc_tax': 66.00
        })
        product_id = new_product['data']['id']
        print(f'   ✓ Created product with ID: {product_id}')
        print(f'   ✓ Product Name: {new_product["data"]["name"]}\n')
        
        # 5. Get Product Details and Variations
        print('5. Getting Product Details...')
        product_details = client.products.get(product_id)
        variations = client.products.get_variations(product_id)
        stock_info = client.products.get_stock(product_id)
        
        print(f'   ✓ Product: {product_details["data"]["name"]}')
        print(f'   ✓ Variations: {len(variations["data"])}')
        print(f'   ✓ Stock Locations: {len(stock_info["data"])}\n')
        
        # 6. Manage Customers
        print('6. Managing Customers...')
        customers = client.contacts.list(type='customer', per_page=3)
        print(f'   ✓ Found {customers["meta"]["total"]} customers')
        
        # Create new customer
        new_customer = client.contacts.create({
            'type': 'customer',
            'name': 'Python SDK Test Customer',
            'mobile': f'+960789{timestamp % 10000:04d}',
            'email': f'py.sdk.test.{timestamp}@example.com',
            'city': 'Addu City',
            'country': 'Maldives',
            'address_line_1': '456 Python Street'
        })
        customer_id = new_customer['data']['id']
        print(f'   ✓ Created customer with ID: {customer_id}\n')
        
        # 7. Create Sale Transaction
        print('7. Creating Sale Transaction...')
        sale_data = {
            'contact_id': customer_id,
            'transaction_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'invoice_no': f'PY-SDK-{timestamp}',
            'status': 'final',
            'payment_status': 'paid',
            'final_total': 66.00,
            'discount_type': 'fixed',
            'discount_amount': 0,
            'shipping_charges': 0,
            'additional_notes': 'Sale created via Python SDK',
            'products': [{
                'product_id': product_id,
                'variation_id': variations['data'][0]['id'],
                'quantity': 1,
                'unit_price': 60.00,
                'unit_price_inc_tax': 66.00
            }],
            'payment': [{
                'amount': 66.00,
                'method': 'cash',
                'paid_on': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }]
        }
        
        sale = client.sales.create(sale_data)
        sale_id = sale['data']['id']
        print(f'   ✓ Created sale with ID: {sale_id}')
        print(f'   ✓ Invoice No: {sale["data"]["invoice_no"]}')
        print(f'   ✓ Total Amount: {sale["data"]["final_total"]}\n')
        
        # 8. Get Customer Transaction History
        print('8. Getting Customer History...')
        transactions = client.contacts.get_transactions(customer_id, per_page=5)
        balance = client.contacts.get_balance(customer_id)
        
        print(f'   ✓ Customer has {transactions["meta"]["total"]} transactions')
        print(f'   ✓ Customer balance: {balance["data"]["balance"]}\n')
        
        # 9. Get Dashboard Metrics
        print('9. Getting Dashboard Metrics...')
        today = date.today()
        first_day = today.replace(day=1)
        
        dashboard = client.reports.get_dashboard(
            date_from=first_day.strftime('%Y-%m-%d'),
            date_to=today.strftime('%Y-%m-%d')
        )
        
        print(f'   ✓ Total Sales: {dashboard["data"]["sales"]["total_sales"]}')
        print(f'   ✓ Total Profit: {dashboard["data"]["profit"]["total_profit"]}')
        print(f'   ✓ Total Customers: {dashboard["data"]["customers"]["total_customers"]}')
        print(f'   ✓ Active Products: {dashboard["data"]["products"]["total_active_products"]}\n')
        
        # 10. Get Sales Analytics
        print('10. Getting Sales Analytics...')
        analytics = client.reports.get_sales_analytics(period='this_month')
        
        print(f'   ✓ Sales Trend Points: {len(analytics["data"]["sales_trend"])}')
        print(f'   ✓ Top Products: {len(analytics["data"]["top_products"])}')
        print(f'   ✓ Customer Growth: {len(analytics["data"]["customer_growth"])}\n')
        
        # 11. Advanced Operations
        print('11. Running Advanced Operations...')
        run_advanced_examples(client, product_id, customer_id)
        
        print('=== All Examples Completed Successfully! ===')
        
    except IslebooksAPIError as e:
        print(f'❌ API Error: {e}')
        print(f'   Status Code: {e.status_code}')
        print(f'   API Error: {e.api_error}')
        if e.response_data:
            print(f'   Response: {e.response_data}')
    
    except Exception as e:
        print(f'❌ General Error: {e}')
        import traceback
        traceback.print_exc()


def run_advanced_examples(client: IslebooksAPI, product_id: int, customer_id: int):
    """Run advanced examples"""
    
    try:
        # Bulk Operations
        print('   - Testing bulk operations...')
        bulk_products = [
            {
                'name': f'Bulk Python Product {i}',
                'sku': f'BULK-PY-{i}-{int(time.time())}',
                'type': 'single',
                'unit_id': 1,
                'category_id': 1,
                'single_dpp': 15.00 + i,
                'single_dsp': 25.00 + i
            }
            for i in range(1, 4)
        ]
        
        bulk_result = client.products.bulk_create(bulk_products)
        print(f'     ✓ Created {bulk_result["data"]["created"]} products in bulk')
        
        # Advanced Filtering
        print('   - Testing advanced filtering...')
        filtered_products = client.products.list(
            search='Python',
            is_active=True,
            sort_by='name',
            sort_direction='desc',
            per_page=5
        )
        print(f'     ✓ Found {len(filtered_products["data"])} products matching "Python"')
        
        # Stock Management
        print('   - Testing stock management...')
        stock = client.products.get_stock(product_id)
        for location_stock in stock['data']:
            location_name = location_stock['location_name']
            quantity = location_stock['qty_available']
            print(f'     - {location_name}: {quantity} units')
        
        # Recent Sales and Purchases
        print('   - Getting recent transactions...')
        recent_sales = client.sales.get_recent(limit=3)
        recent_purchases = client.purchases.get_recent(limit=3)
        
        print(f'     ✓ Recent sales: {len(recent_sales["data"])}')
        print(f'     ✓ Recent purchases: {len(recent_purchases["data"])}')
        
        # Multiple Reports
        print('   - Getting comprehensive reports...')
        reports_data = {}
        
        reports_data['profit_loss'] = client.reports.get_profit_loss(period='this_month')
        reports_data['stock_report'] = client.reports.get_stock_report(location_id=1)
        reports_data['trending'] = client.reports.get_trending_products(period='last_30_days', limit=5)
        
        print(f'     ✓ Profit/Loss report loaded')
        print(f'     ✓ Stock report: {len(reports_data["stock_report"]["data"])} items')
        print(f'     ✓ Trending products: {len(reports_data["trending"]["data"])} items')
        
    except IslebooksAPIError as e:
        print(f'   ❌ Advanced example failed: {e}')


def performance_test(client: IslebooksAPI):
    """Test API performance with concurrent-like operations"""
    print('\n=== Performance Test ===')
    
    try:
        start_time = time.time()
        
        # Simulate concurrent operations by measuring sequential performance
        operations = [
            lambda: client.products.list(per_page=10),
            lambda: client.contacts.list(type='customer', per_page=10),
            lambda: client.sales.get_recent(limit=10),
            lambda: client.reports.get_dashboard(),
            lambda: client.business.get()
        ]
        
        results = []
        for operation in operations:
            op_start = time.time()
            result = operation()
            op_end = time.time()
            results.append((operation.__name__ if hasattr(operation, '__name__') else 'operation', op_end - op_start))
        
        total_time = time.time() - start_time
        
        print(f'✓ Completed {len(operations)} operations in {total_time:.2f}s')
        for op_name, op_time in results:
            print(f'  - {op_name}: {op_time:.2f}s')
    
    except Exception as e:
        print(f'❌ Performance test failed: {e}')


def error_handling_examples(client: IslebooksAPI):
    """Demonstrate proper error handling"""
    print('\n=== Error Handling Examples ===')
    
    # Test 404 error
    try:
        client.products.get(999999)
    except IslebooksAPIError as e:
        if e.status_code == 404:
            print('✓ Properly handled 404 error for non-existent product')
    
    # Test validation error
    try:
        client.products.create({
            'name': '',  # Invalid empty name
            'type': 'single'
            # Missing required fields
        })
    except IslebooksAPIError as e:
        if e.status_code == 422:
            print('✓ Properly handled validation error')
            print(f'   Validation errors: {e.response_data.get("errors", {})}')
    
    # Test rate limiting (if implemented)
    try:
        # Make rapid requests to potentially trigger rate limiting
        for i in range(5):
            client.ping()
            time.sleep(0.1)
        print('✓ No rate limiting encountered with moderate usage')
    except IslebooksAPIError as e:
        if e.status_code == 429:
            print('✓ Properly handled rate limiting error')


if __name__ == '__main__':
    main()
    
    # Uncomment to run additional examples:
    # 
    # client = create_client('https://pos.islebooks.mv', 'YOUR_API_KEY_HERE')
    # performance_test(client)
    # error_handling_examples(client)


# Additional utility functions
def export_products_to_csv(client: IslebooksAPI, filename: str = 'products_export.csv'):
    """Export all products to CSV file"""
    import csv
    
    try:
        products = client.products.list(per_page=1000)  # Get large batch
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            if not products['data']:
                print('No products to export')
                return
                
            fieldnames = products['data'][0].keys()
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            
            writer.writeheader()
            for product in products['data']:
                writer.writerow(product)
        
        print(f'✓ Exported {len(products["data"])} products to {filename}')
        
    except Exception as e:
        print(f'❌ Export failed: {e}')


def generate_sales_report(client: IslebooksAPI, date_from: str, date_to: str):
    """Generate comprehensive sales report"""
    try:
        print(f'\n=== Sales Report ({date_from} to {date_to}) ===')
        
        # Get sales data
        sales = client.sales.list(
            date_from=date_from,
            date_to=date_to,
            per_page=1000
        )
        
        # Get analytics
        analytics = client.reports.get_sales_analytics(
            date_from=date_from,
            date_to=date_to
        )
        
        # Get dashboard metrics
        dashboard = client.reports.get_dashboard(
            date_from=date_from,
            date_to=date_to
        )
        
        print(f'Total Sales: {len(sales["data"])}')
        print(f'Total Revenue: {dashboard["data"]["sales"]["total_sales"]}')
        print(f'Total Profit: {dashboard["data"]["profit"]["total_profit"]}')
        print(f'Average Sale Value: {dashboard["data"]["sales"]["average_sale_value"]}')
        
        # Top products
        if analytics["data"]["top_products"]:
            print('\nTop Selling Products:')
            for i, product in enumerate(analytics["data"]["top_products"][:5], 1):
                print(f'  {i}. {product["product_name"]} - {product["total_sold"]} units')
        
    except Exception as e:
        print(f'❌ Report generation failed: {e}')


def monitor_inventory_levels(client: IslebooksAPI, alert_threshold: int = 10):
    """Monitor inventory and alert on low stock"""
    try:
        print(f'\n=== Inventory Monitor (Alert threshold: {alert_threshold}) ===')
        
        products = client.products.list(per_page=1000, is_active=True)
        low_stock_products = []
        
        for product in products['data']:
            if product['current_stock'] <= alert_threshold:
                low_stock_products.append({
                    'name': product['name'],
                    'sku': product['sku'],
                    'current_stock': product['current_stock'],
                    'alert_quantity': product.get('alert_quantity', 0)
                })
        
        if low_stock_products:
            print(f'⚠️  {len(low_stock_products)} products below threshold:')
            for product in low_stock_products[:10]:  # Show first 10
                print(f'  - {product["name"]} (SKU: {product["sku"]}): {product["current_stock"]} units')
        else:
            print('✓ All products have sufficient stock levels')
    
    except Exception as e:
        print(f'❌ Inventory monitoring failed: {e}')