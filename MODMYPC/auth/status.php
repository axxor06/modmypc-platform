<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
header('Content-Type: application/json');

$user = current_user($conn);
$count = 0;
if ($user) {
    $cart_id = current_cart_id($conn);
    $count = get_cart_count($conn, $cart_id);
}

echo json_encode([
    'logged_in' => (bool)$user,
    'name' => $user['name'] ?? null,
    'cart_count' => $count,
]);
