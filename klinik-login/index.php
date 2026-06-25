<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Klinik Sehat Sentosa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
           <a class="navbar-brand d-flex align-items-center" href="#" data-aos="fade-down">
                <img src="img/logo.png" alt="Logo Klinik" class="custom-logo">
            </a>
            <div class="ms-auto" data-aos="fade-down" data-aos-delay="200">
                <a href="login.php" class="btn btn-klinik">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </a>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center d-flex align-items-center" style="min-height: 85vh;">
        <div class="hero-shape" style="width: 400px; height: 400px; top: -100px; left: -100px;"></div>
        <div class="hero-shape" style="width: 300px; height: 300px; bottom: 0; right: -50px; animation-delay: -4s;"></div>
        
        <div class="container position-relative z-1">
            <span class="badge bg-light text-klinik border border-primary-subtle px-3 py-2 rounded-pill mb-3 shadow-sm" data-aos="fade-down">Platform Medis Digital</span>
            <h1 class="fw-bolder mb-4 text-dark" style="font-size: clamp(2.5rem, 5vw, 4rem); letter-spacing: -1px;" data-aos="zoom-in" data-aos-duration="1000">Sistem Informasi <br> Manajemen <span class="text-klinik">Klinik</span></h1>
            <p class="lead text-secondary mb-5 mx-auto fw-normal" style="max-width: 700px; line-height: 1.8;" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                Platform terpadu untuk pelayanan kesehatan civitas akademika. Memudahkan penjadwalan, rekam medis digital, dan manajemen pasien secara lebih efisien dan modern.
            </p>
            <div data-aos="fade-up" data-aos-delay="400" class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="login.php" class="btn btn-klinik btn-lg shadow-lg">
                    Mulai Gunakan <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="#fitur" class="btn btn-outline-klinik btn-lg">
                    Pelajari Fitur
                </a>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-5 bg-white">
        <div class="container py-5">
            <div class="row align-items-center mb-5 mt-4">
                <div class="col-lg-6 mb-5 mb-lg-0 text-center" data-aos="fade-right" data-aos-duration="1200">
                    <img src="img/logokampus.png" alt="Logo Kampus" class="custom-logo img-floating" style="width: 85%; height: auto; max-width: 500px;">
                </div>
                <div class="col-lg-6 ps-lg-5" data-aos="fade-left" data-aos-duration="1200">
                    <h6 class="text-klinik fw-bold text-uppercase mb-2">Pelayanan Prima</h6>
                    <h2 class="fw-bolder mb-4" style="font-size: 2.5rem;">Fasilitas Kesehatan Terdepan</h2>
                    <p class="text-secondary mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                        AstarHealth didesain khusus untuk memberikan pelayanan medis pertama yang cepat dan tepat bagi mahasiswa dan seluruh staf. Terintegrasi dengan basis data kampus untuk memudahkan verifikasi dan pencatatan riwayat kesehatan.
                    </p>
                    <ul class="list-unstyled text-secondary fw-medium">
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-klinik me-3 fs-4"></i> Pelayanan medis dasar dan darurat</li>
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-klinik me-3 fs-4"></i> Konsultasi dokter terpercaya</li>
                        <li class="mb-3 d-flex align-items-center"><i class="bi bi-check-circle-fill text-klinik me-3 fs-4"></i> Integrasi rekam medis digital mahasiswa</li>
                    </ul>
                </div>
            </div>

            <div class="row g-4 mt-5 pt-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon"></i>
                        <h5 class="fw-bold mb-3">Manajemen Pasien</h5>
                        <p class="text-secondary small mb-0 lh-lg">Pengelolaan data pasien yang terstruktur, terenkripsi aman, dan sangat mudah dilacak riwayat pemeriksaannya.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check-fill feature-icon"></i>
                        <h5 class="fw-bold mb-3">Jadwal & Antrian</h5>
                        <p class="text-secondary small mb-0 lh-lg">Sistem antrian real-time untuk meminimalisir waktu tunggu, lengkap dengan notifikasi panggilan ke pasien.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <i class="bi bi-clipboard2-pulse-fill feature-icon"></i>
                        <h5 class="fw-bold mb-3">Rekam Medis Digital</h5>
                        <p class="text-secondary small mb-0 lh-lg">Penyimpanan riwayat kesehatan sepenuhnya paperless, mengurangi risiko kehilangan data fisik pasien.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container text-center">
            <p class="text-secondary small mb-1">
                &copy; <?php echo date('Y'); ?> AstarHealth. All rights reserved.
            </p>
            <p class="text-muted" style="font-size: 0.8rem;">
                Dikembangkan oleh Kelompok 4 - Sistem Informasi Manajemen Klinik. <br>
                Politeknik Astra Indonesia - Kampus Cikarang.

            </p>
        </div>
    </footer>

    <button type="button" class="btn" id="btn-back-to-top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        AOS.init({ once: true, offset: 80, duration: 800 });

        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) navbar.classList.add('navbar-scrolled');
            else navbar.classList.remove('navbar-scrolled');
        });

        let mybutton = document.getElementById("btn-back-to-top");
        window.onscroll = function () { scrollFunction() };

        function scrollFunction() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) mybutton.style.display = "block";
            else mybutton.style.display = "none";
        }

        mybutton.addEventListener("click", backToTop);
        function backToTop() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
    </script>
</body>
</html>