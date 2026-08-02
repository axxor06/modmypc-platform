<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/auth.php'; // for login_lockout_remaining/record/clear (shared login_attempts table)

function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** Attempt admin login against the `admins` table (hashed passwords). */
function attempt_admin_login($conn, $username, $password) {
    $username = trim($username);

    $remaining = login_lockout_remaining($conn, $username, 'admin');
    if ($remaining > 0) {
        $mins = ceil($remaining / 60);
        return ['ok' => false, 'error' => "Too many failed attempts. Try again in about {$mins} minute(s)."];
    }

    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        record_login_attempt($conn, $username, false, 'admin');
        return ['ok' => false, 'error' => 'Invalid username or password.'];
    }

    record_login_attempt($conn, $username, true, 'admin');
    clear_login_attempts($conn, $username, 'admin');

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_username'] = $username;

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $u = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $u->bind_param('si', $new_hash, $admin['id']);
        $u->execute();
        $u->close();
    }

    log_activity($conn, null, 'admin_login', "admin:{$username}");
    return ['ok' => true];
}

function admin_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
