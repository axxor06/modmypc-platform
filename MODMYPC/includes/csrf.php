<?php
/**
 * CSRF protection helpers.
 * Usage in a form:  <?php echo csrf_field(); ?>
 * Usage on submit:   csrf_verify();  // dies with 403 if invalid/missing
 */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify() {
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        die('Security check failed (invalid or expired form token). Please go back, refresh the page, and try again.');
    }
}
