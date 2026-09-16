<?php
/**
 * Halaman Login.
 * File ini digabung: menampilkan form (GET) SEKALIGUS memproses login (POST),
 * supaya tidak perlu file terpisah login_process.php lagi.
 * Captcha SUDAH DIHAPUS dari halaman login sesuai permintaan.
 */
session_start();
require_once __DIR__ . '/config/database.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// ---------- Proses login (kalau form dikirim lewat POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_email'] = $user['email'];
        header('Location: dashboard.php');
        exit;
    }

    $_SESSION['login_error'] = 'Email atau password salah.';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <h1>PAYROLL</h1>
        <p class="subtitle">Sistem Penggajian</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit" class="btn">MASUK</button>
        </form>
    </div>
</div>

</body>
</html>
