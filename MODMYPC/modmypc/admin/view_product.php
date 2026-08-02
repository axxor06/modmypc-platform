<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();
$id=intval($_GET['id']??0); if(!$id){ header("Location: products.php"); exit; }
$stmt = $conn->prepare("SELECT * FROM modmypc WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$p){ header("Location: products.php"); exit; }
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo htmlspecialchars($p['name']); ?> &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item active"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Product Details</div><div class="topbar-crumb">ModMyPC / Catalog</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Product Details</h1><p class="page-sub">#<?php echo $id; ?></p></div><div style="display:flex;gap:8px"><a href="edit_product.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Edit</a><a href="products.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a></div></div>
    <div style="max-width:480px"><div class="card">
      <div style="padding:40px;text-align:center;font-size:60px;border-bottom:1px solid var(--border);background:var(--surface2)"><?php echo cicon($p['category']); ?></div>
      <div style="padding:24px">
        <h2 style="font-size:19px;font-weight:800;margin-bottom:4px"><?php echo htmlspecialchars($p['name']); ?></h2>
        <?php if(!empty($p['description'])): ?><p style="color:var(--muted2);font-size:13px;margin-bottom:16px"><?php echo htmlspecialchars($p['description']); ?></p><?php endif; ?>
        <div class="detail-row"><span class="detail-key">Product ID</span><span class="mono">#<?php echo $p['id']; ?></span></div>
        <div class="detail-row"><span class="detail-key">Category</span><span class="badge badge-blue"><?php echo htmlspecialchars($p['category']); ?></span></div>
        <div class="detail-row"><span class="detail-key">Price</span><span class="mono" style="color:var(--accent);font-size:20px;font-weight:700">&#8377;<?php echo number_format($p['price']); ?></span></div>
        <div class="detail-row"><span class="detail-key">Stock</span><span class="mono <?php echo $p['stock']==0?'text-red':($p['stock']<=5?'text-amber':''); ?>" style="font-size:17px;font-weight:700"><?php echo $p['stock']; ?> units</span></div>
        <div class="detail-row"><span class="detail-key">Status</span><?php if($p['stock']==0): ?><span class="badge badge-red">Out of Stock</span><?php elseif($p['stock']<=5): ?><span class="badge badge-amber">Low Stock</span><?php else: ?><span class="badge badge-green">In Stock</span><?php endif; ?></div>
        <div class="detail-row"><span class="detail-key">Inventory Value</span><span class="mono">&#8377;<?php echo number_format($p['price']*$p['stock']); ?></span></div>
      </div>
    </div></div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body></html>
