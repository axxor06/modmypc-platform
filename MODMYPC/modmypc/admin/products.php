<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = '(name LIKE ? OR category LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM modmypc $where_sql");
if ($types !== '') $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = (int)$count_stmt->get_result()->fetch_assoc()['c'];
$count_stmt->close();

$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("SELECT id, category, name, stock, price FROM modmypc $where_sql ORDER BY id DESC LIMIT ? OFFSET ?");
$all_types = $types . 'ii';
$all_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cr = mysqli_query($conn, "SELECT DISTINCT category FROM modmypc ORDER BY category"); $cats = []; if ($cr) while ($r = mysqli_fetch_assoc($cr)) $cats[] = $r['category'];
$pc_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc"); $pc = $pc_r ? mysqli_fetch_assoc($pc_r)['c'] : 0;
$cc_r = mysqli_query($conn, "SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc = $cc_r ? mysqli_fetch_assoc($cc_r)['c'] : 0;
$ac_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac = $ac_r ? mysqli_fetch_assoc($ac_r)['c'] : 0;
$oos_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc = $oos_r ? mysqli_fetch_assoc($oos_r)['c'] : 0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Products &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo"><div class="logo-icon"><i class="fas fa-cog" style="color:#fff;font-size:16px"></i></div><div><div class="logo-text">ModMyPC</div><div class="logo-sub">Admin Panel</div></div></div>
  <div class="nav-section"><div class="nav-label">Overview</div><a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a></div>
  <div class="nav-section"><div class="nav-label">Catalog</div>
    <a href="products.php" class="nav-item active"><i class="fas fa-box"></i> Products <span class="nav-badge"><?php echo $pc; ?></span></a>
    <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories <span class="nav-badge"><?php echo $cc; ?></span></a>
    <a href="stock_alerts.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Stock Alerts <?php if($ac>0): ?><span class="nav-badge warn"><?php echo $ac; ?></span><?php endif; ?></a>
  </div>
  <div class="nav-section"><div class="nav-label">Customers</div>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
    <a href="enquiries.php" class="nav-item"><i class="fas fa-comment-dots"></i> Enquiries</a>
  </div>
  <div class="nav-section"><div class="nav-label">System</div><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a></div>
  <div class="sidebar-footer"><div class="sidebar-user"><div class="avatar">A</div><div class="avatar-info"><div class="avatar-name">Admin</div><div class="avatar-role">Super Admin</div></div><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div>
</nav>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Products</div><div class="topbar-crumb">ModMyPC / Catalog / Products</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <?php if(isset($_SESSION['flash'])): ?>
    <div class="flash flash-<?php echo $_SESSION['flash']['type']; ?>"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash']['msg']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>
    <div class="page-header"><div><h1 class="page-title">Products</h1><p class="page-sub"><?php echo $total; ?> product<?php echo $total!==1?'s':''; ?> in inventory</p></div><a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a></div>

    <div class="card">
      <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <input type="text" name="q" class="form-input" style="max-width:240px;" placeholder="Search by name or category..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="category" class="form-select" style="max-width:200px;">
          <option value="">All Categories</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $category===$c?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost">Filter</button>
        <?php if ($search || $category): ?><a href="products.php" class="btn btn-ghost">Clear</a><?php endif; ?>
      </form>

      <div class="table-wrap"><table>
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="5"><div class="empty-state"><i class="fas fa-box-open"></i><p>No products found.</p></div></td></tr>
        <?php else: foreach ($products as $p): ?>
          <tr>
            <td><div class="prod-cell"><div class="prod-thumb"><?php echo cicon($p['category']); ?></div><div><div class="prod-name"><?php echo htmlspecialchars($p['name']); ?></div><div class="prod-id">#<?php echo $p['id']; ?></div></div></div></td>
            <td><span class="badge badge-blue"><?php echo htmlspecialchars($p['category']); ?></span></td>
            <td class="mono">&#8377;<?php echo number_format($p['price']); ?></td>
            <td class="mono <?php echo $p['stock']==0?'text-red':($p['stock']<=5?'text-amber':''); ?>"><?php echo $p['stock']; ?></td>
            <td style="text-align:right; white-space:nowrap;">
              <a href="view_product.php?id=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm" title="View"><i class="fas fa-eye"></i></a>
              <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
              <form method="POST" action="delete_product.php" style="display:inline;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <button type="submit" class="btn btn-ghost btn-sm" title="Delete" style="color:#d92d20;"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div>

      <?php if ($total_pages > 1): ?>
        <div style="display:flex; gap:6px; justify-content:center; margin-top:18px;">
          <?php for ($i = 1; $i <= $total_pages; $i++):
            $qs = http_build_query(['q' => $search, 'category' => $category, 'page' => $i]); ?>
            <a href="?<?php echo $qs; ?>" class="btn btn-sm <?php echo $i===$page?'btn-primary':'btn-ghost'; ?>"><?php echo $i; ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body>
</html>
