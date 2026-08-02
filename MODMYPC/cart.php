<?php
$__page_title = 'Your Cart | ModMyPC';
require_once __DIR__ . '/includes/header.php';
require_login('/cart.php');

$user = current_user($conn);
$cart_id = current_cart_id($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'update' && $product_id > 0) {
        $qty = max(0, (int)($_POST['quantity'] ?? 1));
        set_cart_item_qty($conn, $cart_id, $product_id, $qty);
    } elseif ($action === 'remove' && $product_id > 0) {
        remove_cart_item($conn, $cart_id, $product_id);
    } elseif ($action === 'checkout_whatsapp') {
        $items_now = get_cart_items($conn, $cart_id);
        $total_now = 0;
        $lines = [];
        foreach ($items_now as $it) {
            $total_now += $it['price'] * $it['quantity'];
            $lines[] = "{$it['name']} x{$it['quantity']}";
        }
        $summary = implode(', ', $lines);
        $ins = $conn->prepare("INSERT INTO enquiries (user_id, name, phone, message, source, created_at) VALUES (?, ?, ?, ?, 'cart_checkout', NOW())");
        $ins->bind_param('isss', $user['id'], $user['name'], $user['phone'], $summary);
        $ins->execute();
        $ins->close();
        log_activity($conn, $user['id'], 'whatsapp_checkout', $summary);

        $msg = "Hi ModMyPC, I'd like to order:\n";
        foreach ($items_now as $it) $msg .= "- {$it['name']} x{$it['quantity']} (" . number_format($it['price']*$it['quantity']) . ")\n";
        $msg .= "Total: Rs " . number_format($total_now);
        redirect('https://wa.me/' . WHATSAPP_PHONE . '?text=' . rawurlencode($msg));
    }
    redirect('/cart.php');
}

$items = get_cart_items($conn, $cart_id);
$total = 0;
foreach ($items as $it) $total += $it['price'] * $it['quantity'];
?>
<div class="container" style="max-width:800px;">
  <div class="card">
    <h1>Your Cart</h1>
    <?php render_flash(); ?>
    <?php if (empty($items)): ?>
      <div class="empty-cart">
        <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-10 0a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
        <h2>Your cart feels a little light</h2>
        <p>Nothing here yet — let's fix that.</p>
        <a href="/products.php" class="btn">Browse Products</a>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr class="cart-item-row">
            <td><?php echo e($it['name']); ?><?php if ($it['stock'] <= 0): ?><br><span class="stock-tag low-stock">Out of stock</span><?php endif; ?></td>
            <td><?php echo money($it['price']); ?></td>
            <td>
              <form method="POST" style="display:flex; gap:6px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" value="<?php echo (int)$it['product_id']; ?>">
                <input type="text" inputmode="numeric" name="quantity" value="<?php echo (int)$it['quantity']; ?>" class="qty-input" style="width:60px; padding:6px;">
                <button type="submit" class="btn btn-sm btn-outline">Update</button>
              </form>
            </td>
            <td><?php echo money($it['price'] * $it['quantity']); ?></td>
            <td>
              <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?php echo (int)$it['product_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <h2 style="text-align:right; margin-top:20px;">Total: <?php echo money($total); ?></h2>
      <div style="text-align:right;">
        <form method="POST" target="_blank">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="checkout_whatsapp">
          <button type="submit" class="btn" style="background:#25D366;">
            <i class="fab fa-whatsapp"></i> Checkout via WhatsApp
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
