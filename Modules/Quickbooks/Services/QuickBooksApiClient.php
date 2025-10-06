<?php

namespace Modules\Quickbooks\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;

class QuickBooksApiClient
{
    private Client $httpClient;
    private QuickbooksLocationSettings $settings;
    private string $baseUrl;
    private bool $isSandbox;

    public function __construct(QuickbooksLocationSettings $settings)
    {
        $this->settings = $settings;
        $this->isSandbox = $settings->sandbox_mode === 'sandbox';
        $this->baseUrl = $this->isSandbox 
            ? 'https://sandbox-quickbooks.api.intuit.com/v3/company/' . $settings->company_id
            : 'https://quickbooks.api.intuit.com/v3/company/' . $settings->company_id;

        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function makeAuthenticatedRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->isTokenValid()) {
            $this->refreshAccessToken();
        }

        try {
            $url = $this->baseUrl . $endpoint;
            
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->settings->access_token,
                    'Accept' => 'application/json',
                ],
            ];

            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request($method, $url, $options);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            Log::error('QuickBooks API request failed', [
                'business_id' => $this->settings->business_id,
                'location_id' => $this->settings->location_id,
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            throw new Exception('QuickBooks API request failed: ' . $e->getMessage());
        }
    }

    public function getCustomers(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/customers?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createCustomer(array $customerData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/customer', $customerData);
    }

    public function updateCustomer(string $customerId, array $customerData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', "/customer?operation=update", $customerData);
    }

    public function getVendors(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/vendors?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createVendor(array $vendorData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/vendor', $vendorData);
    }

    public function getItems(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/items?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createItem(array $itemData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/item', $itemData);
    }

    public function updateItem(string $itemId, array $itemData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', "/item?operation=update", $itemData);
    }

    public function getInvoices(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/invoices?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createInvoice(array $invoiceData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/invoice', $invoiceData);
    }

    public function getPayments(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/payments?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createPayment(array $paymentData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/payment', $paymentData);
    }

    public function getBills(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/bills?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createBill(array $billData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/bill', $billData);
    }

    public function getBillPayments(int $page = 1, int $limit = 20): ?array
    {
        $startPosition = ($page - 1) * $limit + 1;
        $endpoint = "/billpayments?startPosition={$startPosition}&maxResults={$limit}";
        
        return $this->makeAuthenticatedRequest('GET', $endpoint);
    }

    public function createBillPayment(array $billPaymentData): ?array
    {
        return $this->makeAuthenticatedRequest('POST', '/billpayment', $billPaymentData);
    }

    public function refreshAccessToken(): bool
    {
        try {
            $appConfig = $this->settings->appConfig();
            if (!$appConfig) {
                throw new Exception('App configuration not found');
            }

            $response = $this->httpClient->post($appConfig->getTokenEndpoint(), [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->settings->refresh_token,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($appConfig->client_id . ':' . $appConfig->client_secret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            if (!$response->getStatusCode() === 200) {
                throw new Exception('Token refresh failed with status: ' . $response->getStatusCode());
            }

            $tokenData = json_decode($response->getBody()->getContents(), true);

            if (!isset($tokenData['access_token'])) {
                throw new Exception('Invalid token response: missing access_token');
            }

            $this->settings->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $this->settings->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'last_token_refresh_at' => now(),
                'consecutive_failed_refreshes' => 0
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('QuickBooks token refresh failed', [
                'business_id' => $this->settings->business_id,
                'location_id' => $this->settings->location_id,
                'error' => $e->getMessage(),
            ]);

            // Increment failure counter
            $this->settings->increment('consecutive_failed_refreshes');
            
            // Disable connection after 5 consecutive failures
            if ($this->settings->consecutive_failed_refreshes >= 5) {
                $this->settings->update([
                    'connection_status' => 'token_expired',
                    'is_active' => false,
                    'last_sync_error' => 'Token refresh failed after 5 attempts'
                ]);
            }

            return false;
        }
    }

    private function isTokenValid(): bool
    {
        if (!$this->settings->access_token || !$this->settings->token_expires_at) {
            return false;
        }

        return now()->lt($this->settings->token_expires_at->subMinutes(5));
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', '/companyinfo/1');
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'company_info' => $response['QueryResponse']['CompanyInfo'][0] ?? null,
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}