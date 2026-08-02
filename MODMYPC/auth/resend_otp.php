<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/otp.php';

if (empty($_SESSION['pending_verification_user_id'])) {
    redirect('/auth/register.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/auth/verify_email.php');
}
csrf_verify();

$pending_id = (int)$_SESSION['pending_verification_user_id'];

// Basic abuse guard: don't allow resending more than once every 45 seconds.
$stmt = $conn->prepare("SELECT created_at FROM email_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $pending_id);
$stmt->execute();
$last = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($last && (time() - strtotime($last['created_at'])) < 45) {
    set_flash('error', 'Please wait a moment before requesting another code.');
    redirect('/auth/verify_email.php');
}

$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $pending_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    generate_and_send_otp($conn, $pending_id, $user['email'], $user['name']);
    set_flash('success', 'A new code has been sent to your email.');
}
redirect('/auth/verify_email.php');
