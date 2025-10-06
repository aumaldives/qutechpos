<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://islebooks.mv');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Accept, Content-Type');

// Start session to check if user is logged in
session_start();

// Check if user is authenticated
$isLoggedIn = false;
$debugInfo = [];

// Laravel session cookie check (most reliable for Laravel apps)
if (isset($_COOKIE['laravel_session']) && !empty($_COOKIE['laravel_session'])) {
    $isLoggedIn = true;
    $debugInfo['laravel_session'] = 'present';
}

// Check for other common session indicators
$sessionChecks = [
    'user_id' => isset($_SESSION['user_id']),
    'authenticated' => isset($_SESSION['authenticated']),
    'login' => isset($_SESSION['login']),
    'business_id' => isset($_SESSION['business_id']),
    'user' => isset($_SESSION['user']),
    'auth' => isset($_SESSION['auth']),
    'loggedIn' => isset($_SESSION['loggedIn']),
];

foreach ($sessionChecks as $key => $exists) {
    if ($exists) {
        $isLoggedIn = true;
        $debugInfo[$key] = 'present';
    }
}

// Additional cookie checks
$cookieChecks = [
    'remember_token' => isset($_COOKIE['remember_token']),
    'auth_token' => isset($_COOKIE['auth_token']),
    'pos_session' => isset($_COOKIE['pos_session']),
];

foreach ($cookieChecks as $key => $exists) {
    if ($exists) {
        $isLoggedIn = true;
        $debugInfo[$key] = 'present';
    }
}

// Return JSON response with debug info
echo json_encode([
    'authenticated' => $isLoggedIn,
    'timestamp' => date('Y-m-d H:i:s'),
    'debug' => $debugInfo,
    'session_id' => session_id()
]);
?>