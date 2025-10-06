<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BankTransfersProxyController extends Controller
{
    private $apiUrl = 'https://banking.gifty.mv/api/qutechpos/4a3d1a642729faf2d4ccf98f18a4980c';
    private $markPaymentUrl = 'https://banking.gifty.mv/api/qutechpos/mark-payment-used';
    private $forceCheckUrl = 'https://banking.gifty.mv/api/qutechpos/force-check';
    private $authToken = 'qtp_7k9m2n5p8r4w6x1z3b5c7f9h2j4k6m8n';

    /**
     * Get bank transfers
     */
    public function getTransfers(Request $request)
    {
        try {
            $accountCode = $request->input('account_code', '7730000777869');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
            ])->get($this->apiUrl, [
                'account_code' => $accountCode
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to fetch transfers',
                'message' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark payment as used or release
     */
    public function markPaymentStatus(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $status = $request->input('status');

            if (!$transactionId || !isset($status)) {
                return response()->json([
                    'error' => 'Missing required parameters'
                ], 400);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
            ])->asForm()->post($this->markPaymentUrl, [
                'transaction_id' => $transactionId,
                'status' => $status
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to mark payment status',
                'message' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force check for new transfers
     */
    public function forceCheck(Request $request)
    {
        try {
            $accountCode = $request->input('account_code', '7730000777869');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
            ])->asForm()->post($this->forceCheckUrl, [
                'account_code' => $accountCode
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to force check',
                'message' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
