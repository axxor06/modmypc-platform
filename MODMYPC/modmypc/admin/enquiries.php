<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_verify();
    $id = (int)$_POST['id'];
    $status = in_array($_POST['status'] ?? '', ['new','contacted','closed']) ? $_POST['status'] : 'new';
    $stmt = $conn->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Enquiry updated.'];
    header("Location: enquiries.php"); exit;
}

$filter = $_GET['status'] ?? '';
$where = $filter ? 'WHERE e.status = ?' : '';
$sql = "SELECT e.*, u.email as user_email FROM enquiries e LEFT JOIN users u ON u.id = e.user_id $where ORDER BY e.created_at DESC LIMIT 200";
$stmt = $conn->prepare($sql);
if ($filter) $stmt->bind_param('s', $filter);
$stmt->execute();
$enquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pc_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc"); $pc = $pc_r ? mysqli_fetch_assoc($pc_r)['c'] : 0;
$cc_r = mysqli_query($conn, "SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc = $cc_r ? mysqli_fetch_assoc($cc_r)['c'] : 0;
$ac_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac = $ac_r ? mysqli_fetch_assoc($ac_r)['c'] : 0;
$total_users_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users"); $total_users = $total_users_r ? mysqli_fetch_assoc($total_users_r)['c'] : 0;
$new_enq_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM enquiries WHERE status='new'"); $new_enquiries = $new_enq_r ? mysqli_fetch_assoc($new_enq_r)['c'] : 0;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enquiries &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
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
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users <span class="nav-badge"><?php echo $total_users; ?></span></a>
    <a href="enquiries.php" class="nav-item active"><i class="fas fa-comment-dots"></i> Enquiries <?php if($new_enquiries>0): ?><span class="nav-badge warn"><?php echo $new_enquiries; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Enquiries</div><div class="topbar-crumb">ModMyPC / Customers / Enquiries</div></div></div>
  </div>
  <div class="content">
    <?php if(isset($_SESSION['flash'])): ?>
    <div class="flash flash-<?php echo $_SESSION['flash']['type']; ?>"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash']['msg']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>
    <div class="page-header"><div><h1 class="page-title">Enquiries</h1><p class="page-sub">Cart checkouts, PC Builder, and product WhatsApp enquiries</p></div></div>
    <div class="card">
      <div style="display:flex; gap:8px; margin-bottom:16px;">
        <a href="enquiries.php" class="btn btn-sm <?php echo !$filter?'btn-primary':'btn-ghost'; ?>">All</a>
        <a href="enquiries.php?status=new" class="btn btn-sm <?php echo $filter==='new'?'btn-primary':'btn-ghost'; ?>">New</a>
        <a href="enquiries.php?status=contacted" class="btn btn-sm <?php echo $filter==='contacted'?'btn-primary':'btn-ghost'; ?>">Contacted</a>
        <a href="enquiries.php?status=closed" class="btn btn-sm <?php echo $filter==='closed'?'btn-primary':'btn-ghost'; ?>">Closed</a>
      </div>
      <div class="table-wrap"><table>
        <thead><tr><th>Customer</th><th>Source</th><th>Message</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($enquiries)): ?>
          <tr><td colspan="5"><div class="empty-state"><i class="fas fa-comment-dots"></i><p>No enquiries yet.</p></div></td></tr>
        <?php else: foreach ($enquiries as $en): ?>
          <tr>
            <td><?php echo htmlspecialchars($en['name']); ?><br><span class="prod-id"><?php echo htmlspecialchars($en['user_email'] ?? $en['phone'] ?? ''); ?></span></td>
            <td><span class="badge badge-blue"><?php echo htmlspecialchars($en['source']); ?></span></td>
            <td style="max-width:280px;"><?php echo htmlspecialchars(truncate_text($en['message'] ?? '', 120)); ?></td>
            <td class="mono"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($en['created_at']))); ?></td>
            <td>
              <form method="POST" style="display:flex; gap:4px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$en['id']; ?>">
                <select name="status" class="form-select" style="padding:4px 8px; font-size:12px;" onchange="this.form.submit()">
                  <option value="new" <?php echo $en['status']==='new'?'selected':''; ?>>New</option>
                  <option value="contacted" <?php echo $en['status']==='contacted'?'selected':''; ?>>Contacted</option>
                  <option value="closed" <?php echo $en['status']==='closed'?'selected':''; ?>>Closed</option>
                </select>
                <input type="hidden" name="update_status" value="1">
              </form>
            </td>
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
