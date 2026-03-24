<?php
// includes/db.php — Database connection
// Include with: require_once __DIR__ . '/db.php';      (from includes/)
//               require_once __DIR__ . '/../includes/db.php'; (from admin/)
$host = 'localhost';
$db   = 'labs_project';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;color:red;padding:30px">
    <strong>Database Error:</strong> Make sure XAMPP MySQL is running and you ran setup.sql in phpMyAdmin.<br>
    ' . htmlspecialchars($e->getMessage()) . '</p>');
}
