<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/csrf.php';

$__user = current_user($conn);
$__cart_count = $__user ? get_cart_count($conn, current_cart_id($conn)) : 0;
$__page_title = $__page_title ?? SITE_NAME;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($__page_title); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css">
<link rel="stylesheet" href="/assets/site.css">
<link rel="stylesheet" href="/assets/enhancements.css">
</head>
<body>
<header class="site-header">
  <div class="site-header-inner">
    <a href="/index.html" class="site-logo"><i class="fas fa-microchip"></i> ModMyPC</a>
    <nav class="site-nav">
      <a href="/products.php">Products</a>
      <a href="/builder.php">PC Builder</a>
      <a href="/compare.php"><i class="fas fa-code-compare"></i></a>
      <a href="/wishlist.php"><i class="far fa-heart"></i></a>
      <a href="/cart.php">Cart<?php if ($__cart_count > 0): ?><span class="cart-badge"><?php echo (int)$__cart_count; ?></span><?php endif; ?></a>
      <?php if ($__user): ?>
        <a href="/auth/my_builds.php">My Builds</a>
        <a href="/auth/profile.php"><i class="fas fa-user"></i> <?php echo e($__user['name']); ?></a>
        <a href="/auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="/auth/login.php">Login</a>
        <a href="/auth/register.php" class="btn btn-sm">Sign Up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.js" defer></script>
<script src="/assets/enhancements.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ if (typeof AOS !== 'undefined') AOS.init({ duration: 600, once: true, offset: 60 }); });</script>
