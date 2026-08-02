<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$where_sql = '';
$params = []; $types = '';
if ($search !== '') {
    $where_sql = 'WHERE name LIKE ? OR email LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like]; $types = 'ss';
}

$stmt = $conn->prepare("SELECT id, name, email, phone, created_at FROM users $where_sql ORDER BY created_at DESC LIMIT 200");
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_users_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users"); $total_users = $total_users_r ? mysqli_fetch_assoc($total_users_r)['c'] : 0;
$pc_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc"); $pc = $pc_r ? mysqli_fetch_assoc($pc_r)['c'] : 0;
$cc_r = mysqli_query($conn, "SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc = $cc_r ? mysqli_fetch_assoc($cc_r)['c'] : 0;
$ac_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac = $ac_r ? mysqli_fetch_assoc($ac_r)['c'] : 0;
$new_enq_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM enquiries WHERE status='new'"); $new_enquiries = $new_enq_r ? mysqli_fetch_assoc($new_enq_r)['c'] : 0;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Users &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">Customers</div>
    <a href="users.php" class="nav-item active"><i class="fas fa-users"></i> Users <span class="nav-badge"><?php echo $total_users; ?></span></a>
    <a href="enquiries.php" class="nav-item"><i class="fas fa-comment-dots"></i> Enquiries <?php if($new_enquiries>0): ?><span class="nav-badge warn"><?php echo $new_enquiries; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Users</div><div class="topbar-crumb">ModMyPC / Customers / Users</div></div></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Registered Users</h1><p class="page-sub"><?php echo $total_users; ?> account<?php echo $total_users!==1?'s':''; ?></p></div></div>
    <div class="card">
      <form method="GET" style="margin-bottom:16px;">
        <input type="text" name="q" class="form-input" style="max-width:280px;" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-ghost">Search</button>
      </form>
      <div class="table-wrap"><table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="4"><div class="empty-state"><i class="fas fa-users"></i><p>No users found.</p></div></td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
            <td class="mono"><?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at']))); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body>
</html>
