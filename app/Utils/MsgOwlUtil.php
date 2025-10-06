<?php

namespace App\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MsgOwlUtil
{
    private $apiKey;
    private $senderId;
    private $restBaseUrl = 'https://rest.msgowl.com';
    private $otpBaseUrl = 'https://otp.msgowl.com';

    public function __construct($apiKey, $senderId = null)
    {
        $this->apiKey = $apiKey;
        $this->senderId = $senderId;
    }

    /**
     * Get HTTP client with default headers
     */
    private function getClient()
    {
        return new Client([
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'AccessKey ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Send SMS message
     * 
     * @param string|array $recipients Phone number(s)
     * @param string $message Message body
     * @param string|null $senderId Optional sender ID override
     * @return array Response data
     */
    public function sendMessage($recipients, $message, $senderId = null)
    {
        $payload = [
            'recipients' => $recipients,
            'body' => $message
        ];

        if ($senderId) {
            $payload['sender_id'] = $senderId;
        } elseif ($this->senderId) {
            $payload['sender_id'] = $this->senderId;
        }

        try {
            $client = $this->getClient();
            $response = $client->post($this->restBaseUrl . '/messages', [
                'json' => $payload
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => $response->getStatusCode() == 200,
                'data' => $responseBody,
                'message' => $responseBody['message'] ?? 'Message sent successfully',
                'id' => $responseBody['id'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl SMS Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to send SMS'
            ];
        }
    }

    /**
     * Check account balance
     * 
     * @return array Balance information
     */
    public function getBalance()
    {
        try {
            $client = $this->getClient();
            $response = $client->get($this->restBaseUrl . '/balance');
            
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'balance' => $responseBody['balance'] ?? '0.00'
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl Balance Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance' => '0.00'
            ];
        }
    }

    /**
     * Get message by ID
     * 
     * @param int $messageId Message ID
     * @return array Message details
     */
    public function getMessage($messageId)
    {
        try {
            $client = $this->getClient();
            $response = $client->get($this->restBaseUrl . '/messages/' . $messageId);
            
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $responseBody
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl Get Message Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of messages
     * 
     * @return array Messages list
     */
    public function getMessages()
    {
        try {
            $client = $this->getClient();
            $response = $client->get($this->restBaseUrl . '/messages');
            
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $responseBody
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl Get Messages Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get sender IDs
     * 
     * @return array Sender IDs list
     */
    public function getSenderIds()
    {
        try {
            $client = $this->getClient();
            $response = $client->get($this->restBaseUrl . '/sms_headers');
            
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $responseBody
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl Get Sender IDs Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send OTP
     * 
     * @param string $phoneNumber Phone number
     * @param string|null $code Custom OTP code (optional)
     * @param int $codeLength OTP code length (default: 6)
     * @param string|null $timestamp Custom timestamp (optional)
     * @return array OTP response
     */
    public function sendOtp($phoneNumber, $code = null, $codeLength = 6, $timestamp = null)
    {
        $payload = [
            'phone_number' => $phoneNumber
        ];

        if ($code) {
            $payload['code'] = $code;
        }

        if ($codeLength != 6) {
            $payload['code_length'] = $codeLength;
        }

        if ($timestamp) {
            $payload['timestamp'] = $timestamp;
        }

        try {
            $client = $this->getClient();
            $response = $client->post($this->otpBaseUrl . '/send', [
                'json' => $payload
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => $response->getStatusCode() == 200,
                'data' => $responseBody,
                'id' => $responseBody['id'] ?? null,
                'phone_number' => $responseBody['phone_number'] ?? $phoneNumber,
                'timestamp' => $responseBody['timestamp'] ?? null,
                'message_id' => $responseBody['message_id'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl OTP Send Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resend OTP
     * 
     * @param string $phoneNumber Phone number
     * @param int $otpId OTP ID from previous send
     * @return array OTP response
     */
    public function resendOtp($phoneNumber, $otpId)
    {
        $payload = [
            'phone_number' => $phoneNumber,
            'id' => $otpId
        ];

        try {
            $client = $this->getClient();
            $response = $client->post($this->otpBaseUrl . '/resend', [
                'json' => $payload
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => $response->getStatusCode() == 200,
                'data' => $responseBody,
                'id' => $responseBody['id'] ?? null,
                'phone_number' => $responseBody['phone_number'] ?? $phoneNumber,
                'timestamp' => $responseBody['timestamp'] ?? null,
                'message_id' => $responseBody['message_id'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl OTP Resend Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP
     * 
     * @param string $phoneNumber Phone number
     * @param string $code OTP code to verify
     * @return array Verification response
     */
    public function verifyOtp($phoneNumber, $code)
    {
        $payload = [
            'phone_number' => $phoneNumber,
            'code' => $code
        ];

        try {
            $client = $this->getClient();
            $response = $client->post($this->otpBaseUrl . '/verify', [
                'json' => $payload
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => $response->getStatusCode() == 200,
                'verified' => $responseBody['status'] ?? false,
                'data' => $responseBody,
                'id' => $responseBody['id'] ?? null,
                'phone_number' => $responseBody['phone_number'] ?? $phoneNumber,
                'timestamp' => $responseBody['timestamp'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('MsgOwl OTP Verify Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'verified' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test SMS configuration
     * 
     * @param string $testNumber Phone number for testing
     * @param string $testMessage Test message content
     * @return array Test result
     */
    public function testConfiguration($testNumber, $testMessage = 'Test SMS from IsleBooks POS')
    {
        // First check balance
        $balanceResult = $this->getBalance();
        
        if (!$balanceResult['success']) {
            return [
                'success' => false,
                'message' => 'Failed to check account balance: ' . $balanceResult['error']
            ];
        }

        if (floatval($balanceResult['balance']) <= 0) {
            return [
                'success' => false,
                'message' => 'Insufficient balance. Current balance: ' . $balanceResult['balance']
            ];
        }

        // Send test message
        $result = $this->sendMessage($testNumber, $testMessage);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Test SMS sent successfully. Balance: ' . $balanceResult['balance'],
                'message_id' => $result['id']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Test SMS failed: ' . $result['message']
            ];
        }
    }
}