<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\MsgOwlUtil;
use Illuminate\Http\JsonResponse;

class MsgOwlApiController extends Controller
{
    /**
     * Create MsgOwl utility instance from business settings
     */
    private function getMsgOwlUtil()
    {
        $business = request()->user()->business;
        $sms_settings = json_decode($business->sms_settings, true);

        if (empty($sms_settings['msgowl_api_key'])) {
            return null;
        }

        return new MsgOwlUtil(
            $sms_settings['msgowl_api_key'],
            $sms_settings['msgowl_sender_id'] ?? null
        );
    }

    /**
     * Send OTP to phone number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code_length' => 'nullable|integer|min:4|max:8'
        ]);

        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->sendOtp(
            $request->phone_number,
            null, // Auto-generate code
            $request->input('code_length', 6)
        );

        return response()->json($result);
    }

    /**
     * Resend OTP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp_id' => 'required|integer'
        ]);

        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->resendOtp(
            $request->phone_number,
            $request->otp_id
        );

        return response()->json($result);
    }

    /**
     * Verify OTP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code' => 'required|string'
        ]);

        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->verifyOtp(
            $request->phone_number,
            $request->code
        );

        return response()->json($result);
    }

    /**
     * Send custom SMS
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSms(Request $request): JsonResponse
    {
        $request->validate([
            'recipients' => 'required', // can be string or array
            'message' => 'required|string',
            'sender_id' => 'nullable|string'
        ]);

        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->sendMessage(
            $request->recipients,
            $request->message,
            $request->sender_id
        );

        return response()->json($result);
    }

    /**
     * Get account balance
     * 
     * @return JsonResponse
     */
    public function getBalance(): JsonResponse
    {
        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->getBalance();

        return response()->json($result);
    }

    /**
     * Get message details
     * 
     * @param Request $request
     * @param int $messageId
     * @return JsonResponse
     */
    public function getMessage(Request $request, $messageId): JsonResponse
    {
        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->getMessage($messageId);

        return response()->json($result);
    }

    /**
     * Get messages list
     * 
     * @return JsonResponse
     */
    public function getMessages(): JsonResponse
    {
        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->getMessages();

        return response()->json($result);
    }

    /**
     * Get sender IDs
     * 
     * @return JsonResponse
     */
    public function getSenderIds(): JsonResponse
    {
        $msgowl = $this->getMsgOwlUtil();
        if (!$msgowl) {
            return response()->json([
                'success' => false,
                'message' => 'MsgOwl not configured for this business'
            ], 400);
        }

        $result = $msgowl->getSenderIds();

        return response()->json($result);
    }
}