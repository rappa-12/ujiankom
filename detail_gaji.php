<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$active_page = 'riwayat';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT g.*, k.nama, k.jabatan, p.nama_periode
    FROM gaji g
    JOIN karyawan k ON g.karyawan_id = k.id
    JOIN periode p ON g.periode_id = p.id
    WHERE g.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: riwayat.php');
    exit;
}

$emailSuccess = $_SESSION['email_success'] ?? '';
$emailError = $_SESSION['email_error'] ?? '';
unset($_SESSION['email_success'], $_SESSION['email_error']);

// Pesan yang otomatis terisi begitu WhatsApp Web terbuka.
$pesanWa = "Slip Gaji\n"
         . "Nama: {$row['nama']}\n"
         . "Jabatan: {$row['jabatan']}\n"
         . "Periode: {$row['nama_periode']}\n"
         . "Gaji Bersih: Rp " . number_format($row['gaji_bersih'], 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Gaji - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <a href="riwayat.php" class="back-link no-print">&larr; Kembali</a>
        <h1>Detail Gaji</h1>
        <p class="page-subtitle">Slip penggajian - <?= htmlspecialchars($row['nama_periode']) ?></p>

        <?php if ($emailSuccess): ?><div class="success-msg" style="max-width:600px;"><?= htmlspecialchars($emailSuccess) ?></div><?php endif; ?>
        <?php if ($emailError): ?><div class="error-msg" style="max-width:600px;"><?= htmlspecialchars($emailError) ?></div><?php endif; ?>

        <div class="card" style="max-width:600px;">
            <h3 style="margin-top:0;">Detail Penggajian</h3>

            <div class="card-label">Nama</div>
            <div style="font-weight:bold;margin-bottom:12px;"><?= htmlspecialchars($row['nama']) ?></div>

            <div class="card-label">Jabatan</div>
            <div style="margin-bottom:12px;"><?= htmlspecialchars($row['jabatan']) ?></div>

            <div class="slip-section-title">PENGHASILAN</div>
            <div class="slip-row"><span>Gaji Pokok</span><span>Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></span></div>
            <div class="slip-row"><span>Lembur</span><span>Rp <?= number_format($row['lembur'], 0, ',', '.') ?></span></div>
            <div class="slip-total"><span>Total Penghasilan</span><span>Rp <?= number_format($row['total_penghasilan'], 0, ',', '.') ?></span></div>

            <div class="slip-section-title">POTONGAN</div>
            <div class="slip-row"><span>Pinjaman</span><span>Rp <?= number_format($row['pinjaman'], 0, ',', '.') ?></span></div>

            <div class="gaji-bersih-box">
                <span>GAJI BERSIH</span>
                <span class="value">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></span>
            </div>

            <div class="btn-row no-print" style="margin-top:16px;">
                <button class="btn" onclick="cetakPDF()">CETAK PDF</button>
                <button class="btn btn-outline" onclick="openModal('emailModal')">EMAIL</button>
                <button class="btn btn-outline" onclick="openModal('waModal')">WHATSAPP</button>
            </div>
        </div>
    </main>
</div>

<!-- Popup Kirim via Email -->
<div class="modal-overlay" id="emailModal">
    <div class="modal-box">
        <h3>Kirim via Email</h3>
        <form action="kirim_email.php" method="POST">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">

            <label for="email_tujuan">Email Tujuan</label>
            <input type="email" id="email_tujuan" name="email_tujuan" placeholder="contoh@email.com" required>

            <div class="btn-row" style="justify-content:flex-end;margin-top:6px;">
                <button type="button" class="btn btn-outline" style="flex:none;padding:10px 18px;" onclick="closeModal('emailModal')">BATAL</button>
                <button type="submit" class="btn" style="flex:none;padding:10px 18px;">KIRIM</button>
            </div>
        </form>
    </div>
</div>

<!-- Popup Kirim via WhatsApp: langsung buka WhatsApp Web dengan pesan sudah terisi -->
<div class="modal-overlay" id="waModal">
    <div class="modal-box">
        <h3>Kirim via WhatsApp</h3>
        <form onsubmit="return kirimWhatsAppWeb(event)" data-pesan="<?= htmlspecialchars($pesanWa, ENT_QUOTES) ?>">
            <label for="nomor_wa">Nomor WhatsApp</label>
            <input type="text" id="nomor_wa" name="nomor_wa" placeholder="628123456789" required>
            <p style="font-size:12px;color:#a39d92;margin:-4px 0 6px;">
                Akan membuka WhatsApp Web di tab baru dengan data slip gaji sudah terisi otomatis.
            </p>

            <div class="btn-row" style="justify-content:flex-end;margin-top:6px;">
                <button type="button" class="btn btn-outline" style="flex:none;padding:10px 18px;" onclick="closeModal('waModal')">BATAL</button>
                <button type="submit" class="btn" style="flex:none;padding:10px 18px;">KIRIM</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
