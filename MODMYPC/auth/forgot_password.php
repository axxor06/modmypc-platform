<?php
$__page_title = 'Forgot Password | ModMyPC';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/mailer.php';

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (is_valid_email($email)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            $ins->bind_param('iss', $user['id'], $token_hash, $expires);
            $ins->execute();
            $ins->close();

            $link = SITE_URL . '/auth/reset_password.php?token=' . $token;
            $subject = 'Reset your ModMyPC password';
            $body = "Click the link below to reset your password. This link expires in 1 hour.\n\n{$link}\n\nIf you didn't request this, you can ignore this email.";
            send_email($email, $subject, $body);
            log_activity($conn, $user['id'], 'password_reset_requested', '');
        }
    }
    // Always show the same message, whether or not the account exists,
    // so this form can't be used to find out which emails are registered.
    $done = true;
}
?>
<div class="auth-wrap"><div class="auth-card" style="max-width:440px;">
    <h1 style="text-align:center;"><i class="fas fa-key" style="color:var(--primary);"></i> Forgot your password?</h1>
    <?php if ($done): ?>
      <p>If an account exists for that email, we've sent a password reset link. It expires in 1 hour.</p>
      <p class="muted">Didn't get it? Check spam, or <a href="/index.html">contact us on WhatsApp</a> for help — free hosting email delivery can occasionally be delayed.</p>
    <?php else: ?>
      <p class="muted">Enter your email and we'll send you a reset link.</p>
      <form method="POST">
        <?php echo csrf_field(); ?>
        <label>Email</label>
        <input type="email" name="email" required>
        <button type="submit" class="btn" style="margin-top:18px; width:100%;">Send Reset Link</button>
      </form>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
