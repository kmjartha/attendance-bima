<?php
require __DIR__ . '/app-core/config/env.php';
$db = require __DIR__ . '/app-core/config/database.php';
$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $db['driver'], $db['host'], $db['port'], $db['database'], $db['charset']);
$pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
$stmt = $pdo->query('SHOW CREATE TABLE users');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "CREATE TABLE users:\n" . $row['Create Table'] . "\n\n";
$stmt = $pdo->query('SHOW CREATE TABLE roles');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "CREATE TABLE roles:\n" . $row['Create Table'] . "\n\n";
$stmt2 = $pdo->query('SELECT id, niy, role_id, is_active, CHAR_LENGTH(password) AS len FROM users ORDER BY id DESC LIMIT 10');
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r, JSON_UNESCAPED_SLASHES) . "\n";
}
