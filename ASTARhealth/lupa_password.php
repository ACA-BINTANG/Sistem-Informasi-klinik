<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$swal = $_SESSION['swal'] ?? null;
$errors = $_SESSION['forgot_errors'] ?? [];
$old = $_SESSION['forgot_old'] ?? [];
unset($_SESSION['swal'], $_SESSION['forgot_errors'], $_SESSION['forgot_old']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function invalidClass(string $field, array $errors): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Kata Sandi - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f4f8ff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card-reset{width:100%;max-width:500px;border:0;border-radius:20px;box-shadow:0 15px 35px rgba(0,0,0,.1)}
    .form-control{padding:12px 14px;border-radius:10px;background:#f8fafc}
    .form-control.is-invalid{border-color:#dc3545;background-image:none;background-color:#fff8f8}
    .btn-primary{background:#175cdd;border-color:#175cdd;border-radius:10px;padding:12px;font-weight:600}
  </style>
</head>
<body>
  <div class="card card-reset">
    <div class="card-body p-4 p-md-5">
      <img src="assets/img/logoA.png" alt="ASTARhealth" class="d-block mx-auto mb-3" style="max-height:75px">
      <h4 class="text-center fw-bold">Reset Kata Sandi</h4>
      <p class="text-muted text-center small mb-4">Masukkan username dan email yang terdaftar, lalu buat kata sandi baru.</p>

      <form id="forgotForm" action="proses_lupa_password.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
          <label for="username" class="form-label fw-bold small">Username</label>
          <input type="text" id="username" name="username" class="form-control<?= invalidClass('username', $errors) ?>" value="<?= e((string)($old['username'] ?? '')) ?>" placeholder="Masukkan username akun" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label fw-bold small">Email Terdaftar</label>
          <input type="email" id="email" name="email" class="form-control<?= invalidClass('email', $errors) ?>" value="<?= e((string)($old['email'] ?? '')) ?>" placeholder="Contoh: nama@email.com" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label fw-bold small">Kata Sandi Baru</label>
          <input type="text" id="password" name="password" class="form-control<?= invalidClass('password', $errors) ?>" placeholder="Min. 8 karakter: huruf besar, kecil, dan angka" minlength="8" maxlength="72" autocomplete="off" required>
        </div>
        <div class="mb-4">
          <label for="confirm_password" class="form-label fw-bold small">Ulangi Kata Sandi Baru</label>
          <input type="text" id="confirm_password" name="confirm_password" class="form-control<?= invalidClass('confirm_password', $errors) ?>" placeholder="Ketik ulang kata sandi baru" minlength="8" maxlength="72" autocomplete="off" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Simpan Kata Sandi Baru</button>
      </form>
      <div class="text-center mt-3"><a href="login.php" class="text-decoration-none fw-bold">Kembali ke Login</a></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>
  <script src="assets/js/sweetalert-fallback.js"></script>
  <script>
    const serverAlert = <?= json_encode($swal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    const form = document.getElementById('forgotForm');
    const fields = {
      username: document.getElementById('username'),
      email: document.getElementById('email'),
      password: document.getElementById('password'),
      confirm_password: document.getElementById('confirm_password')
    };

    function setInvalid(input, invalid) {
      input.classList.toggle('is-invalid', invalid);
    }

    function validate() {
      const errors = {};
      if (!fields.username.value.trim()) errors.username = 'Username wajib diisi.';
      if (!fields.email.value.trim() || !fields.email.checkValidity()) errors.email = 'Masukkan email terdaftar dengan format yang benar.';
      const password = fields.password.value;
      if (!password) errors.password = 'Kata sandi baru wajib diisi.';
      else if (password.length < 8) errors.password = 'Kata sandi baru minimal 8 karakter.';
      else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/\d/.test(password)) errors.password = 'Kata sandi harus memiliki huruf besar, huruf kecil, dan angka.';
      if (fields.confirm_password.value !== password) errors.confirm_password = 'Ulangi kata sandi harus sama.';
      Object.entries(fields).forEach(([key,input]) => setInvalid(input, Boolean(errors[key])));
      return errors;
    }

    form.addEventListener('submit', (event) => {
      const errors = validate();
      const keys = Object.keys(errors);
      if (!keys.length) return;
      event.preventDefault();
      fields[keys[0]].focus();
      Swal.fire({
        icon:'warning',
        title: keys.length === 1 ? 'Periksa Input' : 'Ada beberapa input yang salah',
        text: keys.length === 1 ? errors[keys[0]] : 'Kolom yang bermasalah sudah diberi border merah.',
        confirmButtonColor:'#175cdd'
      });
    });

    Object.values(fields).forEach(input => input.addEventListener('input', validate));
    if (serverAlert) Swal.fire({...serverAlert, confirmButtonColor:'#175cdd'});
  </script>
</body>
</html>
