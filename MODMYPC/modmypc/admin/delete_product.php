<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

// Deletion is destructive, so it must be a POST request with a valid
// CSRF token — a bare GET link would be vulnerable to CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php"); exit;
}
csrf_verify();

$id = intval($_POST['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("SELECT name FROM modmypc WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($p) {
        $del = $conn->prepare("DELETE FROM modmypc WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Product "' . htmlspecialchars($p['name']) . '" deleted.'];
    }
}
header("Location: products.php"); exit;
