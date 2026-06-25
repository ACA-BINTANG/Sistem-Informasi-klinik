
<?php include 'config/register_process.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pendaftaran Pasien - Klinik Sehat Sentosa</title>
<?php include 'includes/header.php'; ?>
</head>
<body class="login-body-bg">
  <div class="card register-card">
    <div class="row g-0">
      <div class="col-md-4 register-side d-none d-md-flex flex-column justify-content-between">
        <div>
          <div class="logo-circle mb-4"><i class="bi bi-heart-pulse-fill"></i></div>
          <h2 class="fw-bold mb-2">Pendaftaran Klinik</h2>
          <p class="opacity-75 mb-4">Layanan kesehatan terpadu civitas akademika dan umum.</p>
        </div>
        <div>
          <div class="feature-item"><i class="bi bi-shield-check"></i><span>Layanan Terpadu</span></div>
          <div class="feature-item"><i class="bi bi-person-lines-fill"></i><span>Rekam Medis Digital</span></div>
          <div class="feature-item"><i class="bi bi-clock-history"></i><span>Cepat & Tepat</span></div>
        </div>
        <p class="small opacity-50 mb-0">&copy; <?php echo date('Y'); ?> Klinik Sehat Sentosa</p>
      </div>

      <div class="col-md-8 form-side">
        <div class="mb-3">
          <h3 class="fw-bold mb-1">Pendaftaran Pengguna Baru</h3>
          <p class="text-muted mb-0 small">Lengkapi data diri Anda di bawah ini</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success d-flex align-items-center py-2" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div><?php echo htmlspecialchars($success); ?> <a href="login.php" class="alert-link text-decoration-underline">Login sekarang</a></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="row g-2">
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">Kategori</label>
              <div class="input-icon">
                <i class="bi bi-tags-fill input-leading"></i>
                <select name="kategori" id="kategori" class="form-select input-icon-control" required>
                  <option value="Tamu">Tamu Umum</option>
                  <option value="Petugas">Petugas (Sigap / Virtus)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary" id="label-identitas">NIK / No. KTP</label>
              <div class="input-icon">
                <i class="bi bi-person-badge-fill input-leading"></i>
                <input type="text" name="identitas" id="input-identitas" class="form-control input-icon-control" placeholder="Masukkan NIK" required>
              </div>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">Nama Lengkap</label>
              <div class="input-icon">
                <i class="bi bi-person-vcard-fill input-leading"></i>
                <input type="text" name="name" class="form-control input-icon-control" placeholder="Nama lengkap" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">Email</label>
              <div class="input-icon">
                <i class="bi bi-envelope-fill input-leading"></i>
                <input type="email" name="email" class="form-control input-icon-control" placeholder="Alamat email">
              </div>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary" id="label-prodi">Instansi / Tujuan</label>
              <div class="input-icon">
                <i class="bi bi-building-fill input-leading" id="icon-prodi"></i>
                <input type="text" name="prodi" id="input-prodi-tamu" class="form-control input-icon-control dynamic-prodi" placeholder="Contoh: Kunjungan / Ekspedisi">
                <select name="prodi" id="input-prodi-petugas" class="form-select input-icon-control dynamic-prodi d-none" disabled>
                  <option value="" disabled selected>Pilih Unit Tugas...</option>
                  <option value="Sigap">Sigap</option>
                  <option value="Virtus">Virtus</option>
                </select>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">No. Telepon / WhatsApp</label>
              <div class="input-icon">
                <i class="bi bi-telephone-fill input-leading"></i>
                <input type="text" name="no_tlp" class="form-control input-icon-control" placeholder="08123xxx">
              </div>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold small text-secondary">Alamat Lengkap</label>
            <div class="input-icon">
              <i class="bi bi-geo-alt-fill input-leading"></i>
              <textarea name="alamat" class="form-control input-icon-control" rows="2" placeholder="Masukkan alamat lengkap"></textarea>
            </div>
          </div>

          <hr class="my-3 text-muted">

          <div class="mb-2">
            <label class="form-label fw-semibold small text-secondary">Username</label>
            <div class="input-icon">
              <i class="bi bi-person-fill input-leading"></i>
              <input type="text" name="username" class="form-control input-icon-control" placeholder="Min. 4 karakter" required>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">Password</label>
              <div class="input-icon">
                <i class="bi bi-lock-fill input-leading"></i>
                <input type="password" name="password" class="form-control input-icon-control" placeholder="Min. 6 karakter" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold small text-secondary">Konfirmasi Password</label>
              <div class="input-icon">
                <i class="bi bi-shield-lock-fill input-leading"></i>
                <input type="password" name="confirm" class="form-control input-icon-control" placeholder="Ulangi password" required>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-klinik w-100 mt-3 mb-3"><i class="bi bi-person-plus-fill me-2"></i>Daftar Sekarang</button>

          <p class="text-center text-muted small mb-0">Sudah punya akun? <a href="login.php" class="text-klinik fw-semibold text-decoration-none">Login di sini</a></p>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const kategoriSelect = document.getElementById('kategori');
      const labelIdentitas = document.getElementById('label-identitas');
      const inputIdentitas = document.getElementById('input-identitas');
      const labelProdi = document.getElementById('label-prodi');
      const iconProdi = document.getElementById('icon-prodi');
      const inputProdiTamu = document.getElementById('input-prodi-tamu');
      const inputProdiPetugas = document.getElementById('input-prodi-petugas');
      
      function updateForm() {
        const value = kategoriSelect.value;
        inputProdiTamu.classList.add('d-none'); inputProdiTamu.disabled = true;
        inputProdiPetugas.classList.add('d-none'); inputProdiPetugas.disabled = true;

        if (value === 'Tamu') {
          labelIdentitas.textContent = 'NIK'; inputIdentitas.placeholder = 'Masukkan NIK';
          labelProdi.textContent = 'Instansi / Tujuan'; iconProdi.className = 'bi bi-building-fill input-leading';
          inputProdiTamu.classList.remove('d-none'); inputProdiTamu.disabled = false;
        } else if (value === 'Petugas') {
          labelIdentitas.textContent = 'ID Petugas'; inputIdentitas.placeholder = 'Masukkan ID';
          labelProdi.textContent = 'Pilih Unit'; iconProdi.className = 'bi bi-shield-check input-leading';
          inputProdiPetugas.classList.remove('d-none'); inputProdiPetugas.disabled = false;
        }
      }
      kategoriSelect.addEventListener('change', updateForm);
      updateForm();
    });
  </script>
</body>
</html>