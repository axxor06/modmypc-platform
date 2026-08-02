<?php
require_once __DIR__ . '/includes/header.php';

$message = trim($_POST['message'] ?? $_GET['message'] ?? 'Hi ModMyPC, I have a question.');
$source = trim($_POST['source'] ?? $_GET['source'] ?? 'website');
$back = $_POST['back'] ?? $_GET['back'] ?? '/index.html';
if (!preg_match('#^/[^/].*#', $back)) $back = '/index.html';

if (!is_logged_in()) {
    redirect('/auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

$user = current_user($conn);
$stmt = $conn->prepare("INSERT INTO enquiries (user_id, name, phone, email, message, source, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('isssss', $user['id'], $user['name'], $user['phone'], $user['email'], $message, $source);
$stmt->execute();
$stmt->close();
log_activity($conn, $user['id'], 'whatsapp_enquiry', $source);

redirect('https://wa.me/' . WHATSAPP_PHONE . '?text=' . rawurlencode($message));
