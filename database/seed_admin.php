<?php
/**
 * Jalankan file ini SATU KALI lewat browser untuk membuat akun admin.
 * Contoh: http://localhost/payroll-app/database/seed_admin.php
 */
require_once __DIR__ . '/../config/database.php';

$email = 'admin@gmail.com';
$password_plain = 'admin123'; // ganti sesuai keinginan sebelum dijalankan
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

$cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$cek->execute([$email]);

if ($cek->rowCount() > 0) {
    echo "Akun admin sudah ada. Silakan login dengan email: $email";
} else {
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $password_hash]);
    echo "Akun admin berhasil dibuat!<br>";
    echo "Email: $email<br>";
    echo "Password: $password_plain<br>";
    echo "<b>Hapus file seed_admin.php ini setelah dipakai.</b>";
}
