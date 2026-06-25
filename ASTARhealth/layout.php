<?php
function render_header($title, $role, $name, $active_page = 'user') {
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo $title; ?> - ASTARhealth</title>
  
  <!-- Vendor CSS -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { 
      --astar-blue: #175cdd; 
      --astar-dark: #112344; 
      --bg-light: #f0f4fb;
    }
    
    body { 
      background-color: var(--bg-light); 
      font-family: 'Montserrat', sans-serif;
    }

    /* Header Styling */
    .header-nav { 
      background: white; 
      box-shadow: 0 2px 15px rgba(0,0,0,0.08); 
      padding: 10px 0;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo-img { max-height: 50px; }

    /* Navigasi Master Data */
    .master-nav {
      background: var(--astar-dark);
      padding: 10px 0;
      margin-bottom: 30px;
    }

    .nav-link-custom {
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 8px 15px;
      border-radius: 6px;
      transition: 0.3s;
      display: inline-block;
    }

    .nav-link-custom:hover {
      color: white;
      background: rgba(255,255,255,0.1);
    }

    .nav-link-custom.active {
      background: var(--astar-blue);
      color: white;
    }

    /* Card & Panel */
    .panel { 
      background: white; 
      border-radius: 15px; 
      padding: 25px; 
      box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
      border: none; 
      margin-bottom: 20px;
    }

    .section-title { 
      position: relative; 
      padding-left: 15px; 
      font-weight: 700; 
      color: var(--astar-dark); 
      display: flex; 
      align-items: center;
      margin-bottom: 20px;
    }

    .section-title::before { 
      content: ""; 
      position: absolute; 
      left: 0; 
      width: 5px; 
      height: 18px; 
      background-color: var(--astar-blue); 
      border-radius: 10px; 
    }

    .btn-astar {
      background-color: var(--astar-blue);
      color: white;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      padding: 10px 20px;
    }
    .btn-astar:hover { background-color: #134fb3; color: white; }
  </style>
</head>
<body>

  <!-- Top Header: Logo & User Profile -->
  <header class="header-nav">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="adminMaster.php">
        <img src="assets/img/logoA.png" alt="ASTARhealth" class="logo-img">
      </a>
      <div class="d-flex align-items-center">
        <div class="text-end me-3 d-none d-md-block">
          <small class="text-muted d-block" style="font-size: 0.7rem;">Login sebagai:</small>
          <span class="fw-bold d-block" style="color: var(--astar-dark);"><?php echo $name; ?></span>
        </div>
        <div class="dropdown">
          <button class="btn btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-4 text-primary"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
            <li class="px-3 py-2 border-bottom">
                <span class="badge bg-primary w-100"><?php echo $role; ?></span>
            </li>
            <li><a class="dropdown-item text-danger mt-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>

  <!-- Navigation Bar: Master Control Links -->
  <div class="master-nav">
    <div class="container text-center text-lg-start">
      <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-1">
        <a href="adminMaster.php?page=user" class="nav-link-custom <?php echo ($active_page == 'user') ? 'active' : ''; ?>">
          <i class="bi bi-person-lock me-1"></i> User
        </a>
        <a href="adminMaster.php?page=staff" class="nav-link-custom <?php echo ($active_page == 'staff') ? 'active' : ''; ?>">
          <i class="bi bi-person-badge me-1"></i> Staff
        </a>
        <a href="adminMaster.php?page=pasien" class="nav-link-custom <?php echo ($active_page == 'pasien') ? 'active' : ''; ?>">
          <i class="bi bi-people me-1"></i> Pasien
        </a>
        <a href="adminMaster.php?page=obat" class="nav-link-custom <?php echo ($active_page == 'obat') ? 'active' : ''; ?>">
          <i class="bi bi-capsule me-1"></i> Obat
        </a>
        <a href="adminMaster.php?page=diagnosa" class="nav-link-custom <?php echo ($active_page == 'diagnosa') ? 'active' : ''; ?>">
          <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosa
        </a>
        <a href="adminMaster.php?page=jadwal" class="nav-link-custom <?php echo ($active_page == 'jadwal') ? 'active' : ''; ?>">
          <i class="bi bi-calendar3 me-1"></i> Jadwal
        </a>
        <a href="adminMaster.php?page=supplier" class="nav-link-custom <?php echo ($active_page == 'supplier') ? 'active' : ''; ?>">
          <i class="bi bi-truck me-1"></i> Supplier
        </a>
      </div>
    </div>
  </div>

  <div class="container pb-5">
<?php
}

function render_footer() {
?>
  </div> <!-- End Container -->

  <footer class="text-center py-4 mt-5 text-muted small border-top bg-white">
    &copy; 2026 <strong>ASTARhealth</strong> - Member of ASTARtech. 
    <br><span class="text-secondary">Sistem Layanan Kesehatan Kampus Terintegrasi</span>
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>