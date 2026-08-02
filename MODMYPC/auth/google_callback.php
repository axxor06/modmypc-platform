<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    http_response_code(503);
    die('Google Sign-In is not configured yet.');
}

// Validate state to prevent CSRF on the OAuth callback.
$state = $_GET['state'] ?? '';
if (empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
    set_flash('error', 'Google sign-in session expired. Please try again.');
    redirect('/auth/login.php');
}
unset($_SESSION['google_oauth_state']);
$next = $_SESSION['google_oauth_next'] ?? '/index.html';
unset($_SESSION['google_oauth_next']);

if (!empty($_GET['error']) || empty($_GET['code'])) {
    set_flash('error', 'Google sign-in was cancelled or failed.');
    redirect('/auth/login.php');
}

// Exchange the authorization code for an access token.
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_TIMEOUT => 10,
]);
$tokenResponse = curl_exec($ch);
$tokenError = curl_error($ch);
curl_close($ch);

if ($tokenError || !$tokenResponse) {
    error_log('Google OAuth token exchange failed: ' . $tokenError);
    set_flash('error', 'Could not reach Google. Please try again.');
    redirect('/auth/login.php');
}

$tokenData = json_decode($tokenResponse, true);
if (empty($tokenData['access_token'])) {
    error_log('Google OAuth token response missing access_token: ' . $tokenResponse);
    set_flash('error', 'Google sign-in failed. Please try again.');
    redirect('/auth/login.php');
}

// Fetch the user's profile.
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_TIMEOUT => 10,
]);
$profileResponse = curl_exec($ch);
curl_close($ch);
$profile = json_decode($profileResponse, true);

if (empty($profile['sub']) || empty($profile['email'])) {
    set_flash('error', 'Could not read your Google profile. Please try again.');
    redirect('/auth/login.php');
}

$google_id = $profile['sub'];
$email = strtolower($profile['email']);
$name = $profile['name'] ?? explode('@', $email)[0];

// Match existing account by google_id first, then by email (to link an
// existing email/password account the first time they use Google Sign-In).
$stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ? LIMIT 1");
$stmt->bind_param('s', $google_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Link Google to the existing account.
        $u = $conn->prepare("UPDATE users SET google_id = ?, email_verified = 1 WHERE id = ?");
        $u->bind_param('si', $google_id, $user['id']);
        $u->execute();
        $u->close();
    } else {
        // Brand new account. Google already verified this email, so we can
        // mark it verified and skip our own OTP step. No password is set;
        // the "Forgot Password" flow can't be used until one is - that's
        // fine, since they'll always sign in via Google.
        $random_password_hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, google_id, email_verified, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param('ssss', $name, $email, $random_password_hash, $google_id);
        $stmt->execute();
        $user = ['id' => $stmt->insert_id];
        $stmt->close();
        log_activity($conn, $user['id'], 'register_google', 'Signed up via Google');
    }
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
merge_guest_cart_into_user($conn, (int)$user['id']);
log_activity($conn, (int)$user['id'], 'login_google', '');
set_flash('success', 'Signed in with Google.');
redirect($next);
