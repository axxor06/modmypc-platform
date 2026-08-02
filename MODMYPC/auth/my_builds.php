<?php
$__page_title = 'My Builds | ModMyPC';
require_once __DIR__ . '/../includes/header.php';
require_login('/auth/my_builds.php');

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $bid = (int)$_POST['build_id'];
    $stmt = $conn->prepare("DELETE FROM saved_builds WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $bid, $uid);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Build deleted.');
    redirect('/auth/my_builds.php');
}

$stmt = $conn->prepare("SELECT * FROM saved_builds WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$builds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="container" style="max-width:800px;">
  <div class="card">
    <h1>My Saved Builds</h1>
    <?php render_flash(); ?>
    <?php if (empty($builds)): ?>
      <p class="muted">No saved builds yet. <a href="/builder.php">Start building</a>.</p>
    <?php else: ?>
      <?php foreach ($builds as $b):
        $components = json_decode($b['components_json'], true) ?: []; ?>
        <div class="card" style="box-shadow:none; border:1px solid #eee;">
          <h3><?php echo e($b['build_name']); ?></h3>
          <p class="muted">Saved on <?php echo e(date('d M Y, H:i', strtotime($b['created_at']))); ?></p>
          <ul>
            <?php foreach ($components as $cat => $item): if (!$item) continue; ?>
              <li><?php echo e($cat); ?>: <?php echo e($item['name']); ?> (<?php echo money($item['price']); ?>)</li>
            <?php endforeach; ?>
          </ul>
          <p><strong>Total: <?php echo money($b['total_price']); ?></strong></p>
          <div style="display:flex; gap:8px;">
            <a href="/builder.php?load=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline">Load / Edit</a>
            <form method="POST">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="build_id" value="<?php echo (int)$b['id']; ?>">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
