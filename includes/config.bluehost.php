<?php
// Bluehost production config.
// Upload this file to public_html/includes/ and RENAME it to config.php.
// Do NOT commit this file — it contains real credentials.

define('DB_HOST',    'localhost');
define('DB_NAME',    'yourusername_peddi');   // ← replace with your full DB name from cPanel
define('DB_USER',    'yourusername_peddi');   // ← replace with your full DB user from cPanel
define('DB_PASS',    'your_password_here');   // ← replace with the password you set
define('DB_CHARSET', 'utf8mb4');

// Deployed at domain root (peddi.jasthi.com) → APP_BASE is empty string.
define('APP_BASE', '');
