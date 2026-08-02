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
$errors=array();
if(isset($_POST['update'])){
    csrf_verify();
    $name=trim($_POST['name']??''); $cat=trim($_POST['category']??''); $ncat=trim($_POST['new_category']??'');
    $brand=trim($_POST['brand']??''); $warranty=trim($_POST['warranty']??'');
    $price=floatval($_POST['price']??0); $stock=intval($_POST['stock']??0); $desc=trim($_POST['description']??'');
    if($ncat) $cat=$ncat;
    if(!$name) $errors[]='Product name required.'; if(!$cat) $errors[]='Category required.'; if($price<=0) $errors[]='Price must be > 0.';

    $imagePath = $p['image'] ?? null; // keep existing image unless a new one is uploaded
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if ($file['size'] > 4 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 4MB.';
        } else {
            $info = getimagesize($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!$info || !isset($allowed[$info['mime']])) {
                $errors[] = 'Image must be a valid JPG, PNG, or WEBP file.';
            } else {
                $dir = __DIR__ . '/../product_images/';
                if (!file_exists($dir)) { mkdir($dir, 0755, true); file_put_contents($dir . '.htaccess', "php_flag engine off\n"); }
                $fname = 'p_' . bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
                if (move_uploaded_file($file['tmp_name'], $dir . $fname)) {
                    $imagePath = 'modmypc/product_images/' . $fname;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    }

    if(empty($errors)){
        $u = $conn->prepare("UPDATE modmypc SET category=?, name=?, stock=?, price=?, description=?, brand=?, warranty=?, image=? WHERE id=?");
        $u->bind_param('ssidssssi', $cat, $name, $stock, $price, $desc, $brand, $warranty, $imagePath, $id);
        if (!$u->execute()) {
            $u2 = $conn->prepare("UPDATE modmypc SET category=?, name=?, stock=?, price=? WHERE id=?");
            $u2->bind_param('ssidi', $cat, $name, $stock, $price, $id);
            $u2->execute();
            $u2->close();
        }
        $u->close();
        $_SESSION['flash']=array('type'=>'success','msg'=>'Product updated!');
        header("Location: products.php"); exit;
    }
    $p=array_merge($p,$_POST);
}
$cr=mysqli_query($conn,"SELECT DISTINCT category FROM modmypc ORDER BY category"); $cats=array(); if($cr) while($r=mysqli_fetch_assoc($cr)) $cats[]=$r['category'];
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Product &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
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
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Edit Product</div><div class="topbar-crumb">ModMyPC / Catalog / Products</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Edit Product</h1><p class="page-sub">#<?php echo $id; ?> &mdash; <?php echo htmlspecialchars($p['name']); ?></p></div><a href="products.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a></div>
    <?php if(!empty($errors)): ?><div class="flash flash-error"><i class="fas fa-exclamation-circle"></i><ul style="margin:0;padding-left:16px"><?php foreach($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="form-layout">
      <div class="card" style="flex:1.5">
        <div class="card-header"><span class="card-title">Edit Details</span></div>
        <form method="POST" class="form-body" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <div class="form-group"><label class="form-label">Product Name <span class="req">*</span></label><input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($p['name']); ?>" required></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Category <span class="req">*</span></label>
              <select name="category" class="form-select" id="cs" onchange="document.getElementById('ncw').style.display=this.value==='__new__'?'block':'none'">
                <?php foreach($cats as $c): ?><option value="<?php echo htmlspecialchars($c); ?>" <?php echo $p['category']===$c?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?>
                <option value="__new__">+ New category...</option>
              </select>
            </div>
            <div class="form-group" id="ncw" style="display:none"><label class="form-label">New Category</label><input type="text" name="new_category" class="form-input" placeholder="e.g. Monitors"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Price (&#8377;) <span class="req">*</span></label><input type="number" name="price" class="form-input" value="<?php echo $p['price']; ?>" min="1" required></div>
            <div class="form-group"><label class="form-label">Stock Qty</label><input type="number" name="stock" class="form-input" value="<?php echo $p['stock']; ?>" min="0"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Brand</label><input type="text" name="brand" class="form-input" value="<?php echo htmlspecialchars($p['brand']??''); ?>"></div>
            <div class="form-group"><label class="form-label">Warranty</label><input type="text" name="warranty" class="form-input" value="<?php echo htmlspecialchars($p['warranty']??''); ?>"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Product Image</label>
            <?php if (!empty($p['image'])): ?><img src="/<?php echo htmlspecialchars(ltrim($p['image'],'/')); ?>" style="width:70px;height:70px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;"><?php endif; ?>
            <input type="file" name="image" class="form-input" accept="image/jpeg,image/png,image/webp">
            <p style="color:var(--muted2);font-size:12px;margin-top:6px">Leave empty to keep the current image. JPG, PNG or WEBP, up to 4MB.</p>
          </div>
          <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea"><?php echo htmlspecialchars($p['description']??''); ?></textarea></div>
          <div class="form-actions">
            <span></span>
            <div style="margin-left:auto;display:flex;gap:8px"><a href="products.php" class="btn btn-ghost">Cancel</a><button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button></div>
          </div>
        </form>
        <form method="POST" action="delete_product.php" onsubmit="return confirm('Delete this product permanently?')" style="margin-top:12px;">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" value="<?php echo $id; ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete This Product</button>
        </form>
      </div>
      <div style="flex:0.7"><div class="card"><div class="card-header"><span class="card-title">Current Values</span></div><div style="padding:20px">
        <div class="detail-row"><span class="detail-key">ID</span><span class="mono">#<?php echo $p['id']; ?></span></div>
        <div class="detail-row"><span class="detail-key">Category</span><span class="badge badge-blue"><?php echo htmlspecialchars($p['category']); ?></span></div>
        <div class="detail-row"><span class="detail-key">Price</span><span class="mono" style="color:#4f8ef7;font-weight:700">&#8377;<?php echo number_format($p['price']); ?></span></div>
        <div class="detail-row"><span class="detail-key">Stock</span><span class="mono <?php echo $p['stock']==0?'text-red':($p['stock']<=5?'text-amber':''); ?>"><?php echo $p['stock']; ?> units</span></div>
        <div class="detail-row"><span class="detail-key">Status</span><?php if($p['stock']==0): ?><span class="badge badge-red">Out of Stock</span><?php elseif($p['stock']<=5): ?><span class="badge badge-amber">Low Stock</span><?php else: ?><span class="badge badge-green">In Stock</span><?php endif; ?></div>
      </div></div></div>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body></html>
