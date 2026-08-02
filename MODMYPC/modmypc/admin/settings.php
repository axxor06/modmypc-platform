<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
$val_r=mysqli_query($conn,"SELECT SUM(price*stock) as v FROM modmypc"); $val=$val_r?mysqli_fetch_assoc($val_r)['v']:0; if(!$val) $val=0;
$msg=null;
if(isset($_POST['save_password'])){
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $np = $_POST['new_password'] ?? '';
    $cp = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        $msg = ['type' => 'error', 'text' => 'Current password is incorrect.'];
    } elseif (strlen($np) < 8) {
        $msg = ['type' => 'error', 'text' => 'New password must be at least 8 characters.'];
    } elseif ($np !== $cp) {
        $msg = ['type' => 'error', 'text' => 'Passwords do not match.'];
    } else {
        $hash = password_hash($np, PASSWORD_DEFAULT);
        $u = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $u->bind_param('si', $hash, $_SESSION['admin_id']);
        $u->execute();
        $u->close();
        log_activity($conn, null, 'admin_password_change', 'admin:' . $_SESSION['admin_username']);
        $msg = ['type' => 'success', 'text' => 'Password updated successfully.'];
    }
}
function fmt($n){if($n>=100000) return '&#8377;'.round($n/100000,1).'L'; return '&#8377;'.number_format($n);}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Settings &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Settings</div><div class="topbar-crumb">ModMyPC / System</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Settings</h1><p class="page-sub">Admin panel configuration</p></div></div>
    <?php if($msg): ?><div class="flash flash-<?php echo $msg['type']; ?>"><i class="fas fa-<?php echo $msg['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i> <?php echo $msg['text']; ?></div><?php endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:860px">
      <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-database" style="color:var(--accent)"></i> Database Info</span></div><div style="padding:20px">
        <div class="detail-row"><span class="detail-key">Host</span><span class="mono" style="font-size:11px">sql212.infinityfree.com</span></div>
        <div class="detail-row"><span class="detail-key">Status</span><span class="badge badge-green">Connected</span></div>
        <div class="detail-row"><span class="detail-key">Products</span><span class="mono"><?php echo $pc; ?></span></div>
        <div class="detail-row"><span class="detail-key">Categories</span><span class="mono"><?php echo $cc; ?></span></div>
        <div class="detail-row"><span class="detail-key">Inventory Value</span><span class="mono"><?php echo fmt($val); ?></span></div>
        <div class="detail-row"><span class="detail-key">PHP Version</span><span class="mono"><?php echo phpversion(); ?></span></div>
      </div></div>
      <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-lock" style="color:var(--accent)"></i> Change Password</span></div>
        <form method="POST" class="form-body">
          <?php echo csrf_field(); ?>
          <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-input" required></div>
          <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-input" placeholder="Min 8 chars" required minlength="8"></div>
          <div class="form-group"><label class="form-label">Confirm</label><input type="password" name="confirm_password" class="form-input" placeholder="Repeat" required minlength="8"></div>
          <button type="submit" name="save_password" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update</button>
        </form>
      </div>
      <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-bolt" style="color:var(--accent)"></i> Quick Actions</span></div><div style="padding:20px;display:flex;flex-direction:column;gap:10px">
        <a href="products.php" class="btn btn-secondary"><i class="fas fa-box"></i> Manage Products</a>
        <a href="categories.php" class="btn btn-secondary"><i class="fas fa-tags"></i> Manage Categories</a>
        <a href="stock_alerts.php" class="btn btn-secondary"><i class="fas fa-exclamation-triangle"></i> Stock Alerts</a>
        <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
        <a href="logout.php" class="btn btn-danger" style="margin-top:6px"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div></div>
      <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-info-circle" style="color:var(--accent)"></i> About</span></div><div style="padding:20px">
        <div class="detail-row"><span class="detail-key">Panel</span><span class="mono">ModMyPC Admin v2.1</span></div>
        <div class="detail-row"><span class="detail-key">Compatibility</span><span class="badge badge-green">PHP 7.0+</span></div>
        <div class="detail-row"><span class="detail-key">Login</span><span class="mono">admin / 1234</span></div>
      </div></div>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body></html>
