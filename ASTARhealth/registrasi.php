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
    }
    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: var(--astar-soft-blue); 
        padding: 50px 0; 
    }
    .reg-card { 
        background: white; 
        padding: 40px; 
        border-radius: 25px; 
        box-shadow: 0 20px 50px rgba(0,0,0,0.05); 
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
        margin-bottom: 20px; 
        margin-top: 30px; 
        color: #112344;
        font-size: 1.1rem;
    }
    .section-title::before { 
        content: ""; 
        position: absolute; 
        left: 0; 
        width: 5px; 
        height: 20px; 
        background: var(--astar-blue); 
        border-radius: 10px; 
    }
    .form-control, .form-select {
        border-radius: 12px;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: var(--astar-blue);
        box-shadow: 0 0 0 4px rgba(23, 92, 221, 0.1);
    }
    .btn-astar {
        background: var(--astar-blue);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 15px;
        font-weight: 700;
        transition: 0.3s;
        margin-top: 20px;
    }
    .btn-astar:hover { 
        background: #134fb3; 
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(23, 92, 221, 0.2);
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
  </style>
</head>
<body>

  <div class="container">
    <div class="reg-card animate__animated animate__fadeIn">
      <div class="text-center mb-4">
          <img src="assets/img/logoA.png" style="max-height: 60px;">
      </div>
      <h4 class="text-center fw-bold mb-1">Pendaftaran Akun Pasien</h4>
      <p class="text-center text-muted small mb-4">Khusus Personel Sigap, Virtus, Tamu & Lainnya</p>
      
      <form action="proses_registrasi.php" method="POST">
        
        <!-- BAGIAN 1: AKUN -->
        <div class="section-title">Keamanan Akun</div>
        <div class="row g-3">
          <div class="col-md-6">
              <label class="form-label small fw-bold">Username</label>
              <input type="text" name="username" class="form-control" placeholder="NIM / NIP / NIK" required>
          </div>
          <div class="col-md-6">
              <label class="form-label small fw-bold">Email</label>
              <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required>
          </div>
          <div class="col-md-12">
              <label class="form-label small fw-bold">Buat Kata Sandi</label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <!-- BAGIAN 2: DATA DIRI -->
        <div class="section-title">Identitas Pasien</div>
        <div class="row g-3">
          <div class="col-md-8">
              <label class="form-label small fw-bold">Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" placeholder="Nama lengkap sesuai identitas" required>
          </div>
          <div class="col-md-4">
              <label class="form-label small fw-bold">Jenis Kelamin</label>
              <select class="form-select" name="jk" required>
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
              </select>
          </div>
          
          <div class="col-md-6">
              <label class="form-label small fw-bold">Kategori Pasien</label>
              <select class="form-select" id="kat" name="kategori" onchange="updateUnitSelection()" required>
                  <option value="Sigap">Personel Sigap</option>
                  <option value="Virtus">Personel Virtus</option>
                  <option value="Tamu">Tamu Umum</option>
                  <option value="Lainnya">Lain-lain</option>
              </select>
          </div>

          <div class="col-md-6">
              <label class="form-label small fw-bold" id="labelId">NIP Sigap</label>
              <input type="text" name="identitas" class="form-control" id="idInput" placeholder="Masukkan Nomor Identitas" required>
          </div>

          <!-- Input Keterangan / Unit -->
          <div class="col-md-12">
              <label class="form-label small fw-bold" id="labelUnit">Jabatan / Bagian</label>
              <input type="text" class="form-control" id="inputUnit" name="unit_kerja" placeholder="Contoh: Security / Cleaning Service / Instansi Asal" required>
          </div>

          <div class="col-md-6">
              <label class="form-label small fw-bold">Nomor WhatsApp</label>
              <div class="input-group">
                  <span class="input-group-text">+62</span>
                  <input type="tel" name="no_hp" id="no_hp" class="form-control phone-input" placeholder="8xx-xxxx-xxxx" required>
              </div>
          </div>
          <div class="col-md-6">
              <label class="form-label small fw-bold">Alamat</label>
              <input type="text" name="alamat" class="form-control" placeholder="Alamat tinggal saat ini">
          </div>
        </div>

        <button type="submit" class="btn btn-astar w-100">Daftar Akun ASTARhealth</button>
        
        <div class="text-center mt-4 small">
          Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold text-primary">Kembali ke Login</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Fungsi Update Label Dinamis
    function updateUnitSelection() {
      const kategori = document.getElementById('kat').value;
      const labelId = document.getElementById('labelId');
      const idInput = document.getElementById('idInput');
      const labelUnit = document.getElementById('labelUnit');
      const inputUnit = document.getElementById('inputUnit');

      if (kategori === 'Sigap') {
        labelId.innerText = "NIP Sigap";
        idInput.placeholder = "Masukkan NIP Sigap";
        labelUnit.innerText = "Jabatan / Tugas";
        inputUnit.placeholder = "Contoh: Security / Danru";
      } 
      else if (kategori === 'Virtus') {
        labelId.innerText = "NIP Virtus";
        idInput.placeholder = "Masukkan NIP Virtus";
        labelUnit.innerText = "Jabatan / Tugas";
        inputUnit.placeholder = "Contoh: Cleaning Service / Gardener";
      } 
      else if (kategori === 'Tamu') {
        labelId.innerText = "NIK (KTP)";
        idInput.placeholder = "Masukkan No. KTP";
        labelUnit.innerText = "Asal Instansi";
        inputUnit.placeholder = "Contoh: PT. Astra International / Tamu Umum";
      }
      else {
        labelId.innerText = "Nomor Identitas";
        idInput.placeholder = "Masukkan No. Identitas";
        labelUnit.innerText = "Keterangan";
        inputUnit.placeholder = "Sebutkan keperluan/status";
      }
    }

    // Auto Format Nomor HP (000-0000-0000)
    document.getElementById('no_hp').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Ambil angka saja
        value = value.substring(0, 13); // Batasi 13 digit
        
        let formatted = '';
        if (value.length > 0) {
            formatted = value.substring(0, 3);
            if (value.length > 3) {
                formatted += '-' + value.substring(3, 7);
            }
            if (value.length > 7) {
                formatted += '-' + value.substring(7, 12);
            }
        }
        e.target.value = formatted;
    });

    // Jalankan sekali saat load pertama
    window.onload = updateUnitSelection;
  </script>
</body>
</html>