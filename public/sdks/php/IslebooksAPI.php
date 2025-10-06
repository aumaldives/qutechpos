<?php

/**
 * IsleBooks POS API PHP SDK
 * 
 * A comprehensive PHP SDK for interacting with the IsleBooks POS API.
 * Provides easy-to-use methods for all API endpoints with proper error handling.
 * 
 * @version 1.0.0
 * @author IsleBooks Development Team
 * @license MIT
 */

namespace IslebooksAPI;

class IslebooksClient
{
    private string $baseUrl;
    private string $apiKey;
    private array $defaultHeaders;
    private int $timeout;
    
    /**
     * Initialize the IsleBooks API client
     * 
     * @param string $baseUrl Base URL of your IsleBooks installation (e.g., 'https://yourstore.islebooks.mv')
     * @param string $apiKey Your API key from IsleBooks dashboard
     * @param array $options Additional options (timeout, etc.)
     */
    public function __construct(string $baseUrl, string $apiKey, array $options = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/api/v1';
        $this->apiKey = $apiKey;
        $this->timeout = $options['timeout'] ?? 30;
        
        $this->defaultHeaders = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: IsleBooks-PHP-SDK/1.0.0'
        ];
    }
    
    /**
     * Products API Methods
     */
    public function products(): ProductsAPI
    {
        return new ProductsAPI($this);
    }
    
    /**
     * Contacts API Methods
     */
    public function contacts(): ContactsAPI
    {
        return new ContactsAPI($this);
    }
    
    /**
     * Transactions API Methods
     */
    public function transactions(): TransactionsAPI
    {
        return new TransactionsAPI($this);
    }
    
    /**
     * Sales API Methods
     */
    public function sales(): SalesAPI
    {
        return new SalesAPI($this);
    }
    
    /**
     * Purchases API Methods
     */
    public function purchases(): PurchasesAPI
    {
        return new PurchasesAPI($this);
    }
    
    /**
     * Reports API Methods
     */
    public function reports(): ReportsAPI
    {
        return new ReportsAPI($this);
    }
    
    /**
     * Business API Methods
     */
    public function business(): BusinessAPI
    {
        return new BusinessAPI($this);
    }
    
    /**
     * Make HTTP request to API
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $params Query parameters
     * @return array API response
     * @throws IslebooksAPIException
     */
    public function request(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Add query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        // Add request body for POST/PUT requests
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new IslebooksAPIException("cURL Error: {$error}");
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IslebooksAPIException("Invalid JSON response: " . json_last_error_msg());
        }
        
        if ($httpCode >= 400) {
            $message = $decodedResponse['message'] ?? 'API Error';
            $error = $decodedResponse['error'] ?? 'UNKNOWN_ERROR';
            throw new IslebooksAPIException("{$message} (HTTP {$httpCode})", $httpCode, $error, $decodedResponse);
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get API status
     */
    public function getStatus(): array
    {
        // Use the working status endpoint
        $curl = curl_init();
        $url = str_replace('/api/v1', '', $this->baseUrl) . '/status';

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new IslebooksAPIException("cURL Error: {$error}");
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IslebooksAPIException("Invalid JSON response: " . json_last_error_msg());
        }

        if ($httpCode >= 400) {
            $message = $decodedResponse['message'] ?? 'API Error';
            $error = $decodedResponse['error'] ?? 'UNKNOWN_ERROR';
            throw new IslebooksAPIException("{$message} (HTTP {$httpCode})", $httpCode, $error, $decodedResponse);
        }

        return $decodedResponse;
    }
    
    /**
     * Ping API
     */
    public function ping(): array
    {
        return $this->request('GET', '/ping');
    }
}

/**
 * Products API Class
 */
class ProductsAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get all products with optional filtering
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', '/products', [], $filters);
    }
    
    /**
     * Get specific product by ID
     */
    public function get(int $id): array
    {
        return $this->client->request('GET', "/products/{$id}");
    }
    
    /**
     * Create new product
     */
    public function create(array $productData): array
    {
        return $this->client->request('POST', '/products', $productData);
    }
    
    /**
     * Update existing product
     */
    public function update(int $id, array $productData): array
    {
        return $this->client->request('PUT', "/products/{$id}", $productData);
    }
    
    /**
     * Delete product
     */
    public function delete(int $id): array
    {
        return $this->client->request('DELETE', "/products/{$id}");
    }
    
    /**
     * Get product variations
     */
    public function getVariations(int $id): array
    {
        return $this->client->request('GET', "/products/{$id}/variations");
    }
    
    /**
     * Get product stock information
     */
    public function getStock(int $id, array $params = []): array
    {
        return $this->client->request('GET', "/products/{$id}/stock", [], $params);
    }
    
    /**
     * Create multiple products at once
     */
    public function bulkCreate(array $productsData): array
    {
        return $this->client->request('POST', '/products/bulk', ['products' => $productsData]);
    }
    
    /**
     * Update multiple products at once
     */
    public function bulkUpdate(array $productsData): array
    {
        return $this->client->request('PUT', '/products/bulk', ['products' => $productsData]);
    }
}

/**
 * Contacts API Class
 */
class ContactsAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get all contacts with optional filtering
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', '/contacts', [], $filters);
    }
    
    /**
     * Get specific contact by ID
     */
    public function get(int $id): array
    {
        return $this->client->request('GET', "/contacts/{$id}");
    }
    
    /**
     * Create new contact
     */
    public function create(array $contactData): array
    {
        return $this->client->request('POST', '/contacts', $contactData);
    }
    
    /**
     * Update existing contact
     */
    public function update(int $id, array $contactData): array
    {
        return $this->client->request('PUT', "/contacts/{$id}", $contactData);
    }
    
    /**
     * Delete contact
     */
    public function delete(int $id): array
    {
        return $this->client->request('DELETE', "/contacts/{$id}");
    }
    
    /**
     * Get contact's transactions
     */
    public function getTransactions(int $id, array $filters = []): array
    {
        return $this->client->request('GET', "/contacts/{$id}/transactions", [], $filters);
    }
    
    /**
     * Get contact's balance
     */
    public function getBalance(int $id): array
    {
        return $this->client->request('GET', "/contacts/{$id}/balance");
    }
}

/**
 * Transactions API Class
 */
class TransactionsAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get all transactions with optional filtering
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', '/transactions', [], $filters);
    }
    
    /**
     * Get specific transaction by ID
     */
    public function get(int $id): array
    {
        return $this->client->request('GET', "/transactions/{$id}");
    }
    
    /**
     * Create new transaction
     */
    public function create(array $transactionData): array
    {
        return $this->client->request('POST', '/transactions', $transactionData);
    }
    
    /**
     * Update existing transaction
     */
    public function update(int $id, array $transactionData): array
    {
        return $this->client->request('PUT', "/transactions/{$id}", $transactionData);
    }
    
    /**
     * Delete transaction
     */
    public function delete(int $id): array
    {
        return $this->client->request('DELETE', "/transactions/{$id}");
    }
    
    /**
     * Add payment to transaction
     */
    public function addPayment(int $id, array $paymentData): array
    {
        return $this->client->request('POST', "/transactions/{$id}/payments", $paymentData);
    }
    
    /**
     * Get transaction payments
     */
    public function getPayments(int $id): array
    {
        return $this->client->request('GET', "/transactions/{$id}/payments");
    }
}

/**
 * Sales API Class
 */
class SalesAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get all sales with optional filtering
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', '/sales', [], $filters);
    }
    
    /**
     * Create new sale
     */
    public function create(array $saleData): array
    {
        return $this->client->request('POST', '/sales', $saleData);
    }
    
    /**
     * Get recent sales
     */
    public function getRecent(array $params = []): array
    {
        return $this->client->request('GET', '/sales/recent', [], $params);
    }
}

/**
 * Purchases API Class
 */
class PurchasesAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get all purchases with optional filtering
     */
    public function list(array $filters = []): array
    {
        return $this->client->request('GET', '/purchases', [], $filters);
    }
    
    /**
     * Create new purchase
     */
    public function create(array $purchaseData): array
    {
        return $this->client->request('POST', '/purchases', $purchaseData);
    }
    
    /**
     * Get recent purchases
     */
    public function getRecent(array $params = []): array
    {
        return $this->client->request('GET', '/purchases/recent', [], $params);
    }
}

/**
 * Reports API Class
 */
class ReportsAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get dashboard metrics
     */
    public function getDashboard(array $params = []): array
    {
        return $this->client->request('GET', '/reports/dashboard', [], $params);
    }
    
    /**
     * Get sales analytics
     */
    public function getSalesAnalytics(array $params = []): array
    {
        return $this->client->request('GET', '/reports/sales-analytics', [], $params);
    }
    
    /**
     * Get profit/loss report
     */
    public function getProfitLoss(array $params = []): array
    {
        return $this->client->request('GET', '/reports/profit-loss', [], $params);
    }
    
    /**
     * Get stock report
     */
    public function getStockReport(array $params = []): array
    {
        return $this->client->request('GET', '/reports/stock-report', [], $params);
    }
    
    /**
     * Get trending products
     */
    public function getTrendingProducts(array $params = []): array
    {
        return $this->client->request('GET', '/reports/trending-products', [], $params);
    }
}

/**
 * Business API Class
 */
class BusinessAPI
{
    private IslebooksClient $client;
    
    public function __construct(IslebooksClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get business information
     */
    public function get(): array
    {
        return $this->client->request('GET', '/business');
    }
    
    /**
     * Get business locations
     */
    public function getLocations(): array
    {
        return $this->client->request('GET', '/business/locations');
    }
    
    /**
     * Get business settings
     */
    public function getSettings(): array
    {
        return $this->client->request('GET', '/business/settings');
    }
}

/**
 * Custom exception for API errors
 */
class IslebooksAPIException extends \Exception
{
    public string $apiError;
    public array $response;
    
    public function __construct(string $message, int $code = 0, string $apiError = '', array $response = [])
    {
        parent::__construct($message, $code);
        $this->apiError = $apiError;
        $this->response = $response;
    }
    
    public function getApiError(): string
    {
        return $this->apiError;
    }
    
    public function getResponse(): array
    {
        return $this->response;
    }
}