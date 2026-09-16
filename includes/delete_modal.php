<!-- Modal konfirmasi hapus (dipakai di beberapa halaman) -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <h3>Hapus Data?</h3>
        <p>Apakah Anda yakin ingin menghapus <b id="deleteNamaTarget"></b>?</p>
        <div class="btn-row" style="justify-content:flex-end;">
            <button class="btn btn-outline" style="flex:none;padding:10px 18px;" onclick="closeDeleteModal()">BATAL</button>
            <button class="btn" style="flex:none;padding:10px 18px;" onclick="confirmDelete()">HAPUS</button>
        </div>
    </div>
</div>
