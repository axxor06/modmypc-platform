<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();
$thresh=intval($_GET['threshold']??5);
$res=mysqli_query($conn,"SELECT id,category,name,stock,price FROM modmypc WHERE stock<=$thresh ORDER BY stock ASC"); $alerts=array(); if($res) while($r=mysqli_fetch_assoc($res)) $alerts[]=$r;
$out=0; $low=0; foreach($alerts as $p){ if($p['stock']==0) $out++; else $low++; }
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Stock Alerts &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item active"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Stock Alerts</div><div class="topbar-crumb">ModMyPC / Catalog</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Stock Alerts</h1><p class="page-sub"><?php echo count($alerts); ?> products need attention</p></div>
    <form method="GET" style="display:flex;align-items:center;gap:8px"><label style="font-size:13px;color:var(--muted2)">Threshold:</label><input type="number" name="threshold" value="<?php echo $thresh; ?>" class="form-input" style="width:80px" min="1"><button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i></button></form></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;max-width:440px">
      <div class="stat-card" style="--accent-c:#ef4444"><div class="stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444"><i class="fas fa-times-circle"></i></div><div class="stat-body"><div class="stat-value"><?php echo $out; ?></div><div class="stat-label">Out of Stock</div></div><div class="stat-bar" style="background:#ef4444"></div></div>
      <div class="stat-card" style="--accent-c:#f59e0b"><div class="stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-body"><div class="stat-value"><?php echo $low; ?></div><div class="stat-label">Low Stock</div></div><div class="stat-bar" style="background:#f59e0b"></div></div>
    </div>
    <div class="card">
    <?php if(empty($alerts)): ?><div class="empty-state" style="padding:60px"><i class="fas fa-check-circle" style="font-size:36px;color:#10b981;opacity:1;display:block;margin-bottom:12px"></i><p>All products well-stocked at threshold &le;<?php echo $thresh; ?>!</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($alerts as $p): ?>
      <tr>
        <td><div class="prod-cell"><div class="prod-thumb"><?php echo cicon($p['category']); ?></div><div><div class="prod-name"><?php echo htmlspecialchars($p['name']); ?></div><div class="prod-id">#<?php echo $p['id']; ?></div></div></div></td>
        <td><span class="badge badge-blue"><?php echo htmlspecialchars($p['category']); ?></span></td>
        <td class="mono">&#8377;<?php echo number_format($p['price']); ?></td>
        <td class="mono <?php echo $p['stock']==0?'text-red':'text-amber'; ?>" style="font-size:16px;font-weight:700"><?php echo $p['stock']; ?></td>
        <td><?php if($p['stock']==0): ?><span class="badge badge-red">Out of Stock</span><?php else: ?><span class="badge badge-amber">Low Stock</span><?php endif; ?></td>
        <td><a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-pen"></i> Update</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body></html>
