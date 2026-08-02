<?php
require_once __DIR__ . '/includes/header.php';

$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$back = $_POST['back'] ?? $_GET['back'] ?? '/products.php';
if (!preg_match('#^/[^/].*#', $back)) $back = '/products.php';

if (!is_logged_in()) {
    // Requirement: must be logged in before adding to cart.
    redirect('/auth/login.php?next=' . urlencode($back));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT id, stock FROM modmypc WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        set_flash('error', 'That product could not be found.');
    } elseif ((int)$product['stock'] <= 0) {
        set_flash('error', 'Sorry, that product is currently out of stock.');
    } else {
        $cart_id = current_cart_id($conn);
        add_or_update_cart_item($conn, $cart_id, $product_id, $qty);
        log_activity($conn, $_SESSION['user_id'], 'cart_add', "product_id={$product_id} qty={$qty}");
        set_flash('success', 'Added to cart.');
    }
}

redirect($back);
