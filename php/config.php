<?php
// NetMatrix Configuration
// Author: Amir Hossein Nourzadeh

define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'netmatrix');
define('DB_USER', getenv('DB_USER') ?: 'netuser');
define('DB_PASS', getenv('DB_PASS') ?: 'netpass123');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// تنظیمات زمانی
date_default_timezone_set('Asia/Tehran');
