<?php
/**
 * Shared DB connection. Include this (not config.php's constants directly)
 * from any script that needs $conn.
 */
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Sorry, something went wrong. Please try again shortly.');
}

$conn->set_charset('utf8mb4');
