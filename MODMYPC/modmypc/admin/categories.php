<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();
if(isset($_POST['rename'])){
    csrf_verify();
    $old = trim($_POST['old_name'] ?? '');
    $new = trim($_POST['new_name'] ?? '');
    if ($new !== '') {
        $stmt = $conn->prepare("UPDATE modmypc SET category = ? WHERE category = ?");
        $stmt->bind_param('ss', $new, $old);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Category renamed to "' . htmlspecialchars($new) . '".'];
    }
    header("Location: categories.php"); exit;
}
if(isset($_GET['delete_cat'])){
    // GET-triggered action (from a confirmation modal) — still requires a
    // matching CSRF token appended to the link by JS below.
    if (empty($_GET['csrf']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf'])) {
        http_response_code(403); die('Security check failed. Please refresh the page and try again.');
    }
    $cat = trim($_GET['delete_cat']);
    $action = $_GET['action'] ?? 'keep';
    if ($action === 'delete_all') {
        $stmt = $conn->prepare("DELETE FROM modmypc WHERE category = ?");
    } else {
        $uncategorized = 'Uncategorized';
        $stmt = $conn->prepare("UPDATE modmypc SET category = ? WHERE category = ?");
        $stmt->bind_param('ss', $uncategorized, $cat);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Category removed.'];
        header("Location: categories.php"); exit;
    }
    $stmt->bind_param('s', $cat);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Category removed.'];
    header("Location: categories.php"); exit;
}
$res=mysqli_query($conn,"SELECT category,COUNT(*) as cnt,SUM(stock) as ts,SUM(price*stock) as tv,SUM(CASE WHEN stock=0 THEN 1 ELSE 0 END) as oc FROM modmypc GROUP BY category ORDER BY cnt DESC"); $categories=array(); if($res) while($r=mysqli_fetch_assoc($res)) $categories[]=$r;
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Categories &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item active"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Categories</div><div class="topbar-crumb">ModMyPC / Catalog</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <?php if(isset($_SESSION['flash'])): ?><div class="flash flash-<?php echo $_SESSION['flash']['type']; ?>"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash']['msg']; unset($_SESSION['flash']); ?></div><?php endif; ?>
    <div class="page-header"><div><h1 class="page-title">Categories</h1><p class="page-sub"><?php echo count($categories); ?> categories</p></div><a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a></div>
    <div class="cat-grid">
    <?php foreach($categories as $c): ?>
    <div class="cat-card">
      <div class="cat-card-top"><div class="cat-icon"><?php echo cicon($c['category']); ?></div><div><div class="cat-name"><?php echo htmlspecialchars($c['category']); ?></div><div class="cat-count"><?php echo $c['cnt']; ?> products</div></div></div>
      <div class="cat-stats">
        <div class="cat-stat"><span class="mono"><?php echo $c['ts']; ?></span><span>stock</span></div>
        <div class="cat-stat"><span class="mono">&#8377;<?php echo $c['tv']>=100000?round($c['tv']/100000,1).'L':number_format($c['tv']); ?></span><span>value</span></div>
        <?php if($c['oc']>0): ?><div class="cat-stat"><span class="mono text-red"><?php echo $c['oc']; ?></span><span>out of stock</span></div><?php endif; ?>
      </div>
      <div class="cat-actions">
        <a href="products.php?category=<?php echo urlencode($c['category']); ?>" class="btn btn-ghost btn-sm"><i class="fas fa-box"></i> Products</a>
        <button class="btn btn-secondary btn-sm" onclick="openRename('<?php echo addslashes(htmlspecialchars($c['category'])); ?>')"><i class="fas fa-pen"></i></button>
        <button class="btn btn-danger btn-sm" onclick="openDel('<?php echo addslashes(htmlspecialchars($c['category'])); ?>',<?php echo $c['cnt']; ?>)"><i class="fas fa-trash"></i></button>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="cat-card cat-card-add" onclick="window.location='add_product.php'"><i class="fas fa-plus" style="font-size:26px;color:var(--accent);margin-bottom:8px"></i><div style="font-weight:700;font-size:14px;color:var(--muted2)">Add New Category</div></div>
    </div>
  </div>
</div>

<!-- Rename Modal -->
<div id="ren-modal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal"><div class="modal-header"><span class="modal-title">Rename Category</span><div class="modal-close" onclick="document.getElementById('ren-modal').style.display='none'"><i class="fas fa-times"></i></div></div>
  <form method="POST"><?php echo csrf_field(); ?><input type="hidden" name="old_name" id="ren-old">
  <div class="modal-body"><div class="form-group"><label class="form-label">New Name</label><input type="text" name="new_name" id="ren-new" class="form-input" required></div><p style="font-size:12px;color:var(--muted2);margin-top:8px">Renames across all products.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="document.getElementById('ren-modal').style.display='none'">Cancel</button><button type="submit" name="rename" class="btn btn-primary"><i class="fas fa-save"></i> Rename</button></div>
  </form></div>
</div>
<!-- Delete Modal -->
<div id="del-modal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal"><div class="modal-header"><span class="modal-title">Delete Category</span><div class="modal-close" onclick="document.getElementById('del-modal').style.display='none'"><i class="fas fa-times"></i></div></div>
  <div class="modal-body">
    <div style="text-align:center;margin-bottom:16px"><div style="width:50px;height:50px;background:rgba(239,68,68,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;color:var(--danger)"><i class="fas fa-trash"></i></div><div style="font-size:15px;font-weight:700;margin-bottom:6px">Delete "<span id="del-name"></span>"?</div><div style="font-size:13px;color:var(--muted2)" id="del-info"></div></div>
    <div style="display:flex;flex-direction:column;gap:8px"><a id="del-keep" href="#" class="btn btn-secondary" style="justify-content:center"><i class="fas fa-box"></i> Move products to "Uncategorized"</a><a id="del-all" href="#" class="btn btn-danger" style="justify-content:center"><i class="fas fa-trash"></i> Delete category &amp; ALL its products</a></div>
  </div>
  <div class="modal-footer"><button class="btn btn-ghost" onclick="document.getElementById('del-modal').style.display='none'">Cancel</button></div>
  </div>
</div>
<script>
function openRename(n){document.getElementById('ren-old').value=n;document.getElementById('ren-new').value=n;document.getElementById('ren-modal').style.display='flex';}
const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
function openDel(n,c){document.getElementById('del-name').textContent=n;document.getElementById('del-info').textContent='This category has '+c+' products.';document.getElementById('del-keep').href='categories.php?delete_cat='+encodeURIComponent(n)+'&action=keep&csrf='+encodeURIComponent(CSRF_TOKEN);document.getElementById('del-all').href='categories.php?delete_cat='+encodeURIComponent(n)+'&action=delete_all&csrf='+encodeURIComponent(CSRF_TOKEN);document.getElementById('del-modal').style.display='flex';}
document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});
</script>
</body></html>
