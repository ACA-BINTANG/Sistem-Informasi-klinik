<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>ASTARhealth - Smart Campus Clinic</title>

  <!-- Favicons -->
  <link href="assets/img/kampus(1).jpg" rel="icon">

  <!-- Fonts (Menggunakan Plus Jakarta Sans agar lebih modern) -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  
  <!-- Main CSS File (Pastikan ini mengarah ke file CSS "Clinic" yang Anda punya) -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    :root {
      --astar-blue: #175cdd;
      --astar-dark: #112344;
      --astar-light: #f4f8ff;
    }

    body { font-family: 'Plus Jakarta Sans', sans-serif; }

    /* Interaktivitas Navigasi */
    .btn-login-nav {
      background: var(--astar-blue) !important;
      border-radius: 50px !important;
      padding: 10px 30px !important;
      color: white !important;
      font-weight: 700;
      box-shadow: 0 4px 15px rgba(23, 92, 221, 0.3);
      transition: all 0.3s ease;
    }

    .btn-login-nav:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(23, 92, 221, 0.4);
    }

    /* Hero Section Interaktif */
    .hero {
      padding: 160px 0 100px 0;
      background: linear-gradient(135deg, #fff 0%, var(--astar-light) 100%);
      overflow: hidden;
    }

    .hero-visual {
      position: relative;
    }

    .hero-visual .main-img {
      border-radius: 30px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.1);
      position: relative;
      z-index: 1;
    }

    /* Floating Card Effect */
    .floating-badge {
      position: absolute;
      background: white;
      padding: 20px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      z-index: 2;
      animation: floating 3s ease-in-out infinite;
    }

    .badge-1 { top: 10%; left: -10%; }
    .badge-2 { bottom: 10%; right: -5%; }

    @keyframes floating {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-15px); }
      100% { transform: translateY(0px); }
    }

    /* Service Card Lift */
    .service-item {
      background: white;
      padding: 40px;
      border-radius: 24px;
      border: 1px solid #f1f5f9;
      transition: all 0.4s ease;
    }

    .service-item:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
      border-color: var(--astar-blue);
    }

    .icon-circle {
      width: 70px;
      height: 70px;
      background: var(--astar-light);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 25px;
      color: var(--astar-blue);
      font-size: 2rem;
    }
    
    /* CTA Modern - Ukuran Lebih Ringkas */
