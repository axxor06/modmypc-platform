<?php
$__page_title = 'Create Account | ModMyPC';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/otp.php';

if (is_logged_in()) redirect('/index.html');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $result = register_user($conn, $name, $email, $phone, $password);
        if ($result['ok']) {
            // Don't log in yet — require email OTP verification first.
            generate_and_send_otp($conn, $result['user_id'], $email, $name);
            $_SESSION['pending_verification_user_id'] = $result['user_id'];
            $_SESSION['pending_verification_next'] = $_GET['next'] ?? '/index.html';
            redirect('/auth/verify_email.php');
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:480px;">
    <h1 style="text-align:center;"><i class="fas fa-user-plus" style="color:var(--primary);"></i> Create Account</h1>
    <p class="muted" style="text-align:center;">Browsing and price checks never need an account — only cart, saved builds, and WhatsApp enquiries do.</p>
    <?php render_flash(); ?>
    <?php foreach ($errors as $err): ?>
      <div class="site-flash flash-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>
    <form method="POST" action="register.php<?php echo isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : ''; ?>">
      <?php echo csrf_field(); ?>
      <label>Full Name</label>
      <input type="text" name="name" required maxlength="100" value="<?php echo e($_POST['name'] ?? ''); ?>">
      <label>Email</label>
      <input type="email" name="email" required maxlength="190" value="<?php echo e($_POST['email'] ?? ''); ?>">
      <label>Phone (optional)</label>
      <input type="tel" name="phone" maxlength="20" value="<?php echo e($_POST['phone'] ?? ''); ?>">
      <label>Password</label>
      <div class="password-field">
        <input type="password" name="password" id="pw1" required minlength="8">
        <span class="password-toggle fas fa-eye" onclick="const i=document.getElementById('pw1'); i.type = i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"></span>
      </div>
      <label>Confirm Password</label>
      <div class="password-field">
        <input type="password" name="confirm_password" id="pw2" required minlength="8">
        <span class="password-toggle fas fa-eye" onclick="const i=document.getElementById('pw2'); i.type = i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"></span>
      </div>
      <button type="submit" class="btn" style="margin-top:20px; width:100%;">Create Account</button>
    </form>
    <?php if (!empty(GOOGLE_CLIENT_ID)): ?>
      <div style="display:flex; align-items:center; gap:10px; margin:18px 0; color:var(--secondary); font-size:12px;">
        <div style="flex:1; height:1px; background:var(--border);"></div>OR<div style="flex:1; height:1px; background:var(--border);"></div>
      </div>
      <a href="/auth/google_login.php" class="btn btn-outline" style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px;">
        <i class="fab fa-google"></i> Continue with Google
      </a>
    <?php endif; ?>
    <p class="muted" style="margin-top:16px; text-align:center;">Already have an account? <a href="/auth/login.php">Log in</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
