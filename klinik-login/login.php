<?php include 'config/auth.php'; ?>
<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Sistem Manajemen Klinik</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body-bg">
  <div class="card login-card">
    <div class="form-side">
      <div class="text-center mb-4">
        <div class="text-primary mb-2" style="font-size: 2.5rem;"><i class="bi bi-heart-pulse-fill text-klinik"></i></div>
        <h3 class="fw-bold mb-1">Selamat Datang</h3>
        <p class="text-muted mb-0">Masuk untuk mengakses dashboard klinik</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <div><?php echo htmlspecialchars($error); ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label fw-semibold small text-secondary">Username / NIM</label>
          <div class="input-icon">
            <i class="bi bi-person-fill input-leading"></i>
            <input type="text" name="username" class="form-control" placeholder="Masukkan Username atau NIM" required autofocus>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold small text-secondary">Password</label>
          <div class="input-icon">
            <i class="bi bi-lock-fill input-leading"></i>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-klinik w-100 mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk</button>

        <p class="text-center text-muted small mb-3">
          Akses Tamu / Petugas? <a href="register.php" class="text-klinik fw-semibold text-decoration-none"><i class="bi bi-person-plus-fill"></i> Daftar di sini</a>
        </p>

        <hr class="my-3">
        
        <div class="text-muted bg-light p-3 rounded-3 border border-light-subtle">
          <div class="fw-semibold small mb-2 text-center text-secondary">Panduan Akun Demo</div>
          <table class="demo-table table table-borderless table-sm mb-0 bg-transparent">
            <tr><td><i class="bi bi-shield-lock text-klinik me-1"></i> Admin</td><td class="fw-medium text-dark">admin</td><td class="text-secondary">admin123</td></tr>
            <tr><td><i class="bi bi-clipboard2-pulse text-klinik me-1"></i> Dokter</td><td class="fw-medium text-dark">dokter</td><td class="text-secondary">dokter123</td></tr>
            <tr><td><i class="bi bi-mortarboard-fill text-klinik me-1"></i> Mahasiswa</td><td class="fw-medium text-dark">0920250054</td><td class="text-secondary">0920250054 <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Default</span></td></tr>
          </table>
        </div>
      </form>
    </div>
  </div>
</body>
</html>