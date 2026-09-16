<?php
/**
 * Halaman Karyawan.
 * Semua proses CRUD (Tambah, Edit, Hapus) digabung dalam SATU file ini
 * (sebelumnya terpisah di tambah_karyawan.php, simpan_karyawan.php,
 * edit_karyawan.php, update_karyawan.php, hapus_karyawan.php) supaya
 * jumlah file lebih sedikit tapi fungsinya tetap sama:
 *  - Tambah & Edit pakai modal popup yang sama (form kirim ke file ini juga).
 *  - Kalau field "id" kosong  -> proses INSERT (tambah data baru).
 *  - Kalau field "id" terisi  -> proses UPDATE (edit data lama).
 *  - Hapus lewat parameter ?hapus=ID (GET), sama seperti sebelumnya.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$active_page = 'karyawan';
$error = $_SESSION['karyawan_error'] ?? '';
unset($_SESSION['karyawan_error']);

// ---------- Proses simpan (Tambah / Edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $nik     = trim($_POST['nik'] ?? '');
    $nama    = trim($_POST['nama'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');

    if ($nik === '' || $nama === '' || $jabatan === '') {
        $_SESSION['karyawan_error'] = 'Semua field wajib diisi.';
        header('Location: karyawan.php');
        exit;
    }

    // Cek NIK jangan sampai dobel (kecuali punya baris yang sedang diedit)
    $cek = $pdo->prepare("SELECT id FROM karyawan WHERE nik = ? AND id != ?");
    $cek->execute([$nik, $id]);

    if ($cek->rowCount() > 0) {
        $_SESSION['karyawan_error'] = 'NIK sudah terdaftar, gunakan NIK lain.';
        header('Location: karyawan.php');
        exit;
    }

    if ($id > 0) {
        // Edit data lama
        $stmt = $pdo->prepare("UPDATE karyawan SET nik = ?, nama = ?, jabatan = ? WHERE id = ?");
        $stmt->execute([$nik, $nama, $jabatan, $id]);
    } else {
        // Tambah data baru
        $stmt = $pdo->prepare("INSERT INTO karyawan (nik, nama, jabatan) VALUES (?, ?, ?)");
        $stmt->execute([$nik, $nama, $jabatan]);
    }

    header('Location: karyawan.php');
    exit;
}

// ---------- Proses hapus ----------
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM karyawan WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: karyawan.php');
    exit;
}

$daftarJabatan = ['Staff IT', 'Staff HRD', 'Staff Finance', 'Manager', 'Office Boy'];
$data = $pdo->query("SELECT * FROM karyawan ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Karyawan - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>Karyawan</h1>
        <p class="page-subtitle">Data master karyawan</p>

        <?php if ($error): ?>
            <div class="error-msg" style="max-width:600px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-toolbar">
                <span class="btn" style="width:auto;padding:10px 18px;cursor:pointer;" onclick="bukaTambahKaryawan()">+ Tambah Karyawan</span>
                <input type="text" id="searchInput" class="search-box" placeholder="Cari nama karyawan..." onkeyup="filterTable('tabelKaryawan')">
            </div>

            <table id="tabelKaryawan">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="4" style="color:#7a756d;">Belum ada data karyawan.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($data as $row): ?>
                        <tr data-nama="<?= htmlspecialchars($row['nama']) ?>">
                            <td><?= htmlspecialchars($row['nik']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['jabatan']) ?></td>
                            <td>
                                <span class="action-link" style="cursor:pointer;"
                                      onclick="bukaEditKaryawan(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nik'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['jabatan'], ENT_QUOTES) ?>')">
                                    Edit
                                </span>
                                <span class="action-link danger"
                                      onclick="openDeleteModal('karyawan.php?hapus=<?= $row['id'] ?>', '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')">
                                    Hapus
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal Tambah / Edit Karyawan (dipakai bareng untuk kedua aksi) -->
<div class="modal-overlay" id="karyawanModal">
    <div class="modal-box">
        <h3 id="karyawanModalTitle">Tambah Karyawan</h3>
        <form action="karyawan.php" method="POST">
            <input type="hidden" id="karyawan_id" name="id" value="">

            <label for="nik">NIK</label>
            <input type="text" id="nik" name="nik" placeholder="Contoh: EMP004" required>

            <label for="nama">Nama Karyawan</label>
            <input type="text" id="nama" name="nama" placeholder="Nama Karyawan" required>

            <label for="jabatan">Jabatan</label>
            <select id="jabatan" name="jabatan" required>
                <option value="">Pilih Jabatan</option>
                <?php foreach ($daftarJabatan as $jbt): ?>
                    <option value="<?= $jbt ?>"><?= $jbt ?></option>
                <?php endforeach; ?>
            </select>

            <div class="btn-row" style="justify-content:flex-end;margin-top:10px;">
                <button type="button" class="btn btn-outline" style="flex:none;padding:10px 18px;" onclick="closeModal('karyawanModal')">BATAL</button>
                <button type="submit" class="btn" style="flex:none;padding:10px 18px;">SIMPAN</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/delete_modal.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
