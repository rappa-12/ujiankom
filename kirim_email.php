<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/smtp_config.php';
require_once __DIR__ . '/includes/SimpleSMTPMailer.php';

$id = (int)($_POST['id'] ?? 0);
$emailTujuan = trim($_POST['email_tujuan'] ?? '');

$stmt = $pdo->prepare("
    SELECT g.*, k.nama, k.jabatan, p.nama_periode
    FROM gaji g
    JOIN karyawan k ON g.karyawan_id = k.id
    JOIN periode p ON g.periode_id = p.id
    WHERE g.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !filter_var($emailTujuan, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['email_error'] = 'Alamat email tujuan tidak valid.';
    header('Location: detail_gaji.php?id=' . $id);
    exit;
}

$subject = "Slip Gaji - {$row['nama']} ({$row['nama_periode']})";
$body = "Nama: {$row['nama']}\n"
      . "Jabatan: {$row['jabatan']}\n"
      . "Periode: {$row['nama_periode']}\n\n"
      . "Gaji Pokok: Rp " . number_format($row['gaji_pokok'], 0, ',', '.') . "\n"
      . "Lembur: Rp " . number_format($row['lembur'], 0, ',', '.') . "\n"
      . "Total Penghasilan: Rp " . number_format($row['total_penghasilan'], 0, ',', '.') . "\n"
      . "Pinjaman: Rp " . number_format($row['pinjaman'], 0, ',', '.') . "\n\n"
      . "GAJI BERSIH: Rp " . number_format($row['gaji_bersih'], 0, ',', '.');

$mailer = new SimpleSMTPMailer(
    $smtp_host, $smtp_port, $smtp_username, $smtp_password,
    $smtp_encryption, $smtp_from_email, $smtp_from_name
);

if ($mailer->send($emailTujuan, $subject, $body)) {
    $_SESSION['email_success'] = "Slip gaji berhasil dikirim ke $emailTujuan";
} else {
    $_SESSION['email_error'] = 'Gagal mengirim email: ' . $mailer->getError();
}

header('Location: detail_gaji.php?id=' . $id);
exit;
