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

<nav class="navbar navbar-expand-lg" style="background-color: #746616cf;">
  <div class="container-fluid">
    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center" href="<?= $link ?>">
      <img src="<?= $pathPrefix ?>gambar/logo_BPK.png" alt="Logo" width="30" class="me-2">
      <span class="fw-bold text-dark"><?= htmlspecialchars($systemTitle) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <?php if (in_array($role, ['super_admin', 'admin_ruangan', 'admin_kendaraan'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $link ?>"><?= htmlspecialchars($label) ?></a>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Laporan
            </a>
            <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
              <li><a class="dropdown-item" href="<?= $pathPrefix ?>pages/roports_weekly.php">Mingguan</a></li>
              <li><a class="dropdown-item" href="<?= $pathPrefix ?>pages/reports_monthly.php">Bulanan</a></li>
            </ul>
          </li>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $pathPrefix ?>index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#riwayat">Riwayat</a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Dropdown User -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            <img src="<?= $pathPrefix ?>gambar/logo_user.png" alt="User Icon" width="28" height="28" class="me-2">
            <?= htmlspecialchars($nama) ?> 
            <!-- (<?= htmlspecialchars($role ?: 'guest') ?>) -->
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">Profil</h6></li>
            <li><a class="dropdown-item" href="#">Nama: <?= htmlspecialchars($nama) ?></a></li>
            <li><a class="dropdown-item" href="#">Role: <?= htmlspecialchars($role) ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger fw-semibold" href="<?= $pathPrefix ?>modules/auth/logout.php">Logout</a></li>
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
  background-color: #74652fff;
  border-radius: 5px;
  transform: translateY(-1px);
}
</style>