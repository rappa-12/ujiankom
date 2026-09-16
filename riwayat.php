<?php
/**
 * Halaman Riwayat Gaji.
 * Edit & Hapus digabung ke file ini (sebelumnya terpisah di edit_gaji.php,
 * update_gaji.php, hapus_gaji.php) supaya jumlah file lebih sedikit.
 * Edit memakai modal popup, sama seperti pola di karyawan.php.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$active_page = 'riwayat';

// ---------- Proses update (dari modal Edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)($_POST['id'] ?? 0);
    $karyawanId = (int)($_POST['karyawan_id'] ?? 0);
    $periodeId  = (int)($_POST['periode_id'] ?? 0);
    $gajiPokok  = (int)($_POST['gaji_pokok'] ?? 0);
    $lembur     = (int)($_POST['lembur'] ?? 0);
    $pinjaman   = (int)($_POST['pinjaman'] ?? 0);

    $totalPenghasilan = $gajiPokok + $lembur;
    $gajiBersih = $totalPenghasilan - $pinjaman;

    $stmt = $pdo->prepare("
        UPDATE gaji
        SET karyawan_id = ?, periode_id = ?, gaji_pokok = ?, lembur = ?, pinjaman = ?,
            total_penghasilan = ?, gaji_bersih = ?
        WHERE id = ?
    ");
    $stmt->execute([$karyawanId, $periodeId, $gajiPokok, $lembur, $pinjaman, $totalPenghasilan, $gajiBersih, $id]);

    header('Location: riwayat.php');
    exit;
}

// ---------- Proses hapus ----------
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM gaji WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: riwayat.php');
    exit;
}

$data = $pdo->query("
    SELECT g.*, k.nama, k.jabatan, p.nama_periode
    FROM gaji g
    JOIN karyawan k ON g.karyawan_id = k.id
    JOIN periode p ON g.periode_id = p.id
    ORDER BY g.tanggal DESC, g.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk dropdown di modal Edit
$daftarKaryawan = $pdo->query("SELECT * FROM karyawan ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$daftarPeriode  = $pdo->query("SELECT * FROM periode ORDER BY tanggal_mulai DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat - Payroll</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>Riwayat</h1>
        <p class="page-subtitle">Riwayat Penggajian</p>

        <div class="card">
            <div class="table-toolbar">
                <input type="text" id="searchInput" class="search-box" placeholder="Cari nama karyawan..." onkeyup="filterTable('tabelRiwayat')">
            </div>

            <table id="tabelRiwayat">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Periode</th>
                        <th>Gaji Bersih</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="6" style="color:#7a756d;">Belum ada data penggajian.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($data as $i => $row): ?>
                        <tr data-nama="<?= htmlspecialchars($row['nama']) ?>">
                            <td><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['jabatan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                            <td>Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                            <td>
                                <a class="action-link" href="detail_gaji.php?id=<?= $row['id'] ?>">Lihat</a>
                                <span class="action-link" style="cursor:pointer;"
                                      onclick="bukaEditGaji(<?= $row['id'] ?>, <?= $row['karyawan_id'] ?>, <?= $row['periode_id'] ?>, <?= (int)$row['gaji_pokok'] ?>, <?= (int)$row['lembur'] ?>, <?= (int)$row['pinjaman'] ?>)">
                                    Edit
                                </span>
                                <span class="action-link danger"
                                      onclick="openDeleteModal('riwayat.php?hapus=<?= $row['id'] ?>', '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')">
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

<!-- Modal Edit Gaji -->
<div class="modal-overlay" id="gajiModal">
    <div class="modal-box">
        <h3>Edit Data Gaji</h3>
        <form action="riwayat.php" method="POST">
            <input type="hidden" id="gaji_id" name="id" value="">

            <label for="edit_karyawan_id">Karyawan</label>
            <select id="edit_karyawan_id" name="karyawan_id" required>
                <?php foreach ($daftarKaryawan as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nik'] . ' - ' . $k['nama']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="edit_periode_id">Periode</label>
            <select id="edit_periode_id" name="periode_id" required>
                <?php foreach ($daftarPeriode as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_periode']) ?><?= $p['status'] !== 'aktif' ? ' (Tidak Aktif)' : '' ?></option>
                <?php endforeach; ?>
            </select>

            <label for="edit_gaji_pokok">Gaji Pokok</label>
            <input type="number" id="edit_gaji_pokok" name="gaji_pokok" required>

            <label for="edit_lembur">Lembur</label>
            <input type="number" id="edit_lembur" name="lembur">

            <label for="edit_pinjaman">Pinjaman</label>
            <input type="number" id="edit_pinjaman" name="pinjaman">

            <div class="btn-row" style="justify-content:flex-end;margin-top:10px;">
                <button type="button" class="btn btn-outline" style="flex:none;padding:10px 18px;" onclick="closeModal('gajiModal')">BATAL</button>
                <button type="submit" class="btn" style="flex:none;padding:10px 18px;">SIMPAN</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/delete_modal.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
