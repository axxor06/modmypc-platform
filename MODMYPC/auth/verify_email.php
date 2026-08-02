<?php
$__page_title = 'Verify Your Email | ModMyPC';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/otp.php';

if (is_logged_in()) redirect('/index.html');

if (empty($_SESSION['pending_verification_user_id'])) {
    redirect('/auth/register.php');
}
$pending_id = (int)$_SESSION['pending_verification_user_id'];

$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $pending_id);
$stmt->execute();
$pending_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pending_user) {
    unset($_SESSION['pending_verification_user_id']);
    redirect('/auth/register.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $code = trim($_POST['otp'] ?? '');
    if (verify_otp($conn, $pending_id, $code)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $pending_id;
        $next = $_SESSION['pending_verification_next'] ?? '/index.html';
        unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_next']);
        merge_guest_cart_into_user($conn, $pending_id);
        log_activity($conn, $pending_id, 'email_verified', '');
        set_flash('success', 'Email verified — welcome to ModMyPC, ' . $pending_user['name'] . '!');
        redirect($next);
    } else {
        $error = 'That code is incorrect or has expired. You can request a new one below.';
    }
}
?>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:420px;">
    <h1 style="text-align:center;"><i class="fas fa-envelope-circle-check" style="color:var(--primary);"></i> Verify Your Email</h1>
    <p class="muted" style="text-align:center;">We sent a 6-digit code to <strong><?php echo e($pending_user['email']); ?></strong>. Enter it below to finish creating your account.</p>
    <?php if ($error): ?><div class="site-flash flash-error"><?php echo e($error); ?></div><?php endif; ?>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <label>Verification Code</label>
      <input type="text" name="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus style="letter-spacing:6px; font-size:22px; text-align:center;">
      <button type="submit" class="btn" style="margin-top:20px; width:100%;">Verify & Continue</button>
    </form>
    <form method="POST" action="/auth/resend_otp.php" style="margin-top:12px;">
      <?php echo csrf_field(); ?>
      <button type="submit" class="btn btn-outline" style="width:100%;">Resend Code</button>
    </form>
    <p class="muted" style="margin-top:16px; text-align:center;">Wrong email? <a href="/auth/register.php">Start over</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
