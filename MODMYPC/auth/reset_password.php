<?php
$__page_title = 'Reset Password | ModMyPC';
require_once __DIR__ . '/../includes/header.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = null;
$success = false;
$reset = null;

if ($token === '') {
    $error = 'Missing reset token.';
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT id, user_id FROM password_resets
         WHERE token_hash = ? AND used = 0 AND expires_at > NOW() LIMIT 1"
    );
    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $u->bind_param('si', $hash, $reset['user_id']);
            $u->execute();
            $u->close();

            $m = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $m->bind_param('i', $reset['id']);
            $m->execute();
            $m->close();

            log_activity($conn, $reset['user_id'], 'password_reset_completed', '');
            $success = true;
        }
    }
}
?>
<div class="auth-wrap"><div class="auth-card" style="max-width:440px;">
    <h1 style="text-align:center;"><i class="fas fa-lock" style="color:var(--primary);"></i> Reset Password</h1>
    <?php if ($success): ?>
      <p>Your password has been reset. You can now <a href="/auth/login.php">log in</a>.</p>
    <?php else: ?>
      <?php if ($error): ?><div class="site-flash flash-error"><?php echo e($error); ?></div><?php endif; ?>
      <?php if ($reset): ?>
      <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo e($token); ?>">
        <label>New Password</label>
        <div class="password-field">
          <input type="password" name="new_password" id="pw1" required minlength="8">
          <span class="password-toggle fas fa-eye" onclick="const i=document.getElementById('pw1'); i.type = i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"></span>
        </div>
        <label>Confirm New Password</label>
        <div class="password-field">
          <input type="password" name="confirm_password" id="pw2" required minlength="8">
          <span class="password-toggle fas fa-eye" onclick="const i=document.getElementById('pw2'); i.type = i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"></span>
        </div>
        <button type="submit" class="btn" style="margin-top:18px; width:100%;">Reset Password</button>
      </form>
      <?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
