<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
logout_user();
session_start();
set_flash('info', 'You have been logged out.');
redirect('/index.html');
