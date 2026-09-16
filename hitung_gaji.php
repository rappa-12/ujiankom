<?php
/**
 * Halaman Hitung Gaji.
 * File ini menggabungkan beberapa file lama supaya lebih sedikit:
 *  - pilih_periode.php   -> sekarang aksi "pilih_periode" di bawah
 *  - simpan_periode.php  -> sekarang aksi "tambah_periode" di bawah
 *  - simpan_gaji.php     -> sekarang aksi "simpan_gaji" di bawah
 *
 * PERBAIKAN BUG UTAMA:
 * Sebelumnya proses simpan gaji bisa error karena ada ketidakcocokan antara
 * kolom yang di-INSERT dengan struktur tabel `gaji` di database (mis. pernah
 * ada kolom "nama" di tabel gaji pada versi lama, padahal sekarang nama
 * karyawan diambil dari tabel `karyawan` lewat karyawan_id, BUKAN disimpan
 * ulang di tabel gaji). Sekarang:
 *  1. Kolom yang di-INSERT ke tabel `gaji` disamakan persis dengan struktur
 *     di database/payroll.sql (karyawan_id, periode_id, gaji_pokok, lembur,
 *     pinjaman, total_penghasilan, gaji_bersih, tanggal) — tidak ada kolom
 *     "nama" di sini.
 *  2. Proses simpan dibungkus try/catch supaya kalau memang ada bentrok
 *     struktur tabel di database milikmu, akan muncul PESAN ERROR YANG JELAS
 *     (bukan halaman putih/fatal error) yang memberitahu untuk meng-import
 *     ulang database/payroll.sql supaya struktur tabelnya sama seperti yang
 *     dipakai kode ini.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/periode_helper.php';

$active_page = 'hitung_gaji';
$error = $_SESSION['gaji_error'] ?? '';
unset($_SESSION['gaji_error']);

// Status Aktif/Tidak Aktif periode otomatis, sinkronkan dulu ke tanggal hari ini.
syncStatusPeriode($pdo);

// Tombol "Ganti Periode" -> lepas periode yang lagi dipakai, munculkan popup lagi.
if (isset($_GET['ganti_periode'])) {
    unset($_SESSION['periode_terpilih_id']);
    header('Location: hitung_gaji.php');
    exit;
}

// ---------- Proses POST (dibedakan lewat field "aksi") ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // 1) Pilih salah satu periode aktif yang sudah ada
    if ($aksi === 'pilih_periode') {
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM periode WHERE id = ? AND status = 'aktif'");
        $stmt->execute([$periodeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $_SESSION['periode_terpilih_id'] = $row['id'];
        } else {
            $_SESSION['gaji_error'] = 'Periode yang dipilih tidak valid atau sudah tidak aktif. Silakan pilih periode lain.';
        }
        header('Location: hitung_gaji.php');
        exit;
    }

    // 2) Tambah periode baru langsung dari popup, lalu langsung dipakai
    if ($aksi === 'tambah_periode') {
        $nama_periode    = trim($_POST['nama_periode'] ?? '');
        $tanggal_mulai   = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

        // Status TIDAK diinput manual — dihitung otomatis dari tanggal selesai.
        $status = hitungStatusPeriode($tanggal_selesai);

        $stmt = $pdo->prepare("INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_periode, $tanggal_mulai, $tanggal_selesai, $status]);
        $newId = $pdo->lastInsertId();

        if ($status === 'aktif') {
            $_SESSION['periode_terpilih_id'] = $newId;
        } else {
            $_SESSION['gaji_error'] = 'Periode baru langsung berstatus Tidak Aktif karena tanggal selesainya sudah lewat, jadi tidak bisa dipakai untuk hitung gaji.';
        }
        header('Location: hitung_gaji.php');
        exit;
    }

    // 3) Simpan hasil hitung gaji (INI YANG SEBELUMNYA ERROR)
    if ($aksi === 'simpan_gaji') {
        $captchaInput = strtoupper(trim($_POST['captcha'] ?? ''));

        if (empty($_SESSION['captcha_code']) || $captchaInput !== $_SESSION['captcha_code']) {
            $_SESSION['gaji_error'] = 'Kode captcha salah. Silakan coba lagi.';
            header('Location: hitung_gaji.php');
            exit;
        }
        unset($_SESSION['captcha_code']); // captcha hanya berlaku sekali

        $karyawanId = (int)($_POST['karyawan_id'] ?? 0);
        $periodeId  = (int)($_POST['periode_id'] ?? 0);
        $gajiPokok  = (int)($_POST['gaji_pokok'] ?? 0);
        $lembur     = (int)($_POST['lembur'] ?? 0);
        $pinjaman   = (int)($_POST['pinjaman'] ?? 0);

        try {
            // Pastikan karyawan yang dipilih benar-benar ada
            $cekKaryawan = $pdo->prepare("SELECT id FROM karyawan WHERE id = ?");
            $cekKaryawan->execute([$karyawanId]);
            if (!$cekKaryawan->fetch()) {
                throw new RuntimeException('Karyawan yang dipilih tidak ditemukan.');
            }

            // Pastikan periode yang dipilih masih berstatus aktif
            $cekPeriode = $pdo->prepare("SELECT status FROM periode WHERE id = ?");
            $cekPeriode->execute([$periodeId]);
            $periode = $cekPeriode->fetch(PDO::FETCH_ASSOC);

            if (!$periode || $periode['status'] !== 'aktif') {
                throw new RuntimeException('Periode yang dipilih sudah tidak aktif. Silakan pilih periode lain.');
            }

            // Rumus perhitungan gaji
            $totalPenghasilan = $gajiPokok + $lembur;
            $gajiBersih = $totalPenghasilan - $pinjaman;

            // PENTING: kolom di bawah ini HARUS sama persis dengan kolom
            // tabel `gaji` di database/payroll.sql. Tidak ada kolom "nama"
            // di tabel gaji, karena nama karyawan diambil lewat JOIN ke
            // tabel karyawan (lihat detail_gaji.php / riwayat.php).
            $stmt = $pdo->prepare("
                INSERT INTO gaji (karyawan_id, periode_id, gaji_pokok, lembur, pinjaman, total_penghasilan, gaji_bersih, tanggal)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([$karyawanId, $periodeId, $gajiPokok, $lembur, $pinjaman, $totalPenghasilan, $gajiBersih]);

            $id = $pdo->lastInsertId();
            header("Location: detail_gaji.php?id=$id");
            exit;
        } catch (Throwable $e) {
            // Kalau memang masih ada bentrok struktur tabel di database
            // (mis. pesan "Unknown column" / "doesn't have a default value"),
            // tampilkan pesan yang jelas + solusinya, bukan halaman error PHP.
            $_SESSION['gaji_error'] = 'Gagal menyimpan gaji: ' . $e->getMessage()
                . '. Coba import ulang database/payroll.sql supaya struktur tabel `gaji` sesuai dengan kode terbaru.';
            header('Location: hitung_gaji.php');
            exit;
        }
    }
}

// ---------- Tampilan halaman ----------

// Ambil periode yang sedang dipakai (kalau ada & masih aktif).
$periodeTerpilih = null;
$periodeTerpilihId = (int)($_SESSION['periode_terpilih_id'] ?? 0);
if ($periodeTerpilihId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM periode WHERE id = ? AND status = 'aktif'");
    $stmt->execute([$periodeTerpilihId]);
    $periodeTerpilih = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$periodeTerpilih) {
        unset($_SESSION['periode_terpilih_id']);
    }
}

$wajibPilihPeriode = !$periodeTerpilih;
$daftarPeriodeAktif = $pdo->query("SELECT * FROM periode WHERE status = 'aktif' ORDER BY tanggal_mulai DESC")->fetchAll(PDO::FETCH_ASSOC);

$daftarKaryawan = [];
if ($periodeTerpilih) {
    $daftarKaryawan = $pdo->query("SELECT * FROM karyawan ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hitung Gaji - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>Hitung Gaji</h1>
        <p class="page-subtitle">Masukkan informasi karyawan</p>

        <?php if ($error): ?>
            <div class="error-msg" style="max-width:600px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($periodeTerpilih): ?>
            <div class="card" style="max-width:600px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div>
                    <div class="card-label">Periode Dipakai</div>
                    <div style="font-weight:bold;">
                        <?= htmlspecialchars($periodeTerpilih['nama_periode']) ?>
                        <span class="badge badge-aktif" style="margin-left:6px;">Aktif</span>
                    </div>
                    <div style="font-size:13px;color:#7a756d;margin-top:2px;">
                        <?= date('d/m/Y', strtotime($periodeTerpilih['tanggal_mulai'])) ?> &ndash;
                        <?= date('d/m/Y', strtotime($periodeTerpilih['tanggal_selesai'])) ?>
                    </div>
                </div>
                <a href="hitung_gaji.php?ganti_periode=1" class="action-link">Ganti Periode</a>
            </div>
        <?php endif; ?>

        <?php if (!$periodeTerpilih): ?>
            <div class="card" style="max-width:600px;">
                Pilih atau tambahkan periode dulu lewat popup di sebelah untuk mulai menghitung gaji.
            </div>
        <?php elseif (empty($daftarKaryawan)): ?>
            <div class="card" style="max-width:600px;">
                Data karyawan masih kosong. Silakan tambahkan dulu lewat menu
                <a href="karyawan.php" style="text-decoration:underline;">Karyawan</a>.
            </div>
        <?php else: ?>

        <div class="hitung-layout">
            <!-- Form input -->
            <div class="card form-panel">
                <h3 style="margin-top:0;">Data Karyawan</h3>
                <form action="hitung_gaji.php" method="POST">
                    <input type="hidden" name="aksi" value="simpan_gaji">
                    <input type="hidden" name="periode_id" value="<?= $periodeTerpilih['id'] ?>">

                    <label for="karyawan_id">Pilih Karyawan</label>
                    <select id="karyawan_id" name="karyawan_id" required>
                        <option value="">Pilih Karyawan</option>
                        <?php foreach ($daftarKaryawan as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nik'] . ' - ' . $k['nama'] . ' (' . $k['jabatan'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="gaji_pokok">Gaji Pokok</label>
                    <input type="number" id="gaji_pokok" name="gaji_pokok" placeholder="Rp" oninput="hitungPreview()" required>

                    <label for="lembur">Lembur</label>
                    <input type="number" id="lembur" name="lembur" placeholder="Rp" value="0" oninput="hitungPreview()">

                    <label for="pinjaman">Pinjaman</label>
                    <input type="number" id="pinjaman" name="pinjaman" placeholder="Rp" value="0" oninput="hitungPreview()">

                    <!-- Captcha (tetap ada di halaman Hitung Gaji, hanya dihapus dari halaman Login) -->
                    <label>Captcha</label>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <img id="captchaImg" src="captcha.php" alt="Captcha" style="border:1px solid #ddd9d3;border-radius:8px;">
                        <span onclick="refreshCaptcha()" style="cursor:pointer;font-size:13px;text-decoration:underline;">&#8635; Refresh</span>
                    </div>
                    <input type="text" name="captcha" placeholder="Masukkan kode captcha di atas" required style="margin-bottom:14px;">

                    <button type="submit" class="btn">HITUNG GAJI</button>
                </form>
            </div>

            <!-- Ringkasan live preview -->
            <div class="card summary-panel">
                <h3 style="margin-top:0;">Ringkasan</h3>

                <div class="card-label">Total Penghasilan</div>
                <div class="card-value" id="preview_penghasilan">Rp 0</div>

                <div class="card-label" style="margin-top:14px;">Total Potongan</div>
                <div class="card-value" id="preview_potongan" style="font-size:20px;">Rp 0</div>

                <div class="gaji-bersih-box">
                    <span>GAJI BERSIH</span>
                    <span class="value" id="preview_bersih">Rp 0</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php if ($wajibPilihPeriode): ?>
<!-- Popup wajib: pilih periode aktif atau tambah periode baru, sebelum bisa hitung gaji -->
<div class="modal-overlay open" id="periodeModal">
    <div class="modal-box" style="max-width:440px;">
        <h3>Pilih Periode Penggajian</h3>
        <p style="font-size:13px;color:#7a756d;margin:-8px 0 14px;">
            Periode wajib dipilih/dibuat dulu sebelum bisa menghitung gaji.
        </p>

        <?php if (!empty($daftarPeriodeAktif)): ?>
            <form action="hitung_gaji.php" method="POST" style="margin-bottom:14px;">
                <input type="hidden" name="aksi" value="pilih_periode">
                <label for="periode_id">Periode Aktif</label>
                <select id="periode_id" name="periode_id" required>
                    <option value="">Pilih Periode</option>
                    <?php foreach ($daftarPeriodeAktif as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['nama_periode']) ?>
                            (<?= date('d/m/Y', strtotime($p['tanggal_mulai'])) ?> - <?= date('d/m/Y', strtotime($p['tanggal_selesai'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn" style="width:100%;margin-top:10px;">GUNAKAN PERIODE INI</button>
            </form>
            <div style="text-align:center;font-size:12px;color:#a39d92;margin:12px 0;">&mdash; atau tambah periode baru &mdash;</div>
        <?php else: ?>
            <div class="error-msg" style="margin-bottom:14px;">
                Belum ada periode aktif. Tambahkan periode baru di bawah ini.
            </div>
        <?php endif; ?>

        <form action="hitung_gaji.php" method="POST">
            <input type="hidden" name="aksi" value="tambah_periode">

            <label for="nama_periode_baru">Nama Periode Baru</label>
            <input type="text" id="nama_periode_baru" name="nama_periode" placeholder="Contoh: Oktober 2026" required>

            <label for="tanggal_mulai_baru">Tanggal Mulai</label>
            <input type="date" id="tanggal_mulai_baru" name="tanggal_mulai" required>

            <label for="tanggal_selesai_baru">Tanggal Selesai</label>
            <input type="date" id="tanggal_selesai_baru" name="tanggal_selesai" required>

            <p style="font-size:12px;color:#a39d92;margin:4px 0 10px;">
                Status Aktif/Tidak Aktif otomatis: aktif selama tanggal selesai belum
                lewat dari hari ini.
            </p>

            <button type="submit" class="btn btn-outline" style="width:100%;">+ TAMBAH &amp; GUNAKAN PERIODE INI</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
