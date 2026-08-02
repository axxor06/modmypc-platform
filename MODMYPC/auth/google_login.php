<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty(GOOGLE_CLIENT_ID)) {
    // Not configured yet - fail safe rather than sending the user into a broken flow.
    http_response_code(503);
    die('Google Sign-In is not configured yet. Please use email/password login.');
}

// CSRF-style "state" param to prevent login CSRF on the callback.
if (session_status() === PHP_SESSION_NONE) session_start();
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_next'] = $_GET['next'] ?? '/index.html';

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
