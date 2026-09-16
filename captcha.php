<?php
/**
 * Membuat gambar captcha berisi 5 huruf/angka acak.
 * Kode jawabannya disimpan di session untuk dicek saat form disubmit.
 * File ini dipanggil lewat tag <img src="captcha.php">.
 *
 * Catatan perbaikan: sebelumnya captcha SELALU digambar pakai ekstensi GD
 * (imagecreatetruecolor, dll). Kalau GD tidak aktif di server/XAMPP, gambar
 * jadi "broken image" / tidak terdeteksi browser. Sekarang, kalau GD tidak
 * tersedia, captcha otomatis digambar pakai SVG (teks biasa, tidak butuh
 * ekstensi apa pun) supaya captcha tetap muncul dan tetap bisa dipakai.
 */
session_start();

// Karakter yang dipakai (huruf O/0, I/1/l sengaja dihilangkan biar tidak membingungkan)
$karakter = 'ABCDEFGHJKLMNPQRTUVWXY346789';
$kode = '';
for ($i = 0; $i < 5; $i++) {
    $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
}
$_SESSION['captcha_code'] = $kode;

header('Cache-Control: no-store, no-cache, must-revalidate');

$width  = 140;
$height = 50;

if (extension_loaded('gd')) {
    // ---------- Versi GD (gambar PNG) ----------
    header('Content-Type: image/png');

    $img = imagecreatetruecolor($width, $height);
    $bg  = imagecolorallocate($img, 245, 243, 240);
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);

    // Garis acak sebagai gangguan visual
    for ($i = 0; $i < 6; $i++) {
        $warnaGaris = imagecolorallocate($img, random_int(190, 220), random_int(190, 220), random_int(190, 220));
        imageline($img, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $warnaGaris);
    }

    // Tulis tiap karakter dengan posisi & warna sedikit acak
    $x = 12;
    for ($i = 0; $i < strlen($kode); $i++) {
        $warnaTeks = imagecolorallocate($img, random_int(30, 70), random_int(30, 70), random_int(30, 70));
        $y = random_int(8, 18);
        imagestring($img, 5, $x, $y, $kode[$i], $warnaTeks);
        $x += 24;
    }

    imagepng($img);
    imagedestroy($img);
} else {
    // ---------- Versi cadangan (SVG, tidak butuh GD) ----------
    header('Content-Type: image/svg+xml');

    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
    echo '<rect width="100%" height="100%" fill="#f5f3f0"/>';

    // Garis acak sebagai gangguan visual
    for ($i = 0; $i < 6; $i++) {
        $x1 = random_int(0, $width);
        $y1 = random_int(0, $height);
        $x2 = random_int(0, $width);
        $y2 = random_int(0, $height);
        echo '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#cfcac2" stroke-width="1"/>';
    }

    // Tulis tiap karakter dengan posisi & rotasi sedikit acak
    $x = 15;
    foreach (str_split($kode) as $huruf) {
        $y   = random_int(30, 38);
        $rot = random_int(-12, 12);
        echo '<text x="' . $x . '" y="' . $y . '" font-family="monospace" font-size="24" font-weight="bold" '
           . 'fill="#333333" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')">' . htmlspecialchars($huruf) . '</text>';
        $x += 24;
    }

    echo '</svg>';
}