.cta-modern {
  position: relative;
  overflow: hidden;
  border-radius: 30px; /* Sudut melengkung yang pas */
  background: linear-gradient(135deg, #0057B8 0%, #175cdd 100%);
  padding: 40px 20px; /* Padding dikecilkan agar kotak tidak kegedean */
  max-width: 1000px; /* Membatasi lebar maksimal kotak */
  margin: 0 auto; /* Menengahkan kotak */
  box-shadow: 0 15px 35px rgba(0, 87, 184, 0.2);
}

.cta-content {
  position: relative;
  z-index: 3;
}

/* Semua Teks Warna Putih */
.cta-title {
  color: #ffffff !important;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.cta-text {
  color: rgba(255, 255, 255, 0.9) !important;
  max-width: 550px;
  margin: 0 auto 30px;
  font-size: 1.05rem;
  line-height: 1.6;
}

/* Tombol dengan Teks Putih */
.btn-sso-modern {
  background: rgba(255, 255, 255, 0.1); /* Putih transparan */
  color: #ffffff !important;
  padding: 14px 35px;
  font-weight: 600;
  border-radius: 100px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border: 2px solid #ffffff; /* Garis tepi putih */
  transition: all 0.3s ease;
  text-decoration: none;
}

.btn-sso-modern:hover {
  background: #ffffff; /* Saat di-hover jadi putih solid */
  color: #0057B8 !important; /* Teks berubah biru saat hover agar terbaca */
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.btn-sso-modern i {
  font-size: 1.2rem;
}

/* Efek dekorasi cahaya dibikin lebih kecil */
.cta-modern::before {
  content: "";
  position: absolute;
  top: -50px;
  left: -50px;
  width: 150px;
  height: 150px;
  background: rgba(255, 255, 255, 0.1);
  filter: blur(40px);
  border-radius: 50%;
}
  </style>
</head>

<body class="index-page">

  <header id="header" class="header fixed-top">
    <!-- Top Bar -->
    <div class="topbar d-flex align-items-center bg-primary text-white py-2">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
            <small><i class="bi bi-geo-alt-fill me-2"></i> Kampus Politeknik Astar, Delta Silicon</small>
            <small class="ms-4 d-none d-md-block"><i class="bi bi-clock-fill me-2"></i> Jam Operasional: 07:30 - 16:30</small>
        </div>
      </div>
    </div>

    <div class="branding d-flex align-items-center shadow-sm bg-white">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.php" class="logo d-flex align-items-center">
          <img src="assets/img/logoA.png" alt="ASTARhealth" style="max-height: 97px !important;">
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="#hero" class="active">Beranda</a></li>
            <li><a href="#about">Visi & Misi</a></li>
            <li><a href="#services">Fitur Digital</a></li>
            <li><a href="login.php" class="btn-login-nav ms-lg-4 text-white">Portal SSO Login</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>

  <main class="main">

    <!-- Hero Section Interaktif -->
    <section id="hero" class="hero section">
      <div class="container">
        <div class="row align-items-center gy-5">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="trust-badge mb-3 d-inline-flex align-items-center py-2 px-3 bg-white rounded-pill border shadow-sm">
                <i class="bi bi-shield-check text-primary me-2"></i>
                <span class="small fw-bold">Smart Campus Health Integration</span>
            </div>
            <h1 class="display-4 fw-bold mb-4">Layanan Kesehatan <span class="text-primary">Terintegrasi</span> Untuk Mahasiswa</h1>
            <p class="lead text-secondary mb-5">
              Kelola kesehatan Anda di kampus dengan lebih cerdas. Booking dokter, cek ketersediaan obat, dan akses rekam medis digital dalam satu genggaman.
            </p>
            <div class="d-flex flex-wrap gap-3">
              <a href="login.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold">Ambil Antrean <i class="bi bi-arrow-right ms-2"></i></a>
              <a href="#services" class="btn btn-outline-secondary btn-lg rounded-pill px-5 py-3">Eksplorasi Layanan</a>
            </div>
          </div>
          
          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="hero-visual">
              <img src="assets/img/kampus (1).jpg" alt="Politeknik Astar Clinic" class="img-fluid main-img">
              
              <!-- Floating Card 1 -->
              <div class="floating-badge badge-1 d-none d-md-block">
                <div class="d-flex align-items-center">
                  <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3 text-success">
                    <i class="bi bi-check2-all fs-4"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold">Layanan Cepat</h6>
                    <small class="text-muted">Booking Tanpa Antre</small>
                  </div>
                </div>
              </div>

              <!-- Floating Card 2 -->
              <div class="floating-badge badge-2 d-none d-md-block">
                <div class="d-flex align-items-center">
                  <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary">
                    <i class="bi bi-capsule fs-4"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold">Cek Obat</h6>
                    <small class="text-muted">Data Real-time Siloam</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="section py-5">
        <div class="container" data-aos="fade-up">
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <h2 class="fw-bold text-primary mb-0">100%</h2>
                    <p class="text-secondary">Sivitas Akademika Tercover</p>
                </div>
                <div class="col-md-4">
                    <h2 class="fw-bold text-primary mb-0">Real-Time</h2>
                    <p class="text-secondary">Informasi Stok Obat</p>
                </div>
                <div class="col-md-4">
                    <h2 class="fw-bold text-primary mb-0">Paperless</h2>
                    <p class="text-secondary">Rekam Medis Digital</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
      <div class="container">
        <div class="row gy-5 align-items-center">
          <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-right">
            <img src="assets/img/kampus (1).jpg" alt="Fasilitas Medis" class="img-fluid rounded-5 shadow-lg">
          </div>
          <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-left">
            <h2 class="fw-bold mb-4">Transformasi Medis Di <br><span class="text-primary">Politeknik Astar</span></h2>
            <p class="text-muted mb-4">ASTARhealth hadir sebagai jembatan antara kebutuhan medis sivitas akademika dengan kemudahan teknologi digital modern.</p>
            
            <div class="d-flex mb-4">
                <div class="icon-circle me-4"><i class="bi bi-heart-pulse"></i></div>
                <div>
                    <h5 class="fw-bold">Pelayanan Personal</h5>
                    <p class="small text-secondary">Setiap rekam medis diolah secara privat dan akurat oleh tim medis profesional kami.</p>
                </div>
            </div>

            <div class="d-flex">
                <div class="icon-circle me-4"><i class="bi bi-building-check"></i></div>
                <div>
                    <h5 class="fw-bold">Standar Industri</h5>
                    <p class="small text-secondary">Bekerja sama dengan Siloam Hospital untuk pengadaan obat dan standar diagnosa penyakit.</p>
                </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section light-background bg-opacity-50">
      <div class="container section-title text-center mb-5" data-aos="fade-up">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3">FITUR UTAMA</span>
        <h2 class="fw-bold">Layanan Digital Untuk Anda</h2>
      </div>

      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item text-center">
              <div class="icon-circle mx-auto"><i class="bi bi-box-seam"></i></div>
              <h4 class="fw-bold mb-3">Inventori Obat</h4>
              <p class="text-muted">Cek ketersediaan obat secara instan melalui sistem sebelum melakukan kunjungan ke klinik.</p>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item text-center">
              <div class="icon-circle mx-auto"><i class="bi bi-calendar-check"></i></div>
              <h4 class="fw-bold mb-3">Booking Konsultasi</h4>
              <p class="text-muted">Pilih waktu luang di sela-sela perkuliahan Anda untuk berkonsultasi dengan dokter umum kami.</p>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item text-center">
              <div class="icon-circle mx-auto"><i class="bi bi-file-earmark-medical"></i></div>
              <h4 class="fw-bold mb-3">E-Medical Record</h4>
              <p class="text-muted">Lihat riwayat penyakit dan catatan kesehatan Anda secara transparan melalui portal login pasien.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

<!-- Call to Action Modernized -->
<section class="section py-5 mb-5">
  <div class="container" data-aos="zoom-in">
    <div class="cta-modern text-white text-center">
      <div class="cta-content">
        <div class="mb-3">
            <span class="badge rounded-pill px-3 py-2" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                <i class="bi bi-stars me-2"></i>Astar Care Plus
            </span>
        </div>
        <h2 class="cta-title mb-3">Bukan Hanya Sekadar Klinik</h2>
        <p class="cta-text">
          Kami peduli dengan kesejahteraan Anda selama menempuh pendidikan di Kampus Astar dengan fasilitas kesehatan berbasis digital.
        </p>
        <a href="login.php" class="btn-sso-modern">
          <span>Login SSO Sekarang</span>
          <i class="bi bi-arrow-right-circle-fill"></i>
        </a>
      </div>
    </div>
  </div>
</section>

  </main>

  <footer id="footer" class="footer position-relative light-background border-top">
    <div class="container copyright text-center py-5">
      <div class="footer-logo mb-4">
        <img src="assets/img/logoA.png" alt="ASTARhealth" style="max-height: 70px;">
      </div>
      <p class="mb-1 text-dark fw-bold">© ASTARhealth - Smart Clinic Management System</p>
      <div class="text-muted small">
        Politeknik Astar - Kampus Cikarang. Kawasan Industri Delta Silicon.
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  
  <script>
    AOS.init({
      duration: 1000,
      easing: 'ease-in-out',
      once: true
    });
  </script>

</body>
</html>