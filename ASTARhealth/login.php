<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login SSO - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f8ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-container { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    .login-logo { display: block; margin: 0 auto 20px; max-height: 80px; }
    .btn-login { background-color: #175cdd; color: white; width: 100%; border-radius: 10px; padding: 12px; border: none; font-weight: 600; }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="assets/img/logoA.png" class="login-logo">
    <h4 class="text-center fw-bold mb-2">Login SSO</h4>
    <p class="text-center text-muted small mb-4">Gunakan akun Politeknik Astra Anda</p>
    
    <?php if(isset($_GET['pesan'])) echo "<div class='alert alert-danger small'>".$_GET['pesan']."</div>"; ?>

    <form action="proses_login.php" method="POST">
      <div class="mb-3">
        <label class="form-label small fw-bold">NIM / NIP / Username</label>
        <input type="text" name="username" class="form-control" placeholder="Masukkan ID anda" required>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-bold">Kata Sandi</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Masuk ke Sistem</button>
    </form>
    
    <div class="text-center mt-4 small">
      Belum punya akun? <a href="registrasi.php" class="text-decoration-none">Daftar Pasien Baru</a>
    </div>
  </div>
</body>
</html>