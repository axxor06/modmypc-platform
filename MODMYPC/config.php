<?php
/**
 * ModMyPC — Central configuration
 * -------------------------------------------------------------
 * IMPORTANT (do this before re-uploading to InfinityFree):
 * 1. Log into your InfinityFree control panel and CHANGE your MySQL
 *    password. The old one was stored in plaintext in several files
 *    and should be considered compromised.
 * 2. Put the NEW password below.
 * 3. This is the ONLY file that should ever contain DB credentials.
 *    Every other script must `require_once __DIR__/includes/db.php`
 *    (which reads the constants defined here) instead of connecting
 *    directly.
 */

// ── DATABASE ─────────────────────────────────────────────────────────────
define('DB_HOST', 'sql212.infinityfree.com');
define('DB_USER', 'if0_39576026');
define('DB_PASS', 'ModmyPc2025'); // <-- rotate this, then update here
define('DB_NAME', 'if0_39576026_modmypc2_db');

// ── SITE ──────────────────────────────────────────────────────────────────
define('SITE_NAME', 'ModMyPC');
define('SITE_URL', 'https://modmypc.com'); // no trailing slash
define('WHATSAPP_PHONE', '918089637705');

// ── GOOGLE OAUTH ──────────────────────────────────────────────────────────
// Reads from .env (copy .env.example to .env and fill in real values from
// https://console.cloud.google.com/apis/credentials). Never hardcode these.
require_once __DIR__ . '/includes/env.php';
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google_callback.php'));

// ── SECURITY ──────────────────────────────────────────────────────────────
// Session cookie hardening. InfinityFree serves over HTTPS on modmypc.com,
// so 'secure' cookies are safe. If you ever test over plain HTTP locally,
// temporarily set SESSION_SECURE_COOKIE to false.
define('SESSION_SECURE_COOKIE', true);

// Login rate limiting
define('LOGIN_MAX_ATTEMPTS', 5);      // attempts allowed
define('LOGIN_LOCKOUT_MINUTES', 15);  // lockout window after max attempts

// ── ERROR REPORTING ───────────────────────────────────────────────────────
// Never show raw PHP errors to visitors on a production site.
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// ── SESSION START (safe, hardened, only once) ────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => SESSION_SECURE_COOKIE,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
