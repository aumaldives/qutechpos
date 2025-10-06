<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TelegramBotSetting;
use App\TelegramBotSession;
use App\Business;
use App\Transaction;
use App\ExpenseCategory;
use GuzzleHttp\Client;
use Carbon\Carbon;

class TelegramBotController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function webhook(Request $request, $business_id)
    {
        try {
            $update = $request->all();
            \Log::info('Telegram webhook received', [
                'business_id' => $business_id,
                'update_id' => $update['update_id'] ?? 'unknown',
                'message_text' => $update['message']['text'] ?? null,
                'chat_id' => $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null,
                'full_update' => $update
            ]);

            // Check if it's a message or callback query
            if (isset($update['message'])) {
                \Log::info('Processing message', ['business_id' => $business_id, 'text' => $update['message']['text'] ?? 'no text']);
                $this->handleMessage($update['message'], $business_id);
            } elseif (isset($update['callback_query'])) {
                \Log::info('Processing callback query', ['business_id' => $business_id, 'data' => $update['callback_query']['data']]);
                $this->handleCallbackQuery($update['callback_query'], $business_id);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Telegram webhook error: ' . $e->getMessage(), [
                'business_id' => $business_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessage($message, $business_id)
    {
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';

        \Log::info('Handling message', ['business_id' => $business_id, 'chat_id' => $chat_id, 'text' => $text]);

        // Find telegram settings for this specific business
        $telegram_setting = TelegramBotSetting::where('business_id', $business_id)
            ->where('is_active', true)
            ->first();

        \Log::info('Telegram setting found', [
            'business_id' => $business_id,
            'setting_exists' => $telegram_setting ? true : false,
            'is_authorized' => $telegram_setting ? $telegram_setting->isChatAuthorized($chat_id) : false
        ]);

        if (!$telegram_setting) {
            \Log::warning('No active telegram settings for business', ['business_id' => $business_id, 'chat_id' => $chat_id]);
            return;
        }

        if (!$telegram_setting->isChatAuthorized($chat_id)) {
            \Log::warning('Unauthorized chat attempt', ['business_id' => $business_id, 'chat_id' => $chat_id]);
            $this->sendMessage($chat_id, "🚫 You are not authorized to use this bot.", null, $telegram_setting);
            return;
        }

        // Get or create session
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        // Handle commands and responses
        if ($text === '/start') {
            $this->handleStartCommand($chat_id, $telegram_setting);
        } elseif ($text === '📅 Reports') {
            $this->showReportsMenu($chat_id, $telegram_setting);
        } elseif ($text === '💵 Add Expense') {
            $this->startAddExpense($chat_id, $telegram_setting);
        } elseif ($session && !$session->isExpired() && $session->current_action) {
            $this->handleSessionAction($session, $text, $message);
        } else {
            $this->showMainMenu($chat_id, $telegram_setting);
        }
    }

    /**
     * Handle callback queries (inline keyboard buttons)
     */
    private function handleCallbackQuery($callback_query, $business_id)
    {
        $chat_id = $callback_query['message']['chat']['id'];
        $data = $callback_query['data'];
        $message_id = $callback_query['message']['message_id'];

        // Find telegram settings for this specific business
        $telegram_setting = TelegramBotSetting::where('business_id', $business_id)
            ->where('is_active', true)
            ->first();

        if (!$telegram_setting || !$telegram_setting->isChatAuthorized($chat_id)) {
            return;
        }

        // Answer callback query to remove loading state
        $this->answerCallbackQuery($callback_query['id'], $telegram_setting);

        // Handle different callback data
        $this->handleCallbackData($chat_id, $data, $telegram_setting, $message_id);
    }

    /**
     * Handle start command
     */
    private function handleStartCommand($chat_id, $telegram_setting)
    {
        $business = $telegram_setting->business;
        $message = "🏢 Welcome to *{$business->name}* POS Bot!\n\n";
        $message .= "I can help you with:\n";
        $message .= "📅 View reports and analytics\n";
        $message .= "💵 Add expenses quickly\n";
        $message .= "🏪 Manage multiple locations\n\n";
        $message .= "Choose an option from the menu below:";

        $this->showMainMenu($chat_id, $telegram_setting, $message);
    }

    /**
     * Show main menu
     */
    private function showMainMenu($chat_id, $telegram_setting, $custom_message = null)
    {
        $message = $custom_message ?: "🏢 *{$telegram_setting->business->name}* - Main Menu\n\nWhat would you like to do?";

        // Use Reply Keyboard for main menu
        $keyboard = [
            [
                ['text' => '📅 Reports'],
                ['text' => '💵 Add Expense']
            ]
        ];

        $this->sendMessageWithReplyKeyboard($chat_id, $message, $keyboard, $telegram_setting);
    }

    /**
     * Handle callback data
     */
    private function handleCallbackData($chat_id, $data, $telegram_setting, $message_id = null)
    {
        switch ($data) {
            case 'reports':
                $this->showReportsMenu($chat_id, $telegram_setting, $message_id);
                break;
            case 'add_expense':
                $this->startAddExpense($chat_id, $telegram_setting, $message_id);
                break;
            case 'back_to_main':
                // For back to main menu, remove keyboard and show main menu
                if ($message_id) {
                    $this->editMessageAndRemoveKeyboard($chat_id, $message_id, "🏠 Returning to main menu...", $telegram_setting);
                }
                $this->showMainMenu($chat_id, $telegram_setting);
                break;
            default:
                // Handle dynamic callbacks (location selection, period selection, etc.)
                $this->handleDynamicCallback($chat_id, $data, $telegram_setting, $message_id);
                break;
        }
    }

    /**
     * Show reports menu
     */
    private function showReportsMenu($chat_id, $telegram_setting, $message_id = null)
    {
        $business = $telegram_setting->business;
        $locations = $business->locations;

        if ($locations->isEmpty()) {
            $error_message = "❌ No business locations found.";
            if ($message_id) {
                $this->editMessageAndRemoveKeyboard($chat_id, $message_id, $error_message, $telegram_setting);
            } else {
                $this->sendMessage($chat_id, $error_message, null, $telegram_setting);
            }
            return;
        }

        $message = "📅 *Reports*\n\nSelect a business location:";
        $keyboard = [];

        foreach ($locations as $location) {
            $keyboard[] = [['text' => "📍 {$location->name}", 'callback_data' => "location_{$location->id}"]];
        }

        $keyboard[] = [['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_to_main']];

        if ($message_id) {
            // Edit the existing message
            $this->editMessageText($chat_id, $message_id, $message, $keyboard, $telegram_setting);
        } else {
            // Send new message
            $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
        }
    }

    /**
     * Start add expense workflow
     */
    private function startAddExpense($chat_id, $telegram_setting, $message_id = null)
    {
        $business = $telegram_setting->business;
        $locations = $business->locations;

        if ($locations->isEmpty()) {
            $error_message = "❌ No business locations found.";
            if ($message_id) {
                $this->editMessageAndRemoveKeyboard($chat_id, $message_id, $error_message, $telegram_setting);
            } else {
                $this->sendMessage($chat_id, $error_message, null, $telegram_setting);
            }
            return;
        }

        // Create session for add expense
        $session = TelegramBotSession::createOrUpdate(
            $telegram_setting->business_id,
            $chat_id,
            'add_expense',
            ['step' => 'location']
        );

        $message = "💵 *Add Expense*\n\nSelect a business location:";
        $keyboard = [];

        foreach ($locations as $location) {
            $keyboard[] = [['text' => "📍 {$location->name}", 'callback_data' => "expense_location_{$location->id}"]];
        }

        $keyboard[] = [['text' => '❌ Cancel', 'callback_data' => 'back_to_main']];

        if ($message_id) {
            // Edit the existing message
            $this->editMessageText($chat_id, $message_id, $message, $keyboard, $telegram_setting);
        } else {
            // Send new message
            $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
        }
    }

    /**
     * Handle dynamic callbacks
     */
    private function handleDynamicCallback($chat_id, $data, $telegram_setting, $message_id = null)
    {
        if (strpos($data, 'location_') === 0) {
            $location_id = str_replace('location_', '', $data);
            $this->showPeriodSelection($chat_id, $location_id, $telegram_setting, $message_id);
        } elseif (strpos($data, 'period_') === 0) {
            // Parse: period_locationid_period_name
            // Example: period_1_this_year becomes location=1, period=this_year
            $parts = explode('_', $data);
            $location_id = $parts[1];
            // Join the remaining parts back together to handle periods with underscores
            $period = implode('_', array_slice($parts, 2));
            
            $this->generateReport($chat_id, $location_id, $period, $telegram_setting, $message_id);
        } elseif (strpos($data, 'expense_location_') === 0) {
            $location_id = str_replace('expense_location_', '', $data);
            $this->handleExpenseLocationSelection($chat_id, $location_id, $telegram_setting);
        } elseif (strpos($data, 'expense_category_') === 0) {
            $category_id = str_replace('expense_category_', '', $data);
            $this->handleExpenseCategorySelection($chat_id, $category_id, $telegram_setting);
        } elseif ($data === 'expense_skip_category') {
            $this->handleExpenseCategorySelection($chat_id, null, $telegram_setting);
        } elseif (strpos($data, 'expense_') === 0) {
            // Handle expense workflow callbacks
            if ($data === 'expense_paid') {
                $this->handlePaymentStatus($chat_id, 'paid', $telegram_setting);
            } elseif ($data === 'expense_unpaid') {
                $this->handlePaymentStatus($chat_id, 'unpaid', $telegram_setting);
            } elseif ($data === 'expense_partial') {
                $this->handlePaymentStatus($chat_id, 'partial', $telegram_setting);
            } elseif ($data === 'expense_payment_cash') {
                $this->handlePaymentMethodSelection($chat_id, 'cash', $telegram_setting);
            } elseif ($data === 'expense_payment_bank') {
                $this->handlePaymentMethodSelection($chat_id, 'bank', $telegram_setting);
            } elseif ($data === 'expense_skip_document') {
                $this->handleSkipDocument($chat_id, $telegram_setting);
            }
        }
    }

    /**
     * Show period selection for reports
     */
    private function showPeriodSelection($chat_id, $location_id, $telegram_setting, $message_id = null)
    {
        $location = $telegram_setting->business->locations()->find($location_id);
        
        if (!$location) {
            $error_message = "❌ Location not found.";
            if ($message_id) {
                $this->editMessageAndRemoveKeyboard($chat_id, $message_id, $error_message, $telegram_setting);
            } else {
                $this->sendMessage($chat_id, $error_message, null, $telegram_setting);
            }
            return;
        }

        $message = "📅 *Reports - {$location->name}*\n\nSelect time period:";
        $keyboard = [
            [['text' => '📅 Today', 'callback_data' => "period_{$location_id}_today"]],
            [['text' => '📅 Yesterday', 'callback_data' => "period_{$location_id}_yesterday"]],
            [['text' => '📅 This Week', 'callback_data' => "period_{$location_id}_this_week"]],
            [['text' => '📅 This Month', 'callback_data' => "period_{$location_id}_this_month"]],
            [['text' => '📅 This Year', 'callback_data' => "period_{$location_id}_this_year"]],
            [['text' => '⬅️ Back to Reports', 'callback_data' => 'reports']]
        ];

        if ($message_id) {
            // Edit the existing message
            $this->editMessageText($chat_id, $message_id, $message, $keyboard, $telegram_setting);
        } else {
            // Send new message
            $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
        }
    }

    /**
     * Generate and send report
     */
    private function generateReport($chat_id, $location_id, $period, $telegram_setting, $message_id = null)
    {
        $location = $telegram_setting->business->locations()->find($location_id);
        
        if (!$location) {
            $error_message = "❌ Location not found.";
            if ($message_id) {
                $this->editMessageAndRemoveKeyboard($chat_id, $message_id, $error_message, $telegram_setting);
            } else {
                $this->sendMessage($chat_id, $error_message, null, $telegram_setting);
            }
            return;
        }

        // Calculate date range based on period
        $date_range = $this->getDateRange($period);
        
        
        // Get sales data
        $sales_data = $this->getSalesData($telegram_setting->business_id, $location_id, $date_range);
        
        // Get expense data
        $expense_data = $this->getExpenseData($telegram_setting->business_id, $location_id, $date_range);
        

        // Format report message
        $period_label = $this->getPeriodLabel($period);
        $message = "📊 *{$location->name} - {$period_label} Report*\n\n";
        
        // Check if there's any data
        $has_sales_data = $sales_data['total_sales'] > 0 || $sales_data['gross_profit'] > 0;
        $has_expense_data = $expense_data['total_expenses'] > 0;
        
        if (!$has_sales_data && !$has_expense_data) {
            $message .= "📭 *No Data Found*\n\n";
            $message .= "No transactions found for this period.\n";
            $message .= "Try selecting a different time period or check if transactions exist for this location.\n\n";
            $message .= "💡 *Tip:* Most recent activity might be in previous periods.";
        } else {
            $message .= "💰 *Sales Information:*\n";
            $message .= "• Total Sales: " . $this->formatCurrency($sales_data['total_sales']) . "\n";
            $message .= "• Gross Profit: " . $this->formatCurrency($sales_data['gross_profit']) . "\n";
            $message .= "• Net Profit: " . $this->formatCurrency($sales_data['net_profit']) . "\n\n";
            
            $message .= "💸 *Expenses:*\n";
            $message .= "• Total Expenses: " . $this->formatCurrency($expense_data['total_expenses']) . "\n\n";
            
            $message .= "📈 *Summary:*\n";
            $message .= "• Revenue: " . $this->formatCurrency($sales_data['total_sales']) . "\n";
            $message .= "• Profit (after expenses): " . $this->formatCurrency($sales_data['net_profit'] - $expense_data['total_expenses']) . "\n";
        }
        
        $keyboard = [
            [['text' => '⬅️ Back to Reports', 'callback_data' => 'reports']],
            [['text' => '🏠 Main Menu', 'callback_data' => 'back_to_main']]
        ];

        if ($message_id) {
            // Edit the existing message with the report
            $this->editMessageText($chat_id, $message_id, $message, $keyboard, $telegram_setting);
        } else {
            // Send new message
            $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
        }
    }

    /**
     * Handle expense location selection
     */
    private function handleExpenseLocationSelection($chat_id, $location_id, $telegram_setting)
    {
        $location = $telegram_setting->business->locations()->find($location_id);
        
        if (!$location) {
            $this->sendMessage($chat_id, "❌ Location not found.", null, $telegram_setting);
            return;
        }

        // Update session
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        if ($session) {
            $session->setSessionValue('location_id', $location_id);
            $session->setSessionValue('step', 'category');
        }

        // Show expense categories
        $categories = ExpenseCategory::where('business_id', $telegram_setting->business_id)->get();
        
        $message = "💵 *Add Expense - {$location->name}*\n\nSelect expense category or skip:";
        $keyboard = [
            [['text' => '⏭️ Skip (No Category)', 'callback_data' => 'expense_skip_category']]
        ];

        foreach ($categories->take(10) as $category) { // Limit to 10 categories to avoid too many buttons
            $keyboard[] = [['text' => $category->name, 'callback_data' => "expense_category_{$category->id}"]];
        }

        $keyboard[] = [['text' => '❌ Cancel', 'callback_data' => 'back_to_main']];

        $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
    }

    /**
     * Handle session actions (text responses during workflows)
     */
    private function handleSessionAction($session, $text, $message)
    {
        $telegram_setting = TelegramBotSetting::where('business_id', $session->business_id)->first();
        $chat_id = $session->chat_id;

        if ($session->current_action === 'add_expense') {
            $step = $session->getSessionValue('step');

            switch ($step) {
                case 'reference':
                    $this->handleExpenseReference($session, $text, $telegram_setting);
                    break;
                case 'amount':
                    $this->handleExpenseAmount($session, $text, $telegram_setting);
                    break;
                case 'note':
                    $this->handleExpenseNote($session, $text, $telegram_setting);
                    break;
                case 'partial_amount':
                    $this->handlePartialPaymentAmount($session, $text, $telegram_setting);
                    break;
                case 'document':
                    $this->handleExpenseDocument($session, $message, $telegram_setting);
                    break;
            }
        }
    }

    /**
     * Handle expense category selection
     */
    private function handleExpenseCategorySelection($chat_id, $category_id, $telegram_setting)
    {
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        if (!$session) {
            $this->showMainMenu($chat_id, $telegram_setting, "Session expired. Please start again.");
            return;
        }

        $session->setSessionValue('category_id', $category_id);
        $session->setSessionValue('step', 'reference');

        $message = "💵 *Add Expense - Step 2/7*\n\n";
        $message .= "📝 Enter reference number or type 'skip' to auto-generate:";

        $this->sendMessage($chat_id, $message, null, $telegram_setting);
    }

    /**
     * Handle expense reference
     */
    private function handleExpenseReference($session, $text, $telegram_setting)
    {
        $reference = trim($text);
        if (strtolower($reference) === 'skip') {
            $reference = 'EXP-' . date('YmdHis') . '-' . $session->business_id;
        }

        $session->setSessionValue('reference', $reference);
        $session->setSessionValue('step', 'amount');

        $message = "💵 *Add Expense - Step 3/7*\n\n";
        $message .= "💰 Enter the total expense amount (numbers only):";

        $this->sendMessage($session->chat_id, $message, null, $telegram_setting);
    }

    /**
     * Handle expense amount
     */
    private function handleExpenseAmount($session, $text, $telegram_setting)
    {
        $amount = trim($text);
        
        if (!is_numeric($amount) || $amount <= 0) {
            $message = "❌ Please enter a valid amount (numbers only):";
            $this->sendMessage($session->chat_id, $message, null, $telegram_setting);
            return;
        }

        $session->setSessionValue('amount', $amount);
        $session->setSessionValue('step', 'note');

        $message = "💵 *Add Expense - Step 4/7*\n\n";
        $message .= "📝 Enter expense note/description:";

        $this->sendMessage($session->chat_id, $message, null, $telegram_setting);
    }

    /**
     * Handle expense note
     */
    private function handleExpenseNote($session, $text, $telegram_setting)
    {
        $note = trim($text);
        $session->setSessionValue('note', $note);
        $session->setSessionValue('step', 'payment_status');

        $message = "💵 *Add Expense - Step 5/7*\n\n";
        $message .= "💳 Choose payment status:";

        $keyboard = [
            [['text' => '✅ Paid', 'callback_data' => 'expense_paid']],
            [['text' => '⏸️ Unpaid', 'callback_data' => 'expense_unpaid']],
            [['text' => '🔄 Partial Payment', 'callback_data' => 'expense_partial']],
            [['text' => '❌ Cancel', 'callback_data' => 'back_to_main']]
        ];

        $this->sendMessage($session->chat_id, $message, $keyboard, $telegram_setting);
    }

    /**
     * Handle payment status selection
     */
    private function handlePaymentStatus($chat_id, $status, $telegram_setting)
    {
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        if (!$session) {
            $this->showMainMenu($chat_id, $telegram_setting, "Session expired. Please start again.");
            return;
        }

        $session->setSessionValue('payment_status', $status);

        if ($status === 'partial') {
            $session->setSessionValue('step', 'partial_amount');
            $message = "💵 *Add Expense - Step 6/7*\n\n";
            $message .= "💰 Enter the amount already paid:";
            $this->sendMessage($chat_id, $message, null, $telegram_setting);
        } else {
            $this->showPaymentMethodSelection($session, $telegram_setting);
        }
    }

    /**
     * Handle partial payment amount
     */
    private function handlePartialPaymentAmount($session, $text, $telegram_setting)
    {
        $paid_amount = trim($text);
        $total_amount = $session->getSessionValue('amount');
        
        if (!is_numeric($paid_amount) || $paid_amount < 0 || $paid_amount > $total_amount) {
            $message = "❌ Please enter a valid paid amount (0 to {$total_amount}):";
            $this->sendMessage($session->chat_id, $message, null, $telegram_setting);
            return;
        }

        $session->setSessionValue('paid_amount', $paid_amount);
        $this->showPaymentMethodSelection($session, $telegram_setting);
    }

    /**
     * Show payment method selection
     */
    private function showPaymentMethodSelection($session, $telegram_setting)
    {
        $session->setSessionValue('step', 'payment_method');

        $message = "💵 *Add Expense - Step 6/7*\n\n";
        $message .= "💳 Select payment method:";

        $keyboard = [
            [['text' => '💵 Cash', 'callback_data' => 'expense_payment_cash']],
            [['text' => '🏦 Bank Transfer', 'callback_data' => 'expense_payment_bank']],
            [['text' => '❌ Cancel', 'callback_data' => 'back_to_main']]
        ];

        $this->sendMessage($session->chat_id, $message, $keyboard, $telegram_setting);
    }

    /**
     * Handle payment method selection
     */
    private function handlePaymentMethodSelection($chat_id, $method, $telegram_setting)
    {
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        if (!$session) {
            $this->showMainMenu($chat_id, $telegram_setting, "Session expired. Please start again.");
            return;
        }

        $session->setSessionValue('payment_method', $method);
        $session->setSessionValue('step', 'document');

        $message = "💵 *Add Expense - Step 7/7*\n\n";
        $message .= "📎 Would you like to attach a document? (PDF, PNG, JPEG, JPG)\n\n";
        $message .= "Send the file now or choose an option below:";

        $keyboard = [
            [['text' => '⏭️ Skip Document', 'callback_data' => 'expense_skip_document']],
            [['text' => '❌ Cancel', 'callback_data' => 'back_to_main']]
        ];

        $this->sendMessage($chat_id, $message, $keyboard, $telegram_setting);
    }

    /**
     * Handle expense document
     */
    private function handleExpenseDocument($session, $message, $telegram_setting)
    {
        // Check if message has document
        if (isset($message['document']) || isset($message['photo'])) {
            $file_data = null;
            $file_name = null;
            
            // Handle document
            if (isset($message['document'])) {
                $file_data = $message['document'];
                $file_name = $file_data['file_name'] ?? 'document.' . $this->getFileExtension($file_data['mime_type'] ?? 'application/octet-stream');
            } 
            // Handle photo
            elseif (isset($message['photo'])) {
                $file_data = end($message['photo']); // Get highest resolution photo
                $file_name = 'photo_' . time() . '.jpg';
            }
            
            if ($file_data && isset($file_data['file_id'])) {
                $downloaded_file = $this->downloadTelegramFile($file_data['file_id'], $file_name, $telegram_setting);
                if ($downloaded_file) {
                    $session->setSessionValue('has_document', true);
                    $session->setSessionValue('document_filename', $downloaded_file);
                    $this->createExpenseRecord($session, $telegram_setting);
                } else {
                    $this->sendMessage($session->chat_id, "❌ Failed to upload document. Please try again or skip.", null, $telegram_setting);
                }
            } else {
                $this->sendMessage($session->chat_id, "❌ Invalid file. Please send a valid document (PDF, PNG, JPEG, JPG) or skip.", null, $telegram_setting);
            }
        } else {
            $this->sendMessage($session->chat_id, "Please send a valid document (PDF, PNG, JPEG, JPG) or skip.", null, $telegram_setting);
        }
    }

    /**
     * Download file from Telegram servers
     */
    private function downloadTelegramFile($file_id, $original_filename, $telegram_setting)
    {
        try {
            // Get file info from Telegram
            $response = $this->client->get("https://api.telegram.org/bot{$telegram_setting->bot_token}/getFile", [
                'query' => ['file_id' => $file_id]
            ]);

            $file_info = json_decode($response->getBody()->getContents(), true);
            
            if (!$file_info['ok'] || !isset($file_info['result']['file_path'])) {
                \Log::error('Failed to get file info from Telegram', ['file_id' => $file_id]);
                return null;
            }

            $file_path = $file_info['result']['file_path'];
            
            // Download the file
            $file_url = "https://api.telegram.org/file/bot{$telegram_setting->bot_token}/{$file_path}";
            $file_content = $this->client->get($file_url)->getBody()->getContents();

            // Generate filename with timestamp prefix (matching existing system)
            $timestamp = time();
            $filename = $timestamp . '_' . $original_filename;
            
            // Ensure uploads/documents directory exists
            $upload_dir = public_path('uploads/documents');
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Save file
            $file_path = $upload_dir . '/' . $filename;
            file_put_contents($file_path, $file_content);

            \Log::info('File downloaded successfully', [
                'original_filename' => $original_filename,
                'saved_filename' => $filename,
                'file_size' => strlen($file_content)
            ]);

            return $filename;

        } catch (\Exception $e) {
            \Log::error('Error downloading Telegram file', [
                'file_id' => $file_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getFileExtension($mime_type)
    {
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt'
        ];

        return $extensions[$mime_type] ?? 'bin';
    }

    /**
     * Create the expense record
     */
    private function createExpenseRecord($session, $telegram_setting)
    {
        try {
            $expense_data = [
                'business_id' => $session->business_id,
                'location_id' => $session->getSessionValue('location_id'),
                'type' => 'expense',
                'status' => 'final',
                'payment_status' => $this->getPaymentStatus($session),
                'ref_no' => $session->getSessionValue('reference'),
                'transaction_date' => date('Y-m-d'),
                'total_before_tax' => $session->getSessionValue('amount'),
                'final_total' => $session->getSessionValue('amount'),
                'expense_category_id' => $session->getSessionValue('category_id'),
                'additional_notes' => $session->getSessionValue('note'),
                'created_by' => 1, // System user for bot transactions
            ];

            // Add document if uploaded
            if ($session->getSessionValue('has_document') && $session->getSessionValue('document_filename')) {
                $expense_data['document'] = $session->getSessionValue('document_filename');
            }

            $transaction = Transaction::create($expense_data);

            // Create payment record if paid
            if ($session->getSessionValue('payment_status') !== 'unpaid') {
                $this->createPaymentRecord($transaction, $session);
            }

            // Success message
            $location = $telegram_setting->business->locations()->find($session->getSessionValue('location_id'));
            $message = "✅ *Expense Added Successfully!*\n\n";
            $message .= "📍 Location: " . $this->escapeMarkdown($location->name) . "\n";
            $message .= "💰 Amount: " . $this->formatCurrency($session->getSessionValue('amount')) . "\n";
            $message .= "📝 Reference: " . $this->escapeMarkdown($session->getSessionValue('reference')) . "\n";
            $message .= "💳 Status: " . ucfirst($session->getSessionValue('payment_status')) . "\n";
            
            // Show document info if uploaded
            if ($session->getSessionValue('has_document') && $session->getSessionValue('document_filename')) {
                $document_name = $session->getSessionValue('document_filename');
                // Remove timestamp prefix for display
                $display_name = explode('_', $document_name, 2)[1] ?? $document_name;
                $message .= "📎 Document: " . $this->escapeMarkdown($display_name) . "\n";
            }
            
            $keyboard = [
                [['text' => '➕ Add Another Expense', 'callback_data' => 'add_expense']],
                [['text' => '🏠 Main Menu', 'callback_data' => 'back_to_main']]
            ];

            $this->sendMessage($session->chat_id, $message, $keyboard, $telegram_setting);

            // Clear session
            $session->clearSession();

        } catch (\Exception $e) {
            \Log::error('Error creating expense: ' . $e->getMessage());
            $this->sendMessage($session->chat_id, "❌ Error creating expense. Please try again.", null, $telegram_setting);
        }
    }

    /**
     * Get payment status for transaction
     */
    private function getPaymentStatus($session)
    {
        $status = $session->getSessionValue('payment_status');
        switch ($status) {
            case 'paid': return 'paid';
            case 'unpaid': return 'due';
            case 'partial': return 'partial';
            default: return 'due';
        }
    }

    /**
     * Create payment record
     */
    private function createPaymentRecord($transaction, $session)
    {
        $amount = $session->getSessionValue('payment_status') === 'partial' 
            ? $session->getSessionValue('paid_amount', 0)
            : $session->getSessionValue('amount');

        if ($amount > 0) {
            // Create payment record (simplified)
            // In real implementation, create proper payment with account details
        }
    }

    /**
     * Handle skip document
     */
    private function handleSkipDocument($chat_id, $telegram_setting)
    {
        $session = TelegramBotSession::where('business_id', $telegram_setting->business_id)
            ->where('chat_id', $chat_id)
            ->first();

        if (!$session) {
            $this->showMainMenu($chat_id, $telegram_setting, "Session expired. Please start again.");
            return;
        }

        $session->setSessionValue('has_document', false);
        $this->createExpenseRecord($session, $telegram_setting);
    }

    // Helper methods for data retrieval and formatting

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                $start = Carbon::now()->startOfDay();
                $end = Carbon::now()->endOfDay();
                return [$start, $end];
            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                return [$start, $end];
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                return [$start, $end];
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                return [$start, $end];
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                return [$start, $end];
            default:
                $start = Carbon::now()->startOfDay();
                $end = Carbon::now()->endOfDay();
                return [$start, $end];
        }
    }

    /**
     * Get period label
     */
    private function getPeriodLabel($period)
    {
        switch ($period) {
            case 'today': return 'Today';
            case 'yesterday': return 'Yesterday';
            case 'this_week': return 'This Week';
            case 'this_month': return 'This Month';
            case 'this_year': return 'This Year';
            default: return 'Today';
        }
    }

    /**
     * Get sales data for date range
     */
    private function getSalesData($business_id, $location_id, $date_range)
    {
        $transactions = Transaction::where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [
                $date_range[0]->toDateTimeString(),
                $date_range[1]->toDateTimeString()
            ])
            ->get();


        $total_sales = $transactions->sum('final_total');
        $gross_profit = $transactions->sum('gross_profit') ?? 0;
        $net_profit = $gross_profit; // Simplified calculation

        return [
            'total_sales' => $total_sales,
            'gross_profit' => $gross_profit,
            'net_profit' => $net_profit
        ];
    }

    /**
     * Get expense data for date range
     */
    private function getExpenseData($business_id, $location_id, $date_range)
    {
        $expenses = Transaction::where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $date_range[0]->toDateTimeString(),
                $date_range[1]->toDateTimeString()
            ])
            ->get();


        return [
            'total_expenses' => $expenses->sum('final_total')
        ];
    }

    /**
     * Format currency
     */
    private function formatCurrency($amount)
    {
        return 'MVR ' . number_format($amount, 2);
    }

    /**
     * Escape Markdown special characters for Telegram
     */
    private function escapeMarkdown($text)
    {
        if (empty($text)) {
            return $text;
        }
        
        // Escape special Markdown characters for Telegram
        $special_chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($special_chars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        
        return $text;
    }

    /**
     * Send message to Telegram
     */
    private function sendMessage($chat_id, $text, $keyboard = null, $telegram_setting = null)
    {
        \Log::info('Attempting to send message', [
            'chat_id' => $chat_id,
            'text_length' => strlen($text),
            'has_keyboard' => $keyboard ? true : false,
            'has_setting' => $telegram_setting ? true : false
        ]);

        if (!$telegram_setting || !$telegram_setting->bot_token) {
            \Log::error('No bot token available for sending message', [
                'has_setting' => $telegram_setting ? true : false,
                'has_token' => $telegram_setting && $telegram_setting->bot_token ? true : false
            ]);
            return;
        }

        try {
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ];

            if ($keyboard) {
                $data['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
                \Log::info('Adding keyboard to message', ['keyboard' => $keyboard]);
            }

            \Log::info('Sending to Telegram API', [
                'url' => "https://api.telegram.org/bot****/sendMessage",
                'data' => $data
            ]);

            $response = $this->client->post("https://api.telegram.org/bot{$telegram_setting->bot_token}/sendMessage", [
                'json' => $data
            ]);

            \Log::info('Telegram API response', [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send message with Reply Keyboard
     */
    private function sendMessageWithReplyKeyboard($chat_id, $text, $keyboard = null, $telegram_setting = null)
    {
        if (!$telegram_setting || !$telegram_setting->bot_token) {
            return;
        }

        try {
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ];

            if ($keyboard) {
                $data['reply_markup'] = json_encode([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'persistent' => true,
                    'one_time_keyboard' => false
                ]);
            }

            $response = $this->client->post("https://api.telegram.org/bot{$telegram_setting->bot_token}/sendMessage", [
                'json' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send Telegram message with reply keyboard', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit message and remove inline keyboard
     */
    private function editMessageAndRemoveKeyboard($chat_id, $message_id, $text, $telegram_setting)
    {
        if (!$telegram_setting || !$telegram_setting->bot_token) {
            return;
        }

        try {
            $data = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => []
                ])
            ];

            $response = $this->client->post("https://api.telegram.org/bot{$telegram_setting->bot_token}/editMessageText", [
                'json' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to edit Telegram message', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit message text with inline keyboard
     */
    private function editMessageText($chat_id, $message_id, $text, $keyboard = null, $telegram_setting = null)
    {
        if (!$telegram_setting || !$telegram_setting->bot_token) {
            return;
        }

        try {
            $data = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ];

            if ($keyboard) {
                $data['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
            }

            $response = $this->client->post("https://api.telegram.org/bot{$telegram_setting->bot_token}/editMessageText", [
                'json' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to edit Telegram message text', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Answer callback query
     */
    private function answerCallbackQuery($callback_query_id, $telegram_setting)
    {
        if (!$telegram_setting || !$telegram_setting->bot_token) {
            return;
        }

        try {
            $this->client->post("https://api.telegram.org/bot{$telegram_setting->bot_token}/answerCallbackQuery", [
                'json' => [
                    'callback_query_id' => $callback_query_id
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to answer callback query: ' . $e->getMessage());
        }
    }
}