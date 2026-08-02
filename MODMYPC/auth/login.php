<?php
$__page_title = 'Login | ModMyPC';
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) redirect('/index.html');

$next = $_GET['next'] ?? ($_POST['next'] ?? '/index.html');
// Guard against open-redirect: only allow local paths.
if (!preg_match('#^/[^/].*#', $next) && $next !== '/') $next = '/index.html';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = attempt_login($conn, $email, $password);
    if ($result['ok']) {
        set_flash('success', 'Welcome back!');
        redirect($next);
    } elseif (!empty($result['needs_verification'])) {
        require_once __DIR__ . '/../includes/otp.php';
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param('i', $result['user_id']);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        generate_and_send_otp($conn, $result['user_id'], $u['email'], $u['name']);
        $_SESSION['pending_verification_user_id'] = $result['user_id'];
        $_SESSION['pending_verification_next'] = $next;
        set_flash('info', 'Please verify your email to continue — we just sent you a new code.');
        redirect('/auth/verify_email.php');
    } else {
        $error = $result['error'];
    }
}
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1 style="text-align:center;"><i class="fas fa-microchip" style="color:var(--primary);"></i> Welcome Back</h1>
    <p class="muted" style="text-align:center; margin-top:-8px;">Log in to your ModMyPC account</p>
    <?php render_flash(); ?>
    <?php if ($error): ?><div class="site-flash flash-error"><?php echo e($error); ?></div><?php endif; ?>
    <form method="POST" action="login.php?next=<?php echo urlencode($next); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="next" value="<?php echo e($next); ?>">
      <label>Email</label>
      <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
      <label>Password</label>
      <div class="password-field">
        <input type="password" name="password" id="pw" required>
        <span class="password-toggle fas fa-eye" onclick="const i=document.getElementById('pw'); i.type = i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"></span>
      </div>
      <button type="submit" class="btn" style="margin-top:20px; width:100%;">Log In</button>
    </form>
    <?php if (!empty(GOOGLE_CLIENT_ID)): ?>
      <div style="display:flex; align-items:center; gap:10px; margin:18px 0; color:var(--secondary); font-size:12px;">
        <div style="flex:1; height:1px; background:var(--border);"></div>OR<div style="flex:1; height:1px; background:var(--border);"></div>
      </div>
      <a href="/auth/google_login.php?next=<?php echo urlencode($next); ?>" class="btn btn-outline" style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px;">
        <i class="fab fa-google"></i> Continue with Google
      </a>
    <?php endif; ?>
    <p class="muted" style="margin-top:16px; text-align:center;">
      <a href="/auth/forgot_password.php">Forgot password?</a> &middot;
      New here? <a href="/auth/register.php">Create an account</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
