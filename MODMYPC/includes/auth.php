<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/** Is a customer currently logged in? */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/** Get the logged-in user's row, or null. Cached per-request. */
function current_user($conn) {
    static $cached = null;
    if (!is_logged_in()) return null;
    if ($cached !== null) return $cached;
    $stmt = $conn->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $cached = $res->fetch_assoc() ?: null;
    $stmt->close();
    return $cached;
}

/** Force login before continuing; remembers where to return to after login. */
function require_login($redirect_after = null) {
    if (!is_logged_in()) {
        $back = $redirect_after ?? ($_SERVER['REQUEST_URI'] ?? '/');
        redirect('/auth/login.php?next=' . urlencode($back));
    }
}

/**
 * Check whether a given identifier (email, for customers) is currently
 * locked out due to too many failed attempts. Returns seconds remaining
 * if locked, or 0 if not locked.
 */
function login_lockout_remaining($conn, $identifier, $scope = 'customer') {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as c, MAX(attempted_at) as last_try FROM login_attempts
         WHERE identifier = ? AND scope = ? AND success = 0
         AND attempted_at > (NOW() - INTERVAL ? MINUTE)"
    );
    $mins = LOGIN_LOCKOUT_MINUTES;
    $stmt->bind_param('ssi', $identifier, $scope, $mins);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ((int)$row['c'] >= LOGIN_MAX_ATTEMPTS) {
        $elapsed = time() - strtotime($row['last_try']);
        $remaining = ($mins * 60) - $elapsed;
        return max(0, $remaining);
    }
    return 0;
}

/** Record a login attempt (success or failure) for rate limiting + auditing. */
function record_login_attempt($conn, $identifier, $success, $scope = 'customer') {
    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, scope, success, ip_address, attempted_at) VALUES (?, ?, ?, ?, NOW())");
    $ip = client_ip();
    $succ = $success ? 1 : 0;
    $stmt->bind_param('ssis', $identifier, $scope, $succ, $ip);
    $stmt->execute();
    $stmt->close();
}

/** Clear failed attempts after a successful login. */
function clear_login_attempts($conn, $identifier, $scope = 'customer') {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ? AND scope = ?");
    $stmt->bind_param('ss', $identifier, $scope);
    $stmt->execute();
    $stmt->close();
}

/**
 * Register a new customer account.
 * Returns ['ok' => bool, 'error' => string|null]
 */
function register_user($conn, $name, $email, $phone, $password) {
    $name = trim($name);
    $email = strtolower(trim($email));
    $phone = trim($phone);

    if ($name === '' || strlen($name) > 100) {
        return ['ok' => false, 'error' => 'Please enter your full name.'];
    }
    if (!is_valid_email($email)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, email_verified, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param('ssss', $name, $email, $phone, $hash);
    if (!$stmt->execute()) {
        // Fall back for a database that hasn't run the email_verified migration yet.
        $stmt2 = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt2->bind_param('ssss', $name, $email, $phone, $hash);
        if (!$stmt2->execute()) {
            $stmt2->close();
            $stmt->close();
            return ['ok' => false, 'error' => 'Could not create account. Please try again.'];
        }
        $insert_id = $stmt2->insert_id;
        $stmt2->close();
        $stmt->close();
        log_activity($conn, $insert_id, 'register', 'Account created');
        return ['ok' => true, 'user_id' => $insert_id];
    }
    $user_id = $stmt->insert_id;
    $stmt->close();

    log_activity($conn, $user_id, 'register', 'Account created');
    return ['ok' => true, 'user_id' => $user_id];
}

/**
 * Attempt customer login. Handles rate limiting, password verification,
 * session regeneration, and merging the guest DB cart (if any) into the
 * user's cart.
 * Returns ['ok' => bool, 'error' => string|null]
 */
function attempt_login($conn, $email, $password) {
    $email = strtolower(trim($email));

    $remaining = login_lockout_remaining($conn, $email, 'customer');
    if ($remaining > 0) {
        $mins = ceil($remaining / 60);
        return ['ok' => false, 'error' => "Too many failed attempts. Please try again in about {$mins} minute(s)."];
    }

    $stmt = $conn->prepare("SELECT id, password_hash, name, email, email_verified FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        // email_verified column doesn't exist yet (migration not re-run) - fall back.
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user) $user['email_verified'] = 1; // treat as verified until migration adds the real column
    } else {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($conn, $email, false, 'customer');
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    record_login_attempt($conn, $email, true, 'customer');
    clear_login_attempts($conn, $email, 'customer');

    if ((int)$user['email_verified'] === 0) {
        // Correct password, but they never finished email verification.
        // Don't grant a session - send them back through OTP verification.
        return ['ok' => false, 'needs_verification' => true, 'user_id' => (int)$user['id']];
    }

    // Prevent session fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];

    // Rehash transparently if PHP's default algorithm/cost has changed.
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $u = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $u->bind_param('si', $new_hash, $user['id']);
        $u->execute();
        $u->close();
    }

    merge_guest_cart_into_user($conn, (int)$user['id']);
    log_activity($conn, (int)$user['id'], 'login', 'Logged in');

    return ['ok' => true];
}

function logout_user() {
    $uid = $_SESSION['user_id'] ?? null;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Merge a guest's session-based cart (stored server-side keyed by a guest
 * token cookie) into their DB cart at login time, so nothing is lost.
 */
function merge_guest_cart_into_user($conn, $user_id) {
    if (empty($_SESSION['guest_cart_token'])) return;
    $guest_token = $_SESSION['guest_cart_token'];

    $cart_id = get_or_create_cart($conn, $user_id, null);

    $stmt = $conn->prepare(
        "SELECT ci.product_id, ci.quantity FROM cart_items ci
         JOIN carts c ON c.id = ci.cart_id
         WHERE c.guest_token = ? AND c.user_id IS NULL"
    );
    $stmt->bind_param('s', $guest_token);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $row) {
        add_or_update_cart_item($conn, $cart_id, (int)$row['product_id'], (int)$row['quantity']);
    }

    // Clean up the now-merged guest cart.
    $del = $conn->prepare("DELETE c, ci FROM carts c LEFT JOIN cart_items ci ON ci.cart_id = c.id WHERE c.guest_token = ? AND c.user_id IS NULL");
    $del->bind_param('s', $guest_token);
    $del->execute();
    $del->close();

    unset($_SESSION['guest_cart_token']);
}
