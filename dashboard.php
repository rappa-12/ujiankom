<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$active_page = 'dashboard';

// Total karyawan = semua data di tabel karyawan (bukan dari tabel gaji, dan tidak dibatasi)
$totalKaryawan = $pdo->query("SELECT COUNT(*) FROM karyawan")->fetchColumn();

// Total gaji bersih yang dihitung bulan ini
$stmtBulanIni = $pdo->query("
    SELECT COALESCE(SUM(gaji_bersih), 0) AS total
    FROM gaji
    WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())
");
$gajiBulanIni = $stmtBulanIni->fetchColumn();

// 3 riwayat terakhir (join ke karyawan supaya dapat nama)
$riwayat = $pdo->query("
    SELECT g.*, k.nama
    FROM gaji g
    JOIN karyawan k ON g.karyawan_id = k.id
    ORDER BY g.id DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>Dashboard</h1>
        <p class="page-subtitle">Halaman utama</p>

        <div class="card-row">
            <div class="card">
                <div class="card-label">Total Karyawan</div>
                <div class="card-value"><?= (int)$totalKaryawan ?></div>
            </div>
            <div class="card">
                <div class="card-label">Gaji Bulan Ini</div>
                <div class="card-value">Rp <?= number_format($gajiBulanIni, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Mulai Hitung Gaji</h3>
            <p style="color:#7a756d;">Hitung gaji karyawan dengan cepat</p>
            <a href="hitung_gaji.php" class="btn" style="width:auto;display:inline-block;padding:12px 24px;">HITUNG GAJI</a>
        </div>

        <h3>Riwayat Terakhir</h3>
        <div class="card" style="padding:0;">
            <?php foreach ($riwayat as $item): ?>
                <div style="display:flex;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #ddd9d3;">
                    <span><?= htmlspecialchars($item['nama']) ?></span>
                    <span>Rp <?= number_format($item['gaji_bersih'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($riwayat)): ?>
                <div style="padding:20px;color:#7a756d;">Belum ada data.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
