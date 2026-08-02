<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

// Safe query - works even if description/created_at columns don't exist
$result = mysqli_query($conn, "SELECT id, category, name, stock, price FROM modmypc ORDER BY id DESC");
$products = array();
if($result){ while($row = mysqli_fetch_assoc($result)) $products[] = $row; }

$total_products = count($products);
$low_stock = 0; $out_of_stock = 0; $inventory_value = 0;
foreach($products as $p){
    if($p['stock'] == 0) $out_of_stock++;
    elseif($p['stock'] <= 5) $low_stock++;
    $inventory_value += floatval($p['price']) * intval($p['stock']);
}

$cat_result = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM modmypc GROUP BY category ORDER BY cnt DESC");
$categories = array();
if($cat_result){ while($row = mysqli_fetch_assoc($cat_result)) $categories[] = $row; }
$total_categories = count($categories);

function fmt($n){ if($n>=100000) return '&#8377;'.round($n/100000,1).'L'; return '&#8377;'.number_format($n); }

function cicon($cat){
    $c=strtolower($cat);
    if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false) return '&#128421;';
    if(strpos($c,'ram')!==false||strpos($c,'memory')!==false) return '&#128190;';
    if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false) return '&#127918;';
    if(strpos($c,'cabinet')!==false||strpos($c,'case')!==false) return '&#128451;';
    if(strpos($c,'mouse')!==false) return '&#128433;';
    if(strpos($c,'keyboard')!==false) return '&#9000;';
    if(strpos($c,'headphone')!==false||strpos($c,'headset')!==false) return '&#127911;';
    if(strpos($c,'psu')!==false||strpos($c,'power')!==false) return '&#9889;';
    if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false||strpos($c,'hdd')!==false) return '&#128191;';
    if(strpos($c,'laptop')!==false) return '&#128187;';
    if(strpos($c,'printer')!==false) return '&#128424;';
    if(strpos($c,'ups')!==false) return '&#128267;';
    return '&#128230;';
}

// Sidebar counts
$cnt_r = mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc = $cnt_r ? mysqli_fetch_assoc($cnt_r)['c'] : 0;
$cat_r = mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc = $cat_r ? mysqli_fetch_assoc($cat_r)['c'] : 0;
$alr_r = mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac = $alr_r ? mysqli_fetch_assoc($alr_r)['c'] : 0;
$oos_r = mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc = $oos_r ? mysqli_fetch_assoc($oos_r)['c'] : 0;

