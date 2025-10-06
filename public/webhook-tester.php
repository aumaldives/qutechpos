<?php
/**
 * IsleBooks Webhook Tester
 * 
 * A simple webhook endpoint for testing IsleBooks webhooks.
 * Place this file on any web server to test webhook deliveries.
 */

// Log file path (make sure this directory is writable)
$logFile = __DIR__ . '/webhook-logs.txt';

// Get request details
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$rawPayload = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');

// Parse JSON payload
$payload = json_decode($rawPayload, true);
$isValidJson = json_last_error() === JSON_ERROR_NONE;

// Verify webhook signature (optional but recommended)
$webhookSecret = 'your-webhook-secret-here'; // Replace with actual secret
$signatureValid = false;

if (isset($headers['X-Webhook-Signature']) && !empty($webhookSecret)) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawPayload, $webhookSecret);
    $signatureValid = hash_equals($expectedSignature, $headers['X-Webhook-Signature']);
}

// Log the webhook request
$logEntry = [
    'timestamp' => $timestamp,
    'method' => $method,
    'headers' => $headers,
    'payload_size' => strlen($rawPayload),
    'is_valid_json' => $isValidJson,
    'signature_valid' => $signatureValid,
    'payload' => $payload,
    'raw_payload' => $rawPayload
];

// Write to log file
file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND | LOCK_EX);

// Response based on request type
if ($method === 'POST' && $isValidJson) {
    // Webhook event received
    $eventType = $payload['event'] ?? 'unknown';
    $eventId = $payload['id'] ?? 'unknown';
    
    // Log specific event details
    error_log("IsleBooks Webhook Received: {$eventType} ({$eventId})");
    
    // Return success response
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Webhook received successfully',
        'event' => $eventType,
        'id' => $eventId,
        'processed_at' => $timestamp,
        'signature_verified' => $signatureValid
    ]);
    
} elseif ($method === 'GET') {
    // Display simple status page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>IsleBooks Webhook Tester</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow: auto; }
        </style>
    </head>
    <body>
        <h1>IsleBooks Webhook Tester</h1>
        
        <div class="status success">
            ✅ Webhook endpoint is ready and accessible
        </div>
        
        <div class="status info">
            📝 POST your IsleBooks webhooks to this URL to test delivery
        </div>
        
        <h3>Recent Webhook Logs</h3>
        <pre><?php
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            echo htmlspecialchars(substr($logs, -2000)); // Show last 2KB
        } else {
            echo "No webhook logs found yet.";
        }
        ?></pre>
        
        <h3>Testing Instructions</h3>
        <ol>
            <li>Copy this URL: <code><?php echo "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></code></li>
            <li>Go to your IsleBooks admin → Integrations → Webhooks</li>
            <li>Create a new webhook with this URL</li>
            <li>Test the webhook or trigger events in IsleBooks</li>
            <li>Refresh this page to see the received webhooks</li>
        </ol>
        
        <p><small>Last updated: <?php echo $timestamp; ?></small></p>
    </body>
    </html>
    <?php
    
} else {
    // Invalid request
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Expected JSON POST.',
        'method' => $method,
        'content_type' => $headers['Content-Type'] ?? 'unknown',
        'payload_valid' => $isValidJson
    ]);
}

// Helper function for older PHP versions
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
?>