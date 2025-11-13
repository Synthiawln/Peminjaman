<?php
if (!isset($_SESSION)) session_start();

$path = str_replace('\\', '/', $_SERVER['PHP_SELF']);
if (strpos($path, '/modules/admin/') !== false) {
    $pathPrefix = '../../';
} elseif (strpos($path, '/modules/') !== false) {
    $pathPrefix = '../';
} elseif (strpos($path, '/pages/') !== false) {
    $pathPrefix = '../';
} else {
    $pathPrefix = '';
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Guest';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

$systemTitle = 'Sistem Peminjaman';
$label = 'Home';
$link = $pathPrefix . 'index.php';

if ($role === 'super_admin') {
    $systemTitle = 'Super Admin';
    $label = 'Dashboard Super Admin';
    $link = $pathPrefix . 'modules/admin/superadmin.php';
} elseif ($role === 'admin_ruangan') {
    $systemTitle = 'Admin Ruangan';
    $label = 'Dashboard Admin Ruangan';
    $link = $pathPrefix . 'modules/admin/admin_ruangan.php';
} elseif ($role === 'admin_kendaraan') {
    $systemTitle = 'Admin Kendaraan';
    $label = 'Dashboard Admin Kendaraan';
    $link = $pathPrefix . 'modules/admin/admin_kendaraan.php';
} elseif ($role === 'user') {
    $systemTitle = 'Pegawai';
    $label = 'Home';
    $link = $pathPrefix . 'index.php';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background: linear-gradient(90deg, #8d7b35, #5e4b1f);">
  <div class="container-fluid px-4">
    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center fw-bold text-light" href="<?= $link ?>">
      <img src="<?= $pathPrefix ?>gambar/logo_BPK.png" alt="Logo" width="35" class="me-2 rounded">
      <?= htmlspecialchars($systemTitle) ?>
    </a>

    <!-- Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (in_array($role, ['super_admin', 'admin_ruangan', 'admin_kendaraan'])): ?>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="<?= $link ?>">
              <i class="bi bi-speedometer2 me-1"></i> <?= htmlspecialchars($label) ?>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light fw-semibold" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-file-earmark-text me-1"></i> Laporan
            </a>
            <ul class="dropdown-menu border-0 shadow-sm" aria-labelledby="reportsDropdown">
              <li><a class="dropdown-item" href="<?= $pathPrefix ?>pages/roports_weekly.php">📅 Laporan Mingguan</a></li>
              <li><a class="dropdown-item" href="<?= $pathPrefix ?>pages/reports_monthly.php">📆 Laporan Bulanan</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="<?= $pathPrefix ?>index.php">🏠 Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="#riwayat">📜 Riwayat</a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- User Dropdown -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center text-light fw-semibold" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            <img src="<?= $pathPrefix ?>gambar/logo_user.png" alt="User" width="32" height="32" class="rounded-circle me-2 border border-light">
            <?= htmlspecialchars($nama) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><h6 class="dropdown-header">👤 Profil Pengguna</h6></li>
            <li><a class="dropdown-item"><?= htmlspecialchars($nama) ?></a></li>
            <li><a class="dropdown-item"><?= htmlspecialchars($username) ?></a></li>
            <!-- <li><a class="dropdown-item">Role: <?= htmlspecialchars($role) ?></a></li> -->
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger fw-semibold" href="<?= $pathPrefix ?>modules/auth/logout.php">
              <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>


<!-- Smooth Scroll -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });
});
</script>

<style>
.navbar-nav .nav-link {
  transition: all 0.3s ease;
}
.navbar-nav .nav-link:hover {
  color: #ffd966 !important;
  transform: translateY(-2px);
}
.dropdown-menu a:hover {
  background-color: #f9f2d7;
}
.navbar {
  font-family: 'Poppins', sans-serif;
  font-size: 15px;
}
</style>
