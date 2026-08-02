<?php
require_once __DIR__ . '/auth.php';

/**
 * Get (or create) the cart row for the current visitor.
 * Logged-in users: cart is keyed by user_id, so it's the same cart on
 * every device they log into.
 * Guests: cart is keyed by a random guest_token stored in their session
 * (and mirrored in a cookie) so it survives a page refresh, but is only
 * merged into a permanent account cart once they log in.
 */
function get_or_create_cart($conn, $user_id = null, $unused = null) {
    if ($user_id) {
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (int)$row['id'];

        $ins = $conn->prepare("INSERT INTO carts (user_id, created_at) VALUES (?, NOW())");
        $ins->bind_param('i', $user_id);
        $ins->execute();
        $id = $ins->insert_id;
        $ins->close();
        return (int)$id;
    }

    // Guest path
    if (empty($_SESSION['guest_cart_token'])) {
        $_SESSION['guest_cart_token'] = bin2hex(random_bytes(16));
    }
    $token = $_SESSION['guest_cart_token'];

    $stmt = $conn->prepare("SELECT id FROM carts WHERE guest_token = ? AND user_id IS NULL LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) return (int)$row['id'];

    $ins = $conn->prepare("INSERT INTO carts (guest_token, created_at) VALUES (?, NOW())");
    $ins->bind_param('s', $token);
    $ins->execute();
    $id = $ins->insert_id;
    $ins->close();
    return (int)$id;
}

/** Get the current visitor's cart id (logged-in or guest). */
function current_cart_id($conn) {
    if (is_logged_in()) {
        return get_or_create_cart($conn, (int)$_SESSION['user_id']);
    }
    return get_or_create_cart($conn, null);
}

/** Add a product to a cart, or increase quantity if it's already in there. */
function add_or_update_cart_item($conn, $cart_id, $product_id, $qty = 1) {
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
    $stmt->bind_param('ii', $cart_id, $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $new_qty = (int)$row['quantity'] + $qty;
        $u = $conn->prepare("UPDATE cart_items SET quantity = ?, added_at = NOW() WHERE id = ?");
        $u->bind_param('ii', $new_qty, $row['id']);
        $u->execute();
        $u->close();
    } else {
        $i = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
        $i->bind_param('iii', $cart_id, $product_id, $qty);
        $i->execute();
        $i->close();
    }
}

function set_cart_item_qty($conn, $cart_id, $product_id, $qty) {
    if ($qty <= 0) {
        remove_cart_item($conn, $cart_id, $product_id);
        return;
    }
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param('iii', $qty, $cart_id, $product_id);
    $stmt->execute();
    $stmt->close();
}

function remove_cart_item($conn, $cart_id, $product_id) {
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param('ii', $cart_id, $product_id);
    $stmt->execute();
    $stmt->close();
}

/** Full cart contents with live product data (name/price/stock/image). */
function get_cart_items($conn, $cart_id) {
    $stmt = $conn->prepare(
        "SELECT ci.product_id, ci.quantity, p.name, p.price, p.stock, p.category, COALESCE(p.image,'') AS image
         FROM cart_items ci
         JOIN modmypc p ON p.id = ci.product_id
         WHERE ci.cart_id = ?
         ORDER BY ci.added_at DESC"
    );
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Total number of items (for the little badge in the nav). */
function get_cart_count($conn, $cart_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS c FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['c'];
}

function get_cart_total($conn, $cart_id) {
    $items = get_cart_items($conn, $cart_id);
    $total = 0;
    foreach ($items as $it) $total += $it['price'] * $it['quantity'];
    return $total;
}
