<?php
require_once __DIR__ . '/includes/admin_auth.php';
admin_logout();
header("Location: login.php");
exit;
