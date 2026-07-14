<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$swal = $_SESSION['swal'] ?? null;
$errors = $_SESSION['login_errors'] ?? [];
$oldUsername = (string) ($_SESSION['old_login']['username'] ?? '');
$oldPassword = (string) ($_SESSION['old_login']['password'] ?? '');
$clearRegisterDraft = !empty($_SESSION['clear_register_draft']);
unset(
    $_SESSION['swal'],
    $_SESSION['login_errors'],
    $_SESSION['old_login'],
    $_SESSION['clear_register_draft']
);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function inputClass(string $field, array $errors): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login SSO - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --astar-blue: #175cdd;
      --astar-danger: #dc3545;
    }

    body {
      background-color: #f4f8ff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .login-container {
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 420px;
    }

    .login-logo {
      display: block;
      margin: 0 auto 20px;
      max-height: 80px;
    }

    .form-control,
    .input-group-text {
      border-color: #e2e8f0;
    }

    .form-control {
      border-radius: 10px;
      padding: 12px 14px;
      background: #f8fafc;
    }

    .form-control:focus {
      background: #fff;
      border-color: var(--astar-blue);
      box-shadow: 0 0 0 4px rgba(23, 92, 221, 0.1);
    }

    .form-control.is-invalid {
      border-color: var(--astar-danger);
      background-image: none;
      background-color: #fff8f8;
    }

    .form-control.is-invalid:focus {
      border-color: var(--astar-danger);
      box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.12);
    }

    .password-group .form-control {
      border-radius: 10px 0 0 10px;
    }

    .password-group .input-group-text {
      border-radius: 0 10px 10px 0;
      background: #f8fafc;
      cursor: pointer;
    }

    .password-group:has(.is-invalid) .input-group-text {
      border-color: var(--astar-danger);
      background: #fff8f8;
      color: var(--astar-danger);
    }

    .btn-login {
      background-color: var(--astar-blue);
      color: #fff;
      width: 100%;
      border-radius: 10px;
      padding: 12px;
      border: none;
      font-weight: 600;
    }

    .btn-login:hover {
      background: #134fb3;
      color: #fff;
    }

    @media (max-width: 480px) {
      .login-container { padding: 28px 20px; }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="assets/img/logoA.png" class="login-logo" alt="Logo ASTARhealth">
    <h4 class="text-center fw-bold mb-2">Login SSO</h4>
    <p class="text-center text-muted small mb-4">Gunakan akun ASTARhealth Anda</p>

    <form id="loginForm" action="proses_login.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

      <div class="mb-3">
        <label for="username" class="form-label small fw-bold">NIM / NIP / Username</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control<?= inputClass('username', $errors) ?>"
          placeholder="Masukkan NIM, NIP, email, atau username"
          value="<?= e($oldUsername) ?>"
          maxlength="100"
          autocomplete="username"
          required
        >
      </div>

      <div class="mb-4">
        <label for="password" class="form-label small fw-bold">Kata Sandi</label>
        <div class="input-group password-group">
          <input
            type="password"
            id="password"
            name="password"
            class="form-control<?= inputClass('password', $errors) ?>"
            placeholder="Masukkan kata sandi akun"
            value="<?= e($oldPassword) ?>"
            maxlength="72"
            autocomplete="current-password"
            required
          >
          <button class="input-group-text" type="button" id="togglePassword" aria-label="Tampilkan kata sandi">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-login">Masuk ke Sistem</button>
    </form>
    <div class="text-center mt-2 small">
      Belum punya akun?
      <a href="registrasi.php" class="text-decoration-none fw-bold">Daftar Pasien Baru</a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>
  <script src="assets/js/sweetalert-fallback.js"></script>
  <script>
    const serverAlert = <?= json_encode(
        $swal,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;

    const serverErrors = <?= json_encode(
        $errors,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;

    const clearRegisterDraft = <?= $clearRegisterDraft ? 'true' : 'false' ?>;
    if (clearRegisterDraft) {
      try {
        sessionStorage.removeItem('astarhealth_register_draft_v2');
      } catch (error) {
        // Browser tidak menyediakan sessionStorage; tidak memengaruhi proses login.
      }
    }

    const form = document.getElementById('loginForm');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    const fields = { username, password };
    const labels = { username: 'Username', password: 'Kata Sandi' };

    function setError(field, message) {
      const input = fields[field];
      if (!input) return;

      if (message) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('title', message);
      } else {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
        input.removeAttribute('title');
      }
    }

    function validateField(field) {
      let message = '';

      if (field === 'username') {
        const value = username.value.trim();
        if (!value) {
          message = 'Username wajib diisi. Masukkan NIM, NIP, email, atau username akun Anda.';
        } else if (value.length > 100) {
          message = 'Username terlalu panjang. Kurangi hingga maksimal 100 karakter.';
        }
      }

      if (field === 'password') {
        if (!password.value) {
          message = 'Kata sandi wajib diisi. Masukkan kata sandi akun Anda.';
        } else if (password.value.length > 72) {
          message = 'Kata sandi terlalu panjang. Kurangi hingga maksimal 72 karakter.';
        }
      }

      setError(field, message);
      return message;
    }

    Object.entries(fields).forEach(([field, input]) => {
      input.addEventListener('input', () => validateField(field));
      input.addEventListener('blur', () => validateField(field));
    });

    form.addEventListener('submit', (event) => {
      const currentErrors = {};

      Object.keys(fields).forEach((field) => {
        const message = validateField(field);
        if (message) currentErrors[field] = message;
      });

      const invalidFields = Object.keys(currentErrors);
      if (invalidFields.length === 0) return;

      event.preventDefault();
      const firstField = invalidFields[0];
      fields[firstField].focus();

      Swal.fire({
        icon: 'warning',
        title: invalidFields.length === 1 ? `Periksa ${labels[firstField]}` : 'Ada beberapa input yang salah',
        text: invalidFields.length === 1
          ? currentErrors[firstField]
          : 'Kolom yang bermasalah sudah diberi border merah. Periksa kembali username dan kata sandi Anda.',
        confirmButtonText: 'Perbaiki',
        confirmButtonColor: '#175cdd'
      });
    });

    togglePassword.addEventListener('click', () => {
      const isPassword = password.type === 'password';
      password.type = isPassword ? 'text' : 'password';
      togglePassword.innerHTML = `<i class="bi ${isPassword ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
      togglePassword.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });

    Object.keys(serverErrors || {}).forEach((field) => setError(field, serverErrors[field]));

    if (serverAlert) {
      Swal.fire({
        icon: serverAlert.icon || 'info',
        title: serverAlert.title || 'Informasi',
        text: serverAlert.text || '',
        confirmButtonText: 'OK',
        confirmButtonColor: '#175cdd'
      }).then(() => {
        const firstInvalid = Object.keys(serverErrors || {})[0];
        if (firstInvalid && fields[firstInvalid]) fields[firstInvalid].focus();
      });
    }
  </script>
</body>
</html>
