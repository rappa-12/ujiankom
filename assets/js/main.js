//  1. Hitung Gaji: preview otomatis 
function hitungPreview() {
    const gajiPokok = parseInt(document.getElementById('gaji_pokok')?.value) || 0;
    const lembur = parseInt(document.getElementById('lembur')?.value) || 0;
    const pinjaman = parseInt(document.getElementById('pinjaman')?.value) || 0;

    const totalPenghasilan = gajiPokok + lembur;
    const gajiBersih = totalPenghasilan - pinjaman;

    const elPenghasilan = document.getElementById('preview_penghasilan');
    const elPotongan = document.getElementById('preview_potongan');
    const elBersih = document.getElementById('preview_bersih');

    if (elPenghasilan) elPenghasilan.textContent = formatRupiah(totalPenghasilan);
    if (elPotongan) elPotongan.textContent = formatRupiah(pinjaman);
    if (elBersih) elBersih.textContent = formatRupiah(gajiBersih);
}

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

// 2. Filter pencarian tabel (dipakai di Riwayat & Karyawan) 
function filterTable(tableId) {
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');

    rows.forEach(row => {
        const nama = (row.dataset.nama || '').toLowerCase();
        row.style.display = nama.includes(keyword) ? '' : 'none';
    });
}

//  3. Modal konfirmasi hapus (generik untuk Gaji/Karyawan) 
let deleteUrlTarget = '';

function openDeleteModal(url, nama) {
    deleteUrlTarget = url;
    document.getElementById('deleteNamaTarget').textContent = nama;
    document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}

function confirmDelete() {
    if (deleteUrlTarget) {
        window.location.href = deleteUrlTarget;
    }
}

//  4. Captcha: refresh gambar tanpa reload halaman 
function refreshCaptcha() {
    const img = document.getElementById('captchaImg');
    if (img) {
        img.src = 'captcha.php?' + new Date().getTime(); // timestamp biar tidak cache
    }
}

//  5. Modal generik (dipakai popup Kirim Email, Kirim WhatsApp, Tambah/Edit) 
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function cetakPDF() {
    window.print();
}

//  6. Kirim via WhatsApp Web (buka WA Web langsung, data sudah terisi) 
function kirimWhatsAppWeb(event) {
    event.preventDefault();

    const form = event.target;
    const nomorInput = form.querySelector('[name="nomor_wa"]');
    let nomor = (nomorInput.value || '').replace(/[^0-9]/g, '');
    if (nomor.startsWith('0')) {
        nomor = '62' + nomor.slice(1);
    }

    const pesan = form.dataset.pesan || '';
    const url = 'https://web.whatsapp.com/send?phone=' + nomor + '&text=' + encodeURIComponent(pesan);

    window.open(url, '_blank');
    closeModal('waModal');
    return false;
}

//  7. Modal Tambah/Edit Karyawan (karyawan.php) 
function bukaTambahKaryawan() {
    document.getElementById('karyawanModalTitle').textContent = 'Tambah Karyawan';
    document.getElementById('karyawan_id').value = '';
    document.getElementById('nik').value = '';
    document.getElementById('nama').value = '';
    document.getElementById('jabatan').value = '';
    openModal('karyawanModal');
}

function bukaEditKaryawan(id, nik, nama, jabatan) {
    document.getElementById('karyawanModalTitle').textContent = 'Edit Karyawan';
    document.getElementById('karyawan_id').value = id;
    document.getElementById('nik').value = nik;
    document.getElementById('nama').value = nama;
    document.getElementById('jabatan').value = jabatan;
    openModal('karyawanModal');
}

//  8. Modal Edit Gaji (riwayat.php) 
function bukaEditGaji(id, karyawanId, periodeId, gajiPokok, lembur, pinjaman) {
    document.getElementById('gaji_id').value = id;
    document.getElementById('edit_karyawan_id').value = karyawanId;
    document.getElementById('edit_periode_id').value = periodeId;
    document.getElementById('edit_gaji_pokok').value = gajiPokok;
    document.getElementById('edit_lembur').value = lembur;
    document.getElementById('edit_pinjaman').value = pinjaman;
    openModal('gajiModal');
}
