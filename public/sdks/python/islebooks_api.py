"""
IsleBooks POS API Python SDK

A comprehensive Python SDK for interacting with the IsleBooks POS API.
Provides easy-to-use classes and methods for all API endpoints with proper error handling.

Version: 1.0.0
Author: IsleBooks Development Team
License: MIT
"""

import requests
import json
import time
from typing import Dict, List, Optional, Union, Any
from urllib.parse import urljoin, urlencode


class IslebooksAPIError(Exception):
    """Custom exception for API errors"""
    
    def __init__(self, message: str, status_code: int = 0, api_error: str = '', response_data: Dict = None):
        super().__init__(message)
        self.status_code = status_code
        self.api_error = api_error
        self.response_data = response_data or {}


class IslebooksAPI:
    """Main IsleBooks API client"""
    
    def __init__(self, base_url: str, api_key: str, **options):
        """
        Initialize the IsleBooks API client
        
        Args:
            base_url: Base URL of your IsleBooks installation
            api_key: Your API key from IsleBooks dashboard
            **options: Additional options (timeout, retries, etc.)
        """
        self.base_url = base_url.rstrip('/') + '/api/v1'
        self.api_key = api_key
        self.timeout = options.get('timeout', 30)
        self.retries = options.get('retries', 3)
        self.retry_delay = options.get('retry_delay', 1)
        
        self.session = requests.Session()
        self.session.headers.update({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-API-Key': self.api_key,
            'User-Agent': 'IsleBooks-Python-SDK/1.0.0'
        })
        
        # Initialize API modules
        self.products = ProductsAPI(self)
        self.contacts = ContactsAPI(self)
        self.transactions = TransactionsAPI(self)
        self.sales = SalesAPI(self)
        self.purchases = PurchasesAPI(self)
        self.reports = ReportsAPI(self)
        self.business = BusinessAPI(self)
    
    def request(self, method: str, endpoint: str, data: Dict = None, params: Dict = None, attempt: int = 1) -> Dict:
        """
        Make HTTP request to API with retry logic
        
        Args:
            method: HTTP method (GET, POST, PUT, DELETE)
            endpoint: API endpoint
            data: Request data for POST/PUT requests
            params: Query parameters
            attempt: Current attempt number
            
        Returns:
            API response as dictionary
            
        Raises:
            IslebooksAPIError: For API-related errors
        """
        url = urljoin(self.base_url + '/', endpoint.lstrip('/'))
        
        try:
            kwargs = {
                'timeout': self.timeout,
                'params': params or {}
            }
            
            if method.upper() in ['POST', 'PUT', 'PATCH'] and data:
                kwargs['json'] = data
            
            response = self.session.request(method, url, **kwargs)
            
            # Try to parse JSON response
            try:
                response_data = response.json()
            except json.JSONDecodeError:
                response_data = {'message': 'Invalid JSON response', 'raw_response': response.text}
            
            if not response.ok:
                error_message = response_data.get('message', 'API Error')
                api_error = response_data.get('error', 'UNKNOWN_ERROR')
                
                # Retry on server errors and rate limits
                if attempt < self.retries and response.status_code in [429, 500, 502, 503, 504]:
                    delay = self.retry_delay * (2 ** (attempt - 1))  # Exponential backoff
                    time.sleep(delay)
                    return self.request(method, endpoint, data, params, attempt + 1)
                
                raise IslebooksAPIError(
                    error_message,
                    response.status_code,
                    api_error,
                    response_data
                )
            
            return response_data
            
        except requests.exceptions.RequestException as e:
            # Retry on network errors
            if attempt < self.retries:
                delay = self.retry_delay * (2 ** (attempt - 1))
                time.sleep(delay)
                return self.request(method, endpoint, data, params, attempt + 1)
            
            raise IslebooksAPIError(
                f"Network error: {str(e)}",
                0,
                'NETWORK_ERROR',
                {'original_error': str(e)}
            )
    
    def get_status(self) -> Dict:
        """Get API status"""
        return self.request('GET', '/status')
    
    def ping(self) -> Dict:
        """Ping API"""
        return self.request('GET', '/ping')


