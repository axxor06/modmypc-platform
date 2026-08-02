<?php
$__page_title = 'My Profile | ModMyPC';
require_once __DIR__ . '/../includes/header.php';
require_login('/auth/profile.php');

$user = current_user($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || strlen($name) > 100) {
            $errors[] = 'Please enter a valid name.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('ssi', $name, $phone, $user['id']);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $user['id'], 'profile_update', 'Updated name/phone');
            set_flash('success', 'Profile updated.');
            redirect('/auth/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('si', $hash, $user['id']);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $user['id'], 'password_change', 'Changed password');
            set_flash('success', 'Password changed successfully.');
            redirect('/auth/profile.php');
        }
    }
}
?>
<div class="container" style="max-width:560px;">
  <div class="card">
    <h1>My Profile</h1>
    <?php render_flash(); ?>
    <?php foreach ($errors as $err): ?><div class="site-flash flash-error"><?php echo e($err); ?></div><?php endforeach; ?>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="update_profile">
      <label>Name</label>
      <input type="text" name="name" required value="<?php echo e($user['name']); ?>">
      <label>Email</label>
      <input type="email" value="<?php echo e($user['email']); ?>" disabled>
      <label>Phone</label>
      <input type="tel" name="phone" value="<?php echo e($user['phone']); ?>">
      <button type="submit" class="btn" style="margin-top:18px;">Save Changes</button>
    </form>
  </div>

  <div class="card">
    <h2>Change Password</h2>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="change_password">
      <label>Current Password</label>
      <input type="password" name="current_password" required>
      <label>New Password</label>
      <input type="password" name="new_password" required minlength="8">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required minlength="8">
      <button type="submit" class="btn" style="margin-top:18px;">Update Password</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
