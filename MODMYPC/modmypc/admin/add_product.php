<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin();

$errors=array();
if(isset($_POST['add'])){
    csrf_verify();
    $name=trim($_POST['name']??''); $cat=trim($_POST['category']??''); $ncat=trim($_POST['new_category']??'');
    $brand=trim($_POST['brand']??''); $warranty=trim($_POST['warranty']??'');
    $price=floatval($_POST['price']??0); $stock=intval($_POST['stock']??0); $desc=trim($_POST['description']??'');
    if($ncat) $cat=$ncat;
    if(!$name) $errors[]='Product name required.';
    if(!$cat)  $errors[]='Category required.';
    if($price<=0) $errors[]='Price must be greater than 0.';

    // Handle optional image upload with real validation (not just trusting
    // the extension/MIME type the browser sends).
    $imagePath = null;
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
        $stmt = $conn->prepare("INSERT INTO modmypc (category, name, stock, price, description, brand, warranty, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssidssss', $cat, $name, $stock, $price, $desc, $brand, $warranty, $imagePath);
        if (!$stmt->execute()) {
            // Fall back for older tables without the newer columns.
            $stmt2 = $conn->prepare("INSERT INTO modmypc (category, name, stock, price) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param('ssid', $cat, $name, $stock, $price);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
        $_SESSION['flash']=array('type'=>'success','msg'=>'Product "'.htmlspecialchars($name).'" added!');
        header("Location: products.php"); exit;
    }
}
$cr=mysqli_query($conn,"SELECT DISTINCT category FROM modmypc ORDER BY category"); $cats=array(); if($cr) while($r=mysqli_fetch_assoc($cr)) $cats[]=$r['category'];
$pc_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc"); $pc=$pc_r?mysqli_fetch_assoc($pc_r)['c']:0;
$cc_r=mysqli_query($conn,"SELECT COUNT(DISTINCT category) as c FROM modmypc"); $cc=$cc_r?mysqli_fetch_assoc($cc_r)['c']:0;
$ac_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock<=5"); $ac=$ac_r?mysqli_fetch_assoc($ac_r)['c']:0;
$oos_r=mysqli_query($conn,"SELECT COUNT(*) as c FROM modmypc WHERE stock=0"); $oosc=$oos_r?mysqli_fetch_assoc($oos_r)['c']:0;
function cicon($cat){$c=strtolower($cat);if(strpos($c,'processor')!==false||strpos($c,'cpu')!==false)return'&#128421;';if(strpos($c,'ram')!==false)return'&#128190;';if(strpos($c,'gpu')!==false||strpos($c,'graphic')!==false)return'&#127918;';if(strpos($c,'cabinet')!==false)return'&#128451;';if(strpos($c,'mouse')!==false)return'&#128433;';if(strpos($c,'keyboard')!==false)return'&#9000;';if(strpos($c,'headphone')!==false)return'&#127911;';if(strpos($c,'psu')!==false||strpos($c,'power')!==false)return'&#9889;';if(strpos($c,'storage')!==false||strpos($c,'ssd')!==false)return'&#128191;';if(strpos($c,'laptop')!==false)return'&#128187;';return'&#128230;';}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Product &#8212; ModMyPC Admin</title><link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="assets/admin.css"></head>
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
    <div class="topbar-left"><button class="sidebar-toggle" id="sidebarToggleBtn" onclick="event.stopPropagation(); document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button><div><div class="topbar-title">Add Product</div><div class="topbar-crumb">ModMyPC / Catalog / Products</div></div></div>
    <div class="topbar-right"><?php if($oosc>0): ?><a href="stock_alerts.php" class="topbar-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></a><?php else: ?><div class="topbar-btn"><i class="fas fa-bell"></i></div><?php endif; ?><a href="add_product.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a></div>
  </div>
  <div class="content">
    <div class="page-header"><div><h1 class="page-title">Add Product</h1><p class="page-sub">Add a new product to your inventory</p></div><a href="products.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a></div>
    <?php if(!empty($errors)): ?><div class="flash flash-error"><i class="fas fa-exclamation-circle"></i><ul style="margin:0;padding-left:16px"><?php foreach($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="form-layout">
      <div class="card" style="flex:1.5">
        <div class="card-header"><span class="card-title">Product Details</span></div>
        <form method="POST" class="form-body" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <div class="form-group"><label class="form-label">Product Name <span class="req">*</span></label><input type="text" name="name" class="form-input" placeholder="e.g. Intel Core i9-13900K" value="<?php echo htmlspecialchars($_POST['name']??''); ?>" required></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Category <span class="req">*</span></label>
              <select name="category" class="form-select" id="cs" onchange="document.getElementById('ncw').style.display=this.value==='__new__'?'block':'none'">
                <option value="">-- Select --</option>
                <?php foreach($cats as $c): ?><option value="<?php echo htmlspecialchars($c); ?>" <?php echo (isset($_POST['category'])&&$_POST['category']===$c)?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?>
                <option value="__new__">+ New category...</option>
              </select>
            </div>
            <div class="form-group" id="ncw" style="display:none"><label class="form-label">New Category Name</label><input type="text" name="new_category" class="form-input" placeholder="e.g. Monitors"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Price (&#8377;) <span class="req">*</span></label><input type="number" name="price" class="form-input" placeholder="0" min="1" value="<?php echo htmlspecialchars($_POST['price']??''); ?>" required></div>
            <div class="form-group"><label class="form-label">Stock Qty <span class="req">*</span></label><input type="number" name="stock" class="form-input" placeholder="0" min="0" value="<?php echo htmlspecialchars($_POST['stock']??''); ?>" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Brand</label><input type="text" name="brand" class="form-input" placeholder="e.g. MSI, Corsair" value="<?php echo htmlspecialchars($_POST['brand']??''); ?>"></div>
            <div class="form-group"><label class="form-label">Warranty</label><input type="text" name="warranty" class="form-input" placeholder="e.g. 2 Years" value="<?php echo htmlspecialchars($_POST['warranty']??''); ?>"></div>
          </div>
          <div class="form-group"><label class="form-label">Product Image</label><input type="file" name="image" class="form-input" accept="image/jpeg,image/png,image/webp"><p style="color:var(--muted2);font-size:12px;margin-top:6px">JPG, PNG or WEBP, up to 4MB.</p></div>
          <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" placeholder="Optional..."><?php echo htmlspecialchars($_POST['description']??''); ?></textarea></div>
          <div class="form-actions"><a href="products.php" class="btn btn-ghost">Cancel</a><button type="submit" name="add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button></div>
        </form>
      </div>
      <div style="flex:0.7;display:flex;flex-direction:column;gap:16px">
        <div class="card"><div class="card-header"><span class="card-title">Existing Categories</span></div><div style="padding:16px;display:flex;flex-wrap:wrap;gap:8px"><?php foreach($cats as $c): ?><span class="badge badge-blue" style="cursor:pointer" onclick="var s=document.getElementById('cs');s.value='<?php echo addslashes($c); ?>';document.getElementById('ncw').style.display='none'"><?php echo cicon($c); ?> <?php echo htmlspecialchars($c); ?></span><?php endforeach; ?><?php if(empty($cats)): ?><p style="color:var(--muted2);font-size:13px">No categories yet.</p><?php endif; ?></div></div>
        <div class="card"><div class="card-header"><span class="card-title">Tips</span></div><div class="tip-list"><div class="tip-item"><i class="fas fa-lightbulb" style="color:#f59e0b"></i><span>Select existing or type a new category name.</span></div><div class="tip-item"><i class="fas fa-lightbulb" style="color:#f59e0b"></i><span>Set stock to 0 for out of stock items.</span></div></div></div>
      </div>
    </div>
  </div>
</div>
<script>document.addEventListener('click',function(e){var s=document.getElementById('sidebar');var t=document.getElementById('sidebarToggleBtn');if(s&&s.classList.contains('open')&&!s.contains(e.target)&&e.target!==t&&!(t&&t.contains(e.target)))s.classList.remove('open');});</script>
</body></html>