// New: customers, enquiries, recent activity
$users_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users"); $total_users = $users_r ? mysqli_fetch_assoc($users_r)['c'] : 0;
$enq_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM enquiries"); $total_enquiries = $enq_r ? mysqli_fetch_assoc($enq_r)['c'] : 0;
$new_enq_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM enquiries WHERE status='new'"); $new_enquiries = $new_enq_r ? mysqli_fetch_assoc($new_enq_r)['c'] : 0;
$activity_r = mysqli_query($conn, "SELECT al.action, al.details, al.created_at, u.name as user_name FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 12");
$recent_activity = array(); if ($activity_r) while ($row = mysqli_fetch_assoc($activity_r)) $recent_activity[] = $row;
$cur = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard &#8212; ModMyPC Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div>
    <div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">Overview</div>
    <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
  </div>
  <div class="nav-section">
    <div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section">
    <div class="nav-label">Customers</div>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users <span class="nav-badge"><?php echo $total_users; ?></span></a>
    <a href="enquiries.php" class="nav-item"><i class="fas fa-comment-dots"></i> Enquiries <?php if($new_enquiries>0): ?><span class="nav-badge warn"><?php echo $new_enquiries; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section">
    <div class="nav-label">System</div>
    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar">A</div>
      <div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div>
      <a href="logout.php" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</nav>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <div><div class="topbar-title">Dashboard</div><div class="topbar-crumb">ModMyPC / Overview</div></div>
    </div>
    <div class="topbar-right">
      <?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn" title="<?php echo $oosc; ?> out of stock"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?>
      <a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <?php if(isset($_SESSION['flash'])): ?>
    <div class="flash flash-<?php echo $_SESSION['flash']['type']; ?>"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash']['msg']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <div class="page-header">
      <div><h1 class="page-title">Dashboard</h1><p class="page-sub">Welcome back, <strong>Admin</strong></p></div>
      <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card" style="--accent-c:#4f8ef7">
        <div class="stat-icon" style="background:rgba(79,142,247,.12);color:#4f8ef7"><i class="fas fa-box"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo $total_products; ?></div><div class="stat-label">Total Products</div></div>
        <div class="stat-bar" style="background:#4f8ef7"></div>
      </div>
      <div class="stat-card" style="--accent-c:#10b981">
        <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="fas fa-tags"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo $total_categories; ?></div><div class="stat-label">Categories</div></div>
        <div class="stat-bar" style="background:#10b981"></div>
      </div>
      <div class="stat-card" style="--accent-c:#f59e0b">
        <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo $low_stock+$out_of_stock; ?></div><div class="stat-label">Stock Alerts</div></div>
        <div class="stat-bar" style="background:#f59e0b"></div>
      </div>
      <div class="stat-card" style="--accent-c:#7c3aed">
        <div class="stat-icon" style="background:rgba(124,58,237,.12);color:#7c3aed"><i class="fas fa-rupee-sign"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo fmt($inventory_value); ?></div><div class="stat-label">Inventory Value</div></div>
        <div class="stat-bar" style="background:#7c3aed"></div>
      </div>
      <div class="stat-card" style="--accent-c:#0ea5e9">
        <div class="stat-icon" style="background:rgba(14,165,233,.12);color:#0ea5e9"><i class="fas fa-users"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo $total_users; ?></div><div class="stat-label">Registered Users</div></div>
        <div class="stat-bar" style="background:#0ea5e9"></div>
      </div>
      <div class="stat-card" style="--accent-c:#ec4899">
        <div class="stat-icon" style="background:rgba(236,72,153,.12);color:#ec4899"><i class="fas fa-comment-dots"></i></div>
        <div class="stat-body"><div class="stat-value"><?php echo $total_enquiries; ?></div><div class="stat-label">Total Enquiries (<?php echo $new_enquiries; ?> new)</div></div>
        <div class="stat-bar" style="background:#ec4899"></div>
      </div>
    </div>

    <!-- TWO COL -->
    <div class="two-col">
      <div class="card">
        <div class="card-header"><span class="card-title">Recent Products</span><a href="products.php" class="btn btn-ghost btn-sm">View All <i class="fas fa-arrow-right"></i></a></div>
        <div class="table-wrap"><table>
          <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th></tr></thead>
          <tbody>
          <?php $recent=array_slice($products,0,6); if(empty($recent)): ?>
            <tr><td colspan="4"><div class="empty-state"><i class="fas fa-box-open"></i><p>No products yet.</p></div></td></tr>
          <?php else: foreach($recent as $p): ?>
            <tr>
              <td><div class="prod-cell"><div class="prod-thumb"><?php echo cicon($p['category']); ?></div><div><div class="prod-name"><?php echo htmlspecialchars($p['name']); ?></div><div class="prod-id">#<?php echo $p['id']; ?></div></div></div></td>
              <td><span class="badge badge-blue"><?php echo htmlspecialchars($p['category']); ?></span></td>
              <td class="mono">&#8377;<?php echo number_format($p['price']); ?></td>
              <td class="mono <?php echo $p['stock']==0?'text-red':($p['stock']<=5?'text-amber':''); ?>"><?php echo $p['stock']; ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table></div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">&#9888; Stock Alerts</span><a href="stock_alerts.php" class="btn btn-ghost btn-sm">View All <i class="fas fa-arrow-right"></i></a></div>
        <div class="alert-list">
        <?php
        $alerts=array(); foreach($products as $p){ if($p['stock']<=5) $alerts[]=$p; }
        if(empty($alerts)):
        ?><div class="empty-state"><i class="fas fa-check-circle" style="color:#10b981;opacity:1"></i><p>All well-stocked!</p></div>
        <?php else: foreach(array_slice($alerts,0,6) as $p): ?>
          <div class="alert-item <?php echo $p['stock']==0?'critical':'warn'; ?>">
            <div class="alert-icon"><i class="fas fa-<?php echo $p['stock']==0?'times-circle':'exclamation-triangle'; ?>"></i></div>
            <div class="alert-info"><div class="alert-name"><?php echo htmlspecialchars($p['name']); ?></div><div class="alert-sub"><?php echo $p['category'].' &middot; '.($p['stock']==0?'OUT OF STOCK':'Only '.$p['stock'].' left'); ?></div></div>
            <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm">Fix</a>
          </div>
        <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <!-- CATEGORY BREAKDOWN -->
    <?php if(!empty($categories)): ?>
    <div class="card" style="margin-top:20px">
      <div class="card-header"><span class="card-title">Category Breakdown</span></div>
      <div class="cat-breakdown">
      <?php $max=1; foreach($categories as $c){ if($c['cnt']>$max) $max=$c['cnt']; } foreach($categories as $cat): $pct=round($cat['cnt']/$max*100); ?>
        <div class="breakdown-row">
          <div class="breakdown-label"><?php echo cicon($cat['category']); ?> <?php echo htmlspecialchars($cat['category']); ?></div>
          <div class="breakdown-bar-wrap"><div class="breakdown-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="breakdown-val mono"><?php echo $cat['cnt']; ?></div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- RECENT ACTIVITY -->
    <div class="card" style="margin-top:20px">
      <div class="card-header"><span class="card-title">Recent Activity</span></div>
      <div class="alert-list">
      <?php if (empty($recent_activity)): ?>
        <div class="empty-state"><i class="fas fa-clock"></i><p>No activity recorded yet.</p></div>
      <?php else: foreach ($recent_activity as $a): ?>
        <div class="alert-item">
          <div class="alert-icon"><i class="fas fa-circle-notch"></i></div>
          <div class="alert-info">
            <div class="alert-name"><?php echo htmlspecialchars($a['user_name'] ?? 'Guest/System'); ?> &mdash; <?php echo htmlspecialchars(str_replace('_',' ',$a['action'])); ?></div>
            <div class="alert-sub"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($a['created_at']))); ?><?php echo !empty($a['details']) ? ' &middot; ' . htmlspecialchars(truncate_text($a['details'],80)) : ''; ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
      </div>
    </div>

  </div>
</div>

<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body>
</html>
