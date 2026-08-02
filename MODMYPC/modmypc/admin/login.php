<?php
require_once __DIR__ . '/includes/admin_auth.php';

if (is_admin_logged_in()) { header("Location: dashboard.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    csrf_verify();
    $result = attempt_admin_login($conn, $_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['ok']) { header("Location: dashboard.php"); exit; }
    $error = $result['error'];
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login - ModMyPC</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/admin.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);position:relative;overflow:hidden}
body::before,body::after{content:'';position:fixed;border-radius:50%;filter:blur(70px);z-index:0;animation:floatGlowAdmin 11s ease-in-out infinite}
body::before{width:360px;height:360px;background:rgba(255,30,60,.22);top:-100px;left:-100px}
body::after{width:320px;height:320px;background:rgba(200,16,46,.18);bottom:-120px;right:-80px;animation-delay:-5s}
@keyframes floatGlowAdmin{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(24px,-24px) scale(1.1)}}
.wrap{width:400px;position:relative;z-index:1;animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 0 24px rgba(255,30,60,.4);display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 14px}
.logo h1{font-size:24px;font-weight:800;letter-spacing:-0.5px;color:#fff}
.logo p{color:#64748b;font-size:13px;margin-top:4px}
.card{background:rgba(17,19,24,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,30,60,.2);border-radius:18px;padding:36px}
.pw-wrap{position:relative}
.pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted2)}
.pw-toggle:hover{color:var(--accent)}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-icon"><i class="fas fa-shield-halved" style="color:#fff"></i></div>
    <h1>ModMyPC</h1>
    <p>Admin Panel — Sign in to continue</p>
  </div>
  <div class="card">
    <?php if ($error): ?>
    <div class="flash flash-error" style="margin-bottom:20px"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-input" value="<?php echo e($_POST['username'] ?? 'admin'); ?>" autofocus>
      </div>
      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label">Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="adminPw" class="form-input" placeholder="••••••">
          <i class="fas fa-eye pw-toggle" onclick="const i=document.getElementById('adminPw');i.type=i.type==='password'?'text':'password';this.classList.toggle('fa-eye');this.classList.toggle('fa-eye-slash');"></i>
        </div>
      </div>
      <button type="submit" name="login" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>
  </div>
</div>
</body>
</html>
