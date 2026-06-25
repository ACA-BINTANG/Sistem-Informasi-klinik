<?php if (isset($_SESSION['is_first_login']) && $_SESSION['is_first_login'] == 1): ?>
<div class="modal fade" id="firstLoginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title fw-bold">
            <i class="bi bi-shield-exclamation me-2"></i>Amankan Akun Anda!
        </h5>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-4">Ini adalah kali pertama Anda login. Demi keamanan rekam medis Anda, wajib mengubah password default menjadi password baru.</p>
        
     <form action="<?php echo BASE_URL; ?>config/update_password_mhs.php" method="POST">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter" style="padding: .5rem .75rem;">
          </div>
          <div class="mb-4">
            <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Ulangi password baru" style="padding: .5rem .75rem;">
          </div>
          <button type="submit" class="btn btn-primary w-100 fw-bold" style="background: linear-gradient(135deg, var(--klinik-primary), var(--klinik-accent)); border:none;">Simpan & Lanjutkan <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>