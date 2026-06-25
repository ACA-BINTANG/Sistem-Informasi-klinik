<?php
$role_required = 'dokter';
$page_title = 'Dashboard Dokter';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);
?>
<div class="row g-3 mb-3">
  <?php
  $stats = [
    ['Pasien Hari Ini', '18', 'bi-people-fill', '#0891b2', '#cffafe'],
    ['Antrian Sekarang', '5', 'bi-hourglass-split', '#f59e0b', '#fef3c7'],
    ['Konsultasi Selesai', '142', 'bi-check2-circle', '#16a34a', '#dcfce7'],
    ['Resep Bulan Ini', '267', 'bi-capsule', '#7c3aed', '#ede9fe'],
  ];
  foreach ($stats as $s): ?>
    <div class="col-md-6 col-lg-3">
      <div class="stat-card">
        <div class="icon-wrap" style="background:<?php echo $s[4]; ?>; color:<?php echo $s[3]; ?>">
          <i class="bi <?php echo $s[2]; ?>"></i>
        </div>
        <h3><?php echo $s[1]; ?></h3>
        <div class="label"><?php echo $s[0]; ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Antrian Pasien Hari Ini</h6>
        <span class="pill pill-info">5 menunggu</span>
      </div>
      <table class="table clean">
        <thead><tr><th>No</th><th>Pasien</th><th>Keluhan</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td><strong>A-01</strong></td><td>Siti Aminah</td><td>Demam & batuk</td><td><span class="pill pill-warn">Dipanggil</span></td></tr>
          <tr><td><strong>A-02</strong></td><td>Joko Widodo</td><td>Kontrol hipertensi</td><td><span class="pill pill-info">Menunggu</span></td></tr>
          <tr><td><strong>A-03</strong></td><td>Maria Santosa</td><td>Sakit kepala</td><td><span class="pill pill-info">Menunggu</span></td></tr>
          <tr><td><strong>A-04</strong></td><td>Ahmad Yani</td><td>Diabetes - kontrol</td><td><span class="pill pill-info">Menunggu</span></td></tr>
          <tr><td><strong>A-05</strong></td><td>Dewi Lestari</td><td>Pemeriksaan umum</td><td><span class="pill pill-info">Menunggu</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel mb-3">
      <h6 class="fw-bold mb-3">Jadwal Hari Ini</h6>
      <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">08:00</div>
          <small class="text-muted">12:00</small>
        </div>
        <div><div class="fw-semibold">Praktik Pagi</div><small class="text-muted">Ruang Periksa 2</small></div>
      </div>
      <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">13:00</div>
          <small class="text-muted">14:00</small>
        </div>
        <div><div class="fw-semibold">Meeting Dokter</div><small class="text-muted">Ruang Rapat</small></div>
      </div>
      <div class="d-flex gap-3">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">15:00</div>
          <small class="text-muted">18:00</small>
        </div>
        <div><div class="fw-semibold">Praktik Sore</div><small class="text-muted">Ruang Periksa 2</small></div>
      </div>
    </div>
    <div class="panel">
      <h6 class="fw-bold mb-3">Aksi Cepat</h6>
      <div class="d-grid gap-2">
        <a href="#" class="btn btn-light text-start"><i class="bi bi-person-plus me-2"></i>Panggil Pasien Berikutnya</a>
        <a href="#" class="btn btn-light text-start"><i class="bi bi-pencil-square me-2"></i>Tulis Rekam Medis</a>
        <a href="#" class="btn btn-light text-start"><i class="bi bi-capsule me-2"></i>Buat Resep Baru</a>
      </div>
    </div>
  </div>
</div>

<audio id="audioSOS" src="alarm.mp3" preload="auto" loop></audio>

<div id="notifDarurat" style="display: none; position: fixed; bottom: 30px; right: 30px; background-color: #dc3545; color: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 9999; width: 350px; border: 3px solid #ffc107;">
    <h5 style="margin-top: 0; display: flex; align-items: center; gap: 10px; font-weight: bold;">
        <i class="bi bi-exclamation-triangle-fill" style="color: #ffc107; font-size: 1.5rem;"></i> DARURAT MEDIS!
    </h5>
    <hr style="border-color: rgba(255,255,255,0.4);">
    <p class="mb-1"><strong>NIM Pasien:</strong> <span id="sosNIM">-</span></p>
    <p class="mb-3"><strong>Waktu:</strong> <span id="sosWaktu">-</span></p>
    
    <a id="btnMaps" href="#" target="_blank" class="btn btn-light w-100 fw-bold mb-2 text-danger" style="border-radius: 8px;">
        <i class="bi bi-geo-alt-fill"></i> BUKA LOKASI MAPS
    </a>
    
    <button onclick="terimaPanggilan()" class="btn btn-success w-100 fw-bold" style="border-radius: 8px;">
        <i class="bi bi-check-circle-fill"></i> TANDAI SEDANG DIPROSES
    </button>
</div>

<script>
let alarmAktif = false;
let logIdSekarang = null;

function pantauSOS() {
    if (alarmAktif) return;

    fetch('cek_sos.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'darurat') {
                aktifkanAlarm(data.data);
            }
        })
        .catch(error => console.error('Error fetching SOS:', error));
}

function aktifkanAlarm(data) {
    alarmAktif = true;
    logIdSekarang = data.LogID;
    
    document.getElementById('sosNIM').innerText = data.NIM;
    document.getElementById('sosWaktu').innerText = data.WaktuKejadian;
    document.getElementById('btnMaps').href = `https://www.google.com/maps/search/?api=1&query=${data.Latitude},${data.Longitude}`;
    
    document.getElementById('notifDarurat').style.display = 'block';
    
    let audio = document.getElementById('audioSOS');
    audio.play().catch(e => {
        console.log("Audio autoplay diblokir browser. Pastikan user pernah berinteraksi dengan halaman.");
    });
}

function terimaPanggilan() {
    // 1. Matikan suara dan sembunyikan UI
    let audio = document.getElementById('audioSOS');
    audio.pause();
    audio.currentTime = 0;
    
    document.getElementById('notifDarurat').style.display = 'none';
    alarmAktif = false;

    // 2. Tembak data ke update_sos.php agar status di database berubah
    fetch('update_sos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: logIdSekarang
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'sukses') {
            alert("Sinyal darurat berhasil diamankan. Tim medis bersiap menuju lokasi!");
        } else {
            alert("Gagal memperbarui status di database: " + data.pesan);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Terjadi kesalahan jaringan saat memperbarui status.");
    });
}

setInterval(pantauSOS, 3000);
</script>

<?php render_footer(); ?>