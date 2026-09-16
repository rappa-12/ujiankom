<?php
/**
 * Helper status periode otomatis (bukan diatur manual lagi).
 *
 * Aturan: periode dianggap aktif selama tanggal_selesai >= hari ini
 * (periode sekarang & periode yang akan datang = aktif).
 * Begitu tanggal_selesai sudah lewat dari hari ini, periode otomatis
 * jadi tidak aktif. Contoh:
 *   - Periode "bulan sekarang s/d bulan depan" -> tanggal selesai masih
 *     di masa depan -> aktif.
 *   - Periode "Januari 2026 s/d Februari 2026" (sudah lewat) -> tidak aktif.
 */

/**
 * Hitung status untuk satu tanggal selesai.
 */
function hitungStatusPeriode(string $tanggalSelesai): string
{
    if ($tanggalSelesai === '') {
        return 'tidak_aktif';
    }
    return ($tanggalSelesai >= date('Y-m-d')) ? 'aktif' : 'tidak_aktif';
}

/**
 * Sinkronkan ulang kolom status semua baris periode di database supaya
 * selalu sesuai dengan tanggal hari ini (dipanggil di setiap halaman yang
 * menampilkan/menggunakan status periode, karena status bisa berubah
 * hanya karena waktu berjalan, tanpa ada yang mengedit periode-nya).
 */
function syncStatusPeriode(PDO $pdo): void
{
    $pdo->exec("
        UPDATE periode
        SET status = IF(tanggal_selesai >= CURDATE(), 'aktif', 'tidak_aktif')
    ");
}