class ProductsAPI:
    """Products API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def list(self, **filters) -> Dict:
        """Get all products with optional filtering"""
        return self.client.request('GET', '/products', params=filters)
    
    def get(self, product_id: int) -> Dict:
        """Get specific product by ID"""
        return self.client.request('GET', f'/products/{product_id}')
    
    def create(self, product_data: Dict) -> Dict:
        """Create new product"""
        return self.client.request('POST', '/products', data=product_data)
    
    def update(self, product_id: int, product_data: Dict) -> Dict:
        """Update existing product"""
        return self.client.request('PUT', f'/products/{product_id}', data=product_data)
    
    def delete(self, product_id: int) -> Dict:
        """Delete product"""
        return self.client.request('DELETE', f'/products/{product_id}')
    
    def get_variations(self, product_id: int) -> Dict:
        """Get product variations"""
        return self.client.request('GET', f'/products/{product_id}/variations')
    
    def get_stock(self, product_id: int, **params) -> Dict:
        """Get product stock information"""
        return self.client.request('GET', f'/products/{product_id}/stock', params=params)
    
    def bulk_create(self, products_data: List[Dict]) -> Dict:
        """Create multiple products at once"""
        return self.client.request('POST', '/products/bulk', data={'products': products_data})
    
    def bulk_update(self, products_data: List[Dict]) -> Dict:
        """Update multiple products at once"""
        return self.client.request('PUT', '/products/bulk', data={'products': products_data})


class ContactsAPI:
    """Contacts API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def list(self, **filters) -> Dict:
        """Get all contacts with optional filtering"""
        return self.client.request('GET', '/contacts', params=filters)
    
    def get(self, contact_id: int) -> Dict:
        """Get specific contact by ID"""
        return self.client.request('GET', f'/contacts/{contact_id}')
    
    def create(self, contact_data: Dict) -> Dict:
        """Create new contact"""
        return self.client.request('POST', '/contacts', data=contact_data)
    
    def update(self, contact_id: int, contact_data: Dict) -> Dict:
        """Update existing contact"""
        return self.client.request('PUT', f'/contacts/{contact_id}', data=contact_data)
    
    def delete(self, contact_id: int) -> Dict:
        """Delete contact"""
        return self.client.request('DELETE', f'/contacts/{contact_id}')
    
    def get_transactions(self, contact_id: int, **filters) -> Dict:
        """Get contact's transactions"""
        return self.client.request('GET', f'/contacts/{contact_id}/transactions', params=filters)
    
    def get_balance(self, contact_id: int) -> Dict:
        """Get contact's balance"""
        return self.client.request('GET', f'/contacts/{contact_id}/balance')


class TransactionsAPI:
    """Transactions API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def list(self, **filters) -> Dict:
        """Get all transactions with optional filtering"""
        return self.client.request('GET', '/transactions', params=filters)
    
    def get(self, transaction_id: int) -> Dict:
        """Get specific transaction by ID"""
        return self.client.request('GET', f'/transactions/{transaction_id}')
    
    def create(self, transaction_data: Dict) -> Dict:
        """Create new transaction"""
        return self.client.request('POST', '/transactions', data=transaction_data)
    
    def update(self, transaction_id: int, transaction_data: Dict) -> Dict:
        """Update existing transaction"""
        return self.client.request('PUT', f'/transactions/{transaction_id}', data=transaction_data)
    
    def delete(self, transaction_id: int) -> Dict:
        """Delete transaction"""
        return self.client.request('DELETE', f'/transactions/{transaction_id}')
    
    def add_payment(self, transaction_id: int, payment_data: Dict) -> Dict:
        """Add payment to transaction"""
        return self.client.request('POST', f'/transactions/{transaction_id}/payments', data=payment_data)
    
    def get_payments(self, transaction_id: int) -> Dict:
        """Get transaction payments"""
        return self.client.request('GET', f'/transactions/{transaction_id}/payments')


class SalesAPI:
    """Sales API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def list(self, **filters) -> Dict:
        """Get all sales with optional filtering"""
        return self.client.request('GET', '/sales', params=filters)
    
    def create(self, sale_data: Dict) -> Dict:
        """Create new sale"""
        return self.client.request('POST', '/sales', data=sale_data)
    
    def get_recent(self, **params) -> Dict:
        """Get recent sales"""
        return self.client.request('GET', '/sales/recent', params=params)


class PurchasesAPI:
    """Purchases API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def list(self, **filters) -> Dict:
        """Get all purchases with optional filtering"""
        return self.client.request('GET', '/purchases', params=filters)
    
    def create(self, purchase_data: Dict) -> Dict:
        """Create new purchase"""
        return self.client.request('POST', '/purchases', data=purchase_data)
    
    def get_recent(self, **params) -> Dict:
        """Get recent purchases"""
        return self.client.request('GET', '/purchases/recent', params=params)


class ReportsAPI:
    """Reports API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def get_dashboard(self, **params) -> Dict:
        """Get dashboard metrics"""
        return self.client.request('GET', '/reports/dashboard', params=params)
    
    def get_sales_analytics(self, **params) -> Dict:
        """Get sales analytics"""
        return self.client.request('GET', '/reports/sales-analytics', params=params)
    
    def get_profit_loss(self, **params) -> Dict:
        """Get profit/loss report"""
        return self.client.request('GET', '/reports/profit-loss', params=params)
    
    def get_stock_report(self, **params) -> Dict:
        """Get stock report"""
        return self.client.request('GET', '/reports/stock-report', params=params)
    
    def get_trending_products(self, **params) -> Dict:
        """Get trending products"""
        return self.client.request('GET', '/reports/trending-products', params=params)


class BusinessAPI:
    """Business API operations"""
    
    def __init__(self, client: IslebooksAPI):
        self.client = client
    
    def get(self) -> Dict:
        """Get business information"""
        return self.client.request('GET', '/business')
    
    def get_locations(self) -> Dict:
        """Get business locations"""
        return self.client.request('GET', '/business/locations')
    
    def get_settings(self) -> Dict:
        """Get business settings"""
        return self.client.request('GET', '/business/settings')


# Convenience functions for quick operations
def create_client(base_url: str, api_key: str, **options) -> IslebooksAPI:
    """Create and return a new IslebooksAPI client instance"""
    return IslebooksAPI(base_url, api_key, **options)


def test_connection(base_url: str, api_key: str) -> bool:
    """Test API connection and return True if successful"""
    try:
        client = create_client(base_url, api_key, timeout=10)
        status = client.get_status()
        return status.get('success', False)
    except Exception:
        return False