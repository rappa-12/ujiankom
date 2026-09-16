<?php
$active_page = $active_page ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-logo">PAYROLL</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?= $active_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="karyawan.php" class="<?= $active_page === 'karyawan' ? 'active' : '' ?>">Karyawan</a>
        <a href="hitung_gaji.php" class="<?= $active_page === 'hitung_gaji' ? 'active' : '' ?>">Hitung Gaji</a>
        <a href="riwayat.php" class="<?= $active_page === 'riwayat' ? 'active' : '' ?>">Riwayat</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>
