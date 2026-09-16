<?php
/**
 * Konfigurasi koneksi database.
 * Sesuaikan $host, $db_name, $username, $password dengan setup lokal Anda.
 */

$host     = 'localhost';
$db_name  = 'payroll_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}
