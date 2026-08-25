<?php
/**
 * PHANTOM DETECTIVE - Database Connection
 * Uses PDO (MySQL) so we can rely on real prepared statements everywhere.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'phantom_detective';
$DB_USER = 'root';
$DB_PASS = '';       // <-- set your XAMPP MySQL root password here if you have one

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Make sure XAMPP MySQL is running and the '
        . '"phantom_detective" database has been imported. Error: ' . $e->getMessage());
}
