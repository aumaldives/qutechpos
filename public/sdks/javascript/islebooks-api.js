/**
 * IsleBooks POS API JavaScript SDK
 * 
 * A comprehensive JavaScript SDK for interacting with the IsleBooks POS API.
 * Works in both browser and Node.js environments.
 * 
 * @version 1.0.0
 * @author IsleBooks Development Team
 * @license MIT
 */

class IslebooksAPI {
    /**
     * Initialize the IsleBooks API client
     * 
     * @param {string} baseUrl - Base URL of your IsleBooks installation
     * @param {string} apiKey - Your API key from IsleBooks dashboard
     * @param {Object} options - Additional options
     */
    constructor(baseUrl, apiKey, options = {}) {
        this.baseUrl = baseUrl.replace(/\/$/, '') + '/api/v1';
        this.apiKey = apiKey;
        this.timeout = options.timeout || 30000;
        this.retries = options.retries || 3;
        
        this.defaultHeaders = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-API-Key': this.apiKey,
            'User-Agent': 'IsleBooks-JS-SDK/1.0.0'
        };
        
        // Initialize API modules
        this.products = new ProductsAPI(this);
        this.contacts = new ContactsAPI(this);
        this.transactions = new TransactionsAPI(this);
        this.sales = new SalesAPI(this);
        this.purchases = new PurchasesAPI(this);
        this.reports = new ReportsAPI(this);
        this.business = new BusinessAPI(this);
    }
    
    /**
     * Make HTTP request to API with retry logic
     * 
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @param {Object} params - Query parameters
     * @param {number} attempt - Current attempt number
     * @returns {Promise<Object>} API response
     */
    async request(method, endpoint, data = null, params = {}, attempt = 1) {
        const url = new URL(this.baseUrl + '/' + endpoint.replace(/^\//, ''));
        
        // Add query parameters
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        const config = {
            method: method.toUpperCase(),
            headers: { ...this.defaultHeaders },
            signal: AbortSignal.timeout(this.timeout)
        };
        
        // Add request body for POST/PUT requests
        if (['POST', 'PUT', 'PATCH'].includes(method.toUpperCase()) && data) {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url.toString(), config);
            const responseData = await response.json();
            
            if (!response.ok) {
                throw new IslebooksAPIError(
                    responseData.message || 'API Error',
                    response.status,
                    responseData.error || 'UNKNOWN_ERROR',
                    responseData
                );
            }
            
            return responseData;
            
        } catch (error) {
            // Retry logic for network errors
            if (attempt < this.retries && this.isRetryableError(error)) {
                const delay = Math.pow(2, attempt) * 1000; // Exponential backoff
                await this.sleep(delay);
                return this.request(method, endpoint, data, params, attempt + 1);
            }
            
            if (error instanceof IslebooksAPIError) {
                throw error;
            }
            
            throw new IslebooksAPIError(
                error.message || 'Network Error',
                0,
                'NETWORK_ERROR',
                { originalError: error.message }
            );
        }
    }
    
    /**
     * Check if error is retryable
     */
    isRetryableError(error) {
        if (error instanceof IslebooksAPIError) {
            return error.status >= 500 || error.status === 429; // Server errors and rate limits
        }
        return error.name === 'TypeError' || error.name === 'NetworkError';
    }
    
    /**
     * Sleep utility for retry delays
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Get API status
     */
    async getStatus() {
        return this.request('GET', '/status');
    }
    
    /**
     * Ping API
     */
    async ping() {
        return this.request('GET', '/ping');
    }
}

/**
 * Products API Class
 */
class ProductsAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get all products with optional filtering
     */
    async list(filters = {}) {
        return this.client.request('GET', '/products', null, filters);
    }
    
    /**
     * Get specific product by ID
     */
    async get(id) {
        return this.client.request('GET', `/products/${id}`);
    }
    
    /**
     * Create new product
     */
    async create(productData) {
        return this.client.request('POST', '/products', productData);
    }
    
    /**
     * Update existing product
     */
    async update(id, productData) {
        return this.client.request('PUT', `/products/${id}`, productData);
    }
    
    /**
     * Delete product
     */
    async delete(id) {
        return this.client.request('DELETE', `/products/${id}`);
    }
    
    /**
     * Get product variations
     */
    async getVariations(id) {
        return this.client.request('GET', `/products/${id}/variations`);
    }
    
    /**
     * Get product stock information
     */
    async getStock(id, params = {}) {
        return this.client.request('GET', `/products/${id}/stock`, null, params);
    }
    
    /**
     * Create multiple products at once
     */
    async bulkCreate(productsData) {
        return this.client.request('POST', '/products/bulk', { products: productsData });
    }
    
    /**
     * Update multiple products at once
     */
    async bulkUpdate(productsData) {
        return this.client.request('PUT', '/products/bulk', { products: productsData });
    }
}

/**
 * Contacts API Class
 */
class ContactsAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get all contacts with optional filtering
     */
    async list(filters = {}) {
        return this.client.request('GET', '/contacts', null, filters);
    }
    
    /**
     * Get specific contact by ID
     */
    async get(id) {
        return this.client.request('GET', `/contacts/${id}`);
    }
    
    /**
     * Create new contact
     */
    async create(contactData) {
        return this.client.request('POST', '/contacts', contactData);
    }
    
    /**
     * Update existing contact
     */
    async update(id, contactData) {
        return this.client.request('PUT', `/contacts/${id}`, contactData);
    }
    
    /**
     * Delete contact
     */
    async delete(id) {
        return this.client.request('DELETE', `/contacts/${id}`);
    }
    
    /**
     * Get contact's transactions
     */
    async getTransactions(id, filters = {}) {
        return this.client.request('GET', `/contacts/${id}/transactions`, null, filters);
    }
    
    /**
     * Get contact's balance
     */
    async getBalance(id) {
        return this.client.request('GET', `/contacts/${id}/balance`);
    }
}

/**
 * Transactions API Class
 */
class TransactionsAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get all transactions with optional filtering
     */
    async list(filters = {}) {
        return this.client.request('GET', '/transactions', null, filters);
    }
    
    /**
     * Get specific transaction by ID
     */
    async get(id) {
        return this.client.request('GET', `/transactions/${id}`);
    }
    
    /**
     * Create new transaction
     */
    async create(transactionData) {
        return this.client.request('POST', '/transactions', transactionData);
    }
    
    /**
     * Update existing transaction
     */
    async update(id, transactionData) {
        return this.client.request('PUT', `/transactions/${id}`, transactionData);
    }
    
    /**
     * Delete transaction
     */
    async delete(id) {
        return this.client.request('DELETE', `/transactions/${id}`);
    }
    
    /**
     * Add payment to transaction
     */
    async addPayment(id, paymentData) {
        return this.client.request('POST', `/transactions/${id}/payments`, paymentData);
    }
    
    /**
     * Get transaction payments
     */
    async getPayments(id) {
        return this.client.request('GET', `/transactions/${id}/payments`);
    }
}

/**
 * Sales API Class
 */
class SalesAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get all sales with optional filtering
     */
    async list(filters = {}) {
        return this.client.request('GET', '/sales', null, filters);
    }
    
    /**
     * Create new sale
     */
    async create(saleData) {
        return this.client.request('POST', '/sales', saleData);
    }
    
    /**
     * Get recent sales
     */
    async getRecent(params = {}) {
        return this.client.request('GET', '/sales/recent', null, params);
    }
}

/**
 * Purchases API Class
 */
class PurchasesAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get all purchases with optional filtering
     */
    async list(filters = {}) {
        return this.client.request('GET', '/purchases', null, filters);
    }
    
    /**
     * Create new purchase
     */
    async create(purchaseData) {
        return this.client.request('POST', '/purchases', purchaseData);
    }
    
    /**
     * Get recent purchases
     */
    async getRecent(params = {}) {
        return this.client.request('GET', '/purchases/recent', null, params);
    }
}

/**
 * Reports API Class
 */
class ReportsAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get dashboard metrics
     */
    async getDashboard(params = {}) {
        return this.client.request('GET', '/reports/dashboard', null, params);
    }
    
    /**
     * Get sales analytics
     */
    async getSalesAnalytics(params = {}) {
        return this.client.request('GET', '/reports/sales-analytics', null, params);
    }
    
    /**
     * Get profit/loss report
     */
    async getProfitLoss(params = {}) {
        return this.client.request('GET', '/reports/profit-loss', null, params);
    }
    
    /**
     * Get stock report
     */
    async getStockReport(params = {}) {
        return this.client.request('GET', '/reports/stock-report', null, params);
    }
    
    /**
     * Get trending products
     */
    async getTrendingProducts(params = {}) {
        return this.client.request('GET', '/reports/trending-products', null, params);
    }
}

/**
 * Business API Class
 */
class BusinessAPI {
    constructor(client) {
        this.client = client;
    }
    
    /**
     * Get business information
     */
    async get() {
        return this.client.request('GET', '/business');
    }
    
    /**
     * Get business locations
     */
    async getLocations() {
        return this.client.request('GET', '/business/locations');
    }
    
    /**
     * Get business settings
     */
    async getSettings() {
        return this.client.request('GET', '/business/settings');
    }
}

/**
 * Custom error class for API errors
 */
class IslebooksAPIError extends Error {
    constructor(message, status = 0, apiError = '', response = {}) {
        super(message);
        this.name = 'IslebooksAPIError';
        this.status = status;
        this.apiError = apiError;
        this.response = response;
    }
}

// Export for different environments
if (typeof module !== 'undefined' && module.exports) {
    // Node.js environment
    module.exports = { IslebooksAPI, IslebooksAPIError };
} else if (typeof window !== 'undefined') {
    // Browser environment
    window.IslebooksAPI = IslebooksAPI;
    window.IslebooksAPIError = IslebooksAPIError;
}