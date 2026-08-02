<?php
/** Escape for safe HTML output. */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** Format a rupee amount. */
function money($n) {
    return '&#8377;' . number_format((float)$n);
}

/** Redirect and stop execution. */
function redirect($path) {
    header('Location: ' . $path);
    exit;
}

/** Basic validated email check. */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Set a one-time flash message shown on the next page load. */
function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/** Pop and return the flash message, or null. */
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function render_flash() {
    $f = get_flash();
    if (!$f) return;
    $cls = $f['type'] === 'success' ? 'flash-success' : ($f['type'] === 'error' ? 'flash-error' : 'flash-info');
    echo '<div class="site-flash ' . e($cls) . '">' . e($f['msg']) . '</div>';
}

/** Truncate text safely without requiring the mbstring extension. */
function truncate_text($str, $len = 80, $suffix = '...') {
    $str = (string)$str;
    if (strlen($str) <= $len) return $str;
    return substr($str, 0, $len) . $suffix;
}

/** Get the client IP as best as InfinityFree's proxy setup allows. */
function client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Log an activity row (best-effort; failure here should never break the page). */
function log_activity($conn, $user_id, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) return;
    $ip = client_ip();
    $stmt->bind_param('isss', $user_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
