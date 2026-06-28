<?php
// ONE-TIME PASSWORD FIX SCRIPT
// Upload to Hostinger, visit the URL once, then DELETE immediately.
// URL: https://hkbuildersanddevelopers.com/crm/fix-passwords.php

define('APP_ROOT', is_dir(__DIR__ . '/../app') ? __DIR__ . '/..' : __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

$db   = Database::connect();
$hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);

$emails = [
    'admin@hkbuilders.com',
    'usman@hkbuilders.com',
    'hina@hkbuilders.com',
    'ali@hkbuilders.com',
    'sara@hkbuilders.com',
    'bilal@hkbuilders.com',
    'fatima@hkbuilders.com',
    'kamran@hkbuilders.com',
    'zainab@hkbuilders.com',
];

$stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
$updated = 0;
foreach ($emails as $email) {
    $stmt->execute([$hash, $email]);
    $updated += $stmt->rowCount();
}

echo "<h2>Done — $updated password(s) updated to <b>Admin@1234</b></h2>";
echo "<p style='color:red'><b>DELETE this file from Hostinger immediately!</b></p>";
echo "<p>File to delete: <code>public_html/crm/fix-passwords.php</code></p>";
