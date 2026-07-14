<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$swal = $_SESSION['swal'] ?? null;
$old = $_SESSION['old_register'] ?? [];
$errors = $_SESSION['register_errors'] ?? [];
unset($_SESSION['swal'], $_SESSION['old_register'], $_SESSION['register_errors']);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fieldClass(string $field, array $errors): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrasi Pasien - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --astar-blue: #175cdd;
      --astar-soft-blue: #f4f8ff;
      --astar-danger: #dc3545;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--astar-soft-blue);
      padding: 50px 0;
    }

    .reg-card {
      background: #fff;
      padding: 40px;
      border-radius: 25px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
      max-width: 700px;
      margin: auto;
      border: 1px solid #eef2f7;
    }

    .section-title {
      position: relative;
      padding-left: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      margin: 30px 0 20px;
      color: #112344;
      font-size: 1.1rem;
    }

    .section-title::before {
      content: '';
      position: absolute;
      left: 0;
      width: 5px;
      height: 20px;
      background: var(--astar-blue);
      border-radius: 10px;
    }

    .form-control,
    .form-select {
      border-radius: 12px;
      padding: 12px 15px;
      border: 1px solid #e2e8f0;
      background-color: #f8fafc;
      font-size: 0.95rem;
    }

    .form-control:focus,
    .form-select:focus {
      background-color: #fff;
      border-color: var(--astar-blue);
      box-shadow: 0 0 0 4px rgba(23, 92, 221, 0.1);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
      border-color: var(--astar-danger);
      background-color: #fff8f8;
      box-shadow: none;
    }

    .form-control.is-invalid:focus,
    .form-select.is-invalid:focus {
      border-color: var(--astar-danger);
      box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.12);
    }


    .required-star {
      color: var(--astar-danger);
      margin-left: 2px;
    }

    .input-group-text {
      border-radius: 12px 0 0 12px;
      border: 1px solid #e2e8f0;
      background: #eef2f7;
      font-weight: 600;
    }

    .phone-input {
      border-radius: 0 12px 12px 0 !important;
    }

    .input-group:has(.is-invalid) .input-group-text {
      border-color: var(--astar-danger);
      background: #fff1f1;
      color: var(--astar-danger);
    }

    .register-password-group .form-control {
      border-radius: 12px 0 0 12px;
    }

    .register-password-group .input-group-text {
      border-radius: 0 12px 12px 0;
      cursor: pointer;
      color: #475569;
    }

    .register-password-group .input-group-text:hover {
      color: var(--astar-blue);
    }

    .btn-astar {
      background: var(--astar-blue);
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 15px;
      font-weight: 700;
      transition: 0.3s;
      margin-top: 20px;
    }

    .btn-astar:hover {
      background: #134fb3;
      color: #fff;
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(23, 92, 221, 0.2);
    }

    @media (max-width: 767px) {
      body { padding: 20px 0; }
      .reg-card { padding: 24px 18px; border-radius: 18px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="reg-card">
      <div class="text-center mb-4">
        <img src="assets/img/logoA.png" alt="Logo ASTARhealth" style="max-height: 60px;">
      </div>

      <h4 class="text-center fw-bold mb-1">Pendaftaran Akun Pasien</h4>
      <p class="text-center text-muted small mb-4">Khusus Personel Sigap, Virtus, dan Tamu Umum / Lain-lain</p>

      <form id="registrationForm" action="proses_registrasi.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <div class="section-title">Keamanan Akun</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="username" class="form-label small fw-bold">Username<span class="required-star">*</span></label>
            <input
              type="text"
              id="username"
              name="username"
              class="form-control<?= fieldClass('username', $errors) ?>"
              placeholder="Min. 3 karakter: huruf, angka, titik, _ atau -"
              value="<?= e($old['username'] ?? '') ?>"
              minlength="3"
              maxlength="50"
              autocomplete="username"
              required
            >
          </div>

          <div class="col-md-6">
            <label for="email" class="form-label small fw-bold">Email<span class="required-star">*</span></label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control<?= fieldClass('email', $errors) ?>"
              placeholder="contoh@email.com"
              value="<?= e($old['email'] ?? '') ?>"
              maxlength="100"
              autocomplete="email"
              required
            >
          </div>

          <div class="col-md-12">
            <label for="password" class="form-label small fw-bold">Buat Kata Sandi<span class="required-star">*</span></label>
            <div class="input-group register-password-group">
              <input
                type="password"
                id="password"
                name="password"
                class="form-control<?= fieldClass('password', $errors) ?>"
                placeholder="Min. 8 karakter: huruf besar, kecil, dan angka"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
              >
              <button class="input-group-text" type="button" id="toggleRegisterPassword" aria-label="Tampilkan kata sandi">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="section-title">Identitas Pasien</div>
        <div class="row g-3">
          <div class="col-md-8">
            <label for="nama" class="form-label small fw-bold">Nama Lengkap<span class="required-star">*</span></label>
            <input
              type="text"
              id="nama"
              name="nama"
              class="form-control<?= fieldClass('nama', $errors) ?>"
              placeholder="Min. 3 huruf; contoh: Budi Santoso"
              value="<?= e($old['nama'] ?? '') ?>"
              minlength="3"
              maxlength="100"
              autocomplete="name"
              required
            >
          </div>

          <div class="col-md-4">
            <label for="jk" class="form-label small fw-bold">Jenis Kelamin<span class="required-star">*</span></label>
            <select id="jk" class="form-select<?= fieldClass('jk', $errors) ?>" name="jk" aria-describedby="jkError" required>
              <option value="">Pilih jenis kelamin</option>
              <option value="L" <?= ($old['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="P" <?= ($old['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="kat" class="form-label small fw-bold">Kategori Pasien<span class="required-star">*</span></label>
            <select id="kat" class="form-select<?= fieldClass('kategori', $errors) ?>" name="kategori" aria-describedby="kategoriError" required>
              <option value="">Pilih kategori pasien</option>
              <option value="Sigap" <?= ($old['kategori'] ?? '') === 'Sigap' ? 'selected' : '' ?>>Personel Sigap</option>
              <option value="Virtus" <?= ($old['kategori'] ?? '') === 'Virtus' ? 'selected' : '' ?>>Personel Virtus</option>
              <option value="Tamu" <?= in_array(($old['kategori'] ?? ''), ['Tamu', 'Lainnya'], true) ? 'selected' : '' ?>>Tamu Umum / Lain-lain</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="idInput" class="form-label small fw-bold" id="labelId">Nomor Identitas<span class="required-star">*</span></label>
            <input
              type="text"
              id="idInput"
              name="identitas"
              class="form-control<?= fieldClass('identitas', $errors) ?>"
              placeholder="Hanya angka; pilih kategori untuk ketentuan"
              value="<?= e($old['identitas'] ?? '') ?>"
              maxlength="30"
              inputmode="numeric"
              pattern="[0-9]*"
              autocomplete="off"
              required
            >
          </div>
          <div class="col-md-6">
            <label for="no_hp" class="form-label small fw-bold">Nomor WhatsApp<span class="required-star">*</span></label>
            <div class="input-group">
              <span class="input-group-text">+62</span>
              <input
                type="tel"
                id="no_hp"
                name="no_hp"
                class="form-control phone-input<?= fieldClass('no_hp', $errors) ?>"
                placeholder="Contoh: 812-3456-7890"
                value="<?= e($old['no_hp'] ?? '') ?>"
                inputmode="numeric"
                autocomplete="tel"
                required
              >
            </div>
          </div>

          <div class="col-md-6">
            <label for="alamat" class="form-label small fw-bold">Alamat<span class="required-star">*</span></label>
            <input
              type="text"
              id="alamat"
              name="alamat"
              class="form-control<?= fieldClass('alamat', $errors) ?>"
              placeholder="Min. 5 karakter; contoh: Jl. Melati No. 10"
              value="<?= e($old['alamat'] ?? '') ?>"
              minlength="5"
              maxlength="255"
              autocomplete="street-address"
              required
            >
          </div>
        </div>

        <button type="submit" class="btn btn-astar w-100">Daftar Akun ASTARhealth</button>

        <div class="text-center mt-4 small">
          Sudah punya akun?
          <a href="login.php" class="text-decoration-none fw-bold text-primary">Kembali ke Login</a>
        </div>
      </form>
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

    const form = document.getElementById('registrationForm');
    const kategoriInput = document.getElementById('kat');
    const identityInput = document.getElementById('idInput');
    const phoneInput = document.getElementById('no_hp');
    const passwordInput = document.getElementById('password');
    const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
    const registerDraftKey = 'astarhealth_register_draft_v2';

    const fields = {
      username: document.getElementById('username'),
      email: document.getElementById('email'),
      password: document.getElementById('password'),
      nama: document.getElementById('nama'),
      jk: document.getElementById('jk'),
      kategori: kategoriInput,
      identitas: identityInput,
      no_hp: phoneInput,
      alamat: document.getElementById('alamat')
    };

    const fieldLabels = {
      username: 'Username',
      email: 'Email',
      password: 'Kata Sandi',
      nama: 'Nama Lengkap',
      jk: 'Jenis Kelamin',
      kategori: 'Kategori Pasien',
      identitas: 'Nomor Identitas',
      no_hp: 'Nomor WhatsApp',
      alamat: 'Alamat'
    };

    function readRegisterDraft() {
      try {
        return JSON.parse(sessionStorage.getItem(registerDraftKey) || '{}');
      } catch (error) {
        try {
          sessionStorage.removeItem(registerDraftKey);
        } catch (ignored) {
          // Storage browser tidak tersedia. Form tetap dapat digunakan tanpa draft.
        }
        return {};
      }
    }

    function saveRegisterDraft() {
      const draft = {};
      Object.entries(fields).forEach(([name, input]) => {
        draft[name] = input.value;
      });

      try {
        sessionStorage.setItem(registerDraftKey, JSON.stringify(draft));
      } catch (error) {
        // Storage browser tidak tersedia. Validasi dan registrasi tetap berjalan normal.
      }
    }

    function restoreRegisterDraft() {
      const draft = readRegisterDraft();
      Object.entries(fields).forEach(([name, input]) => {
        if (!input.value && typeof draft[name] === 'string' && draft[name] !== '') {
          input.value = draft[name];
        }
      });

      updateUnitSelection();
      phoneInput.value = formatPhone(phoneInput.value);
    }

    function setFieldError(name, message) {
      const input = fields[name];
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

    function getDigits(value) {
      return String(value || '').replace(/\D/g, '');
    }

    function validateField(name) {
      const input = fields[name];
      if (!input) return '';

      const value = input.value.trim();
      let message = '';

      switch (name) {
        case 'username':
          if (!value) message = 'Username wajib diisi. Isi minimal 3 karakter menggunakan huruf, angka, titik, garis bawah, atau tanda minus.';
          else if (value.length < 3) message = 'Username terlalu pendek. Tambahkan hingga minimal 3 karakter.';
          else if (value.length > 50) message = 'Username terlalu panjang. Kurangi hingga maksimal 50 karakter.';
          else if (!/^[A-Za-z0-9._-]+$/.test(value)) message = 'Username mengandung karakter yang tidak diperbolehkan. Gunakan hanya huruf, angka, titik, garis bawah, atau tanda minus.';
          break;

        case 'email':
          if (!value) message = 'Email wajib diisi. Contoh format yang benar: nama@email.com.';
          else if (value.length > 100) message = 'Email terlalu panjang. Kurangi hingga maksimal 100 karakter.';
          else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) message = 'Format email belum benar. Gunakan format seperti nama@email.com.';
          break;

        case 'password':
          if (!input.value) message = 'Kata sandi wajib diisi. Gunakan minimal 8 karakter yang berisi huruf besar, huruf kecil, dan angka.';
          else if (input.value.length < 8) message = 'Kata sandi terlalu pendek. Tambahkan hingga minimal 8 karakter.';
          else if (input.value.length > 72) message = 'Kata sandi terlalu panjang. Kurangi hingga maksimal 72 karakter.';
          else if (!/[A-Z]/.test(input.value)) message = 'Kata sandi belum memiliki huruf besar. Tambahkan minimal satu huruf kapital, misalnya A.';
          else if (!/[a-z]/.test(input.value)) message = 'Kata sandi belum memiliki huruf kecil. Tambahkan minimal satu huruf kecil, misalnya a.';
          else if (!/\d/.test(input.value)) message = 'Kata sandi belum memiliki angka. Tambahkan minimal satu angka, misalnya 1.';
          break;

        case 'nama':
          if (!value) message = 'Nama lengkap wajib diisi. Masukkan nama sesuai identitas.';
          else if (value.length < 3) message = 'Nama lengkap terlalu pendek. Masukkan minimal 3 huruf.';
          else if (value.length > 100) message = 'Nama lengkap terlalu panjang. Kurangi hingga maksimal 100 karakter.';
          else if (!/^[\p{L} .,'-]+$/u.test(value)) message = 'Nama mengandung angka atau simbol yang tidak diperbolehkan. Gunakan huruf dan tanda baca nama yang wajar.';
          break;

        case 'jk':
          if (!['L', 'P'].includes(value)) message = 'Jenis kelamin belum dipilih. Pilih Laki-laki atau Perempuan.';
          break;

        case 'kategori':
          if (!['Sigap', 'Virtus', 'Tamu'].includes(value)) message = 'Kategori pasien belum dipilih. Pilih salah satu kategori yang tersedia.';
          break;

        case 'identitas':
          if (!value) {
            message = 'Nomor identitas wajib diisi. Masukkan angka sesuai NIP atau NIK.';
          } else if (!/^\d+$/.test(value)) {
            message = 'Nomor identitas hanya boleh berisi angka. Hapus huruf, spasi, titik, garis, atau simbol lainnya.';
          } else if (kategoriInput.value === 'Tamu' && value.length !== 16) {
            message = 'NIK untuk kategori Tamu Umum / Lain-lain harus tepat 16 angka.';
          } else if (kategoriInput.value !== 'Tamu' && (value.length < 3 || value.length > 30)) {
            message = 'NIP harus berisi minimal 3 dan maksimal 30 angka.';
          }
          break;
        case 'no_hp': {
          const digits = getDigits(value).replace(/^0+/, '');
          if (!digits) message = 'Nomor WhatsApp wajib diisi. Ketik nomor setelah +62, dimulai angka 8.';
          else if (!/^8\d{8,12}$/.test(digits)) message = 'Nomor WhatsApp belum benar. Setelah +62, nomor harus dimulai angka 8 dan berisi 9–13 angka.';
          break;
        }

        case 'alamat':
          if (!value) message = 'Alamat wajib diisi. Masukkan alamat tinggal saat ini.';
          else if (value.length < 5) message = 'Alamat terlalu pendek. Tambahkan hingga minimal 5 karakter.';
          else if (value.length > 255) message = 'Alamat terlalu panjang. Kurangi hingga maksimal 255 karakter.';
          break;
      }

      setFieldError(name, message);
      return message;
    }

    function updateUnitSelection() {
      const kategori = kategoriInput.value;
      const labelId = document.getElementById('labelId');
      const requiredStar = '<span class="required-star">*</span>';

      if (kategori === 'Sigap') {
        labelId.innerHTML = `NIP Sigap${requiredStar}`;
        identityInput.placeholder = 'Hanya angka, minimal 3 digit';
        identityInput.maxLength = 30;
      } else if (kategori === 'Virtus') {
        labelId.innerHTML = `NIP Virtus${requiredStar}`;
        identityInput.placeholder = 'Hanya angka, minimal 3 digit';
        identityInput.maxLength = 30;
      } else if (kategori === 'Tamu') {
        labelId.innerHTML = `NIK (KTP)${requiredStar}`;
        identityInput.placeholder = 'Wajib tepat 16 angka tanpa spasi';
        identityInput.maxLength = 16;
      } else {
        labelId.innerHTML = `Nomor Identitas${requiredStar}`;
        identityInput.placeholder = 'Pilih kategori terlebih dahulu';
        identityInput.maxLength = 30;
      }

      identityInput.inputMode = 'numeric';
      identityInput.pattern = '[0-9]*';
      identityInput.value = getDigits(identityInput.value).slice(0, identityInput.maxLength);
    }

    function formatPhone(value) {
      let digits = getDigits(value).replace(/^62/, '').replace(/^0+/, '').slice(0, 13);
      const parts = [];

      if (digits.length > 0) parts.push(digits.slice(0, 3));
      if (digits.length > 3) parts.push(digits.slice(3, 7));
      if (digits.length > 7) parts.push(digits.slice(7, 13));

      return parts.join('-');
    }

    Object.entries(fields).forEach(([name, input]) => {
      const eventName = input.tagName === 'SELECT' ? 'change' : 'input';

      input.addEventListener(eventName, () => {
        if (name === 'no_hp') {
          input.value = formatPhone(input.value);
        }

        if (name === 'identitas') {
          const maxDigits = kategoriInput.value === 'Tamu' ? 16 : 30;
          input.value = getDigits(input.value).slice(0, maxDigits);
        }

        if (name === 'kategori') {
          updateUnitSelection();
          validateField('identitas');
        }

        saveRegisterDraft();
        validateField(name);
      });

      input.addEventListener('blur', () => validateField(name));
    });

    form.addEventListener('submit', function (event) {
      saveRegisterDraft();
      const currentErrors = {};

      Object.keys(fields).forEach((name) => {
        const message = validateField(name);
        if (message) currentErrors[name] = message;
      });

      const firstInvalidName = Object.keys(currentErrors)[0];
      if (firstInvalidName) {
        event.preventDefault();
        fields[firstInvalidName].focus();
        fields[firstInvalidName].scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (window.Swal) {
          const errorNames = Object.keys(currentErrors);
          const onlyOneError = errorNames.length === 1;
          const errorField = errorNames[0];

          Swal.fire({
            icon: 'warning',
            title: onlyOneError
              ? `Periksa ${fieldLabels[errorField] || 'inputan'}`
              : 'Ada beberapa input yang salah',
            text: onlyOneError
              ? currentErrors[errorField]
              : 'Kolom yang bermasalah sudah diberi border merah. Periksa petunjuk pada placeholder setiap kolom, lalu kirim kembali formulir.',
            confirmButtonText: 'Perbaiki Data',
            confirmButtonColor: '#175cdd'
          });
        }
        return;
      }

      if (window.Swal) {
        Swal.fire({
          title: 'Memproses registrasi',
          text: 'Data sedang diperiksa dan disimpan...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
      }
    });

    toggleRegisterPassword.addEventListener('click', () => {
      const showPassword = passwordInput.type === 'password';
      passwordInput.type = showPassword ? 'text' : 'password';
      toggleRegisterPassword.innerHTML = `<i class="bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
      toggleRegisterPassword.setAttribute(
        'aria-label',
        showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
      );
      passwordInput.focus({ preventScroll: true });
    });

    document.addEventListener('DOMContentLoaded', () => {
      restoreRegisterDraft();

      Object.entries(serverErrors || {}).forEach(([name, message]) => {
        setFieldError(name, message);
      });

      const firstServerError = Object.keys(serverErrors || {})[0];
      if (firstServerError && fields[firstServerError]) {
        fields[firstServerError].scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      if (serverAlert) {
        if (window.Swal) {
          Swal.fire({
            icon: serverAlert.icon || 'info',
            title: serverAlert.title || 'Informasi',
            text: serverAlert.text || '',
            confirmButtonText: 'Oke',
            confirmButtonColor: '#175cdd'
          });
        }
      }

      // Chrome/Edge terkadang mengisi password beberapa saat setelah halaman selesai dimuat.
      // Pemulihan ulang hanya mengisi kolom yang masih kosong, jadi tidak menimpa autofill browser.
      window.setTimeout(restoreRegisterDraft, 150);
      window.setTimeout(restoreRegisterDraft, 500);
    });
  </script>
</body>
</html>
