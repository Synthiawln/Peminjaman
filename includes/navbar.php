<?php
if (!isset($_SESSION)) session_start();
$pathPrefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
?>

<nav class="navbar navbar-expand-lg" style="background-color: #746616cf;">
  <div class="container-fluid">

  <!-- 🔹 Logo Instansi -->
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="<?= $pathPrefix ?>gambar/logo_BPK.png" alt="Logo" width="30" class="me-2">
      <span class="fw-bold text-dark">Sistem Peminjaman</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <li class="nav-item"><a class="nav-link text-dark" href="../admin.php">Admin</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link logout-link text-dark fw-semibold" href="../index.php">Home</a></li>
        <?php endif; ?>

         <li class="nav-item"><a class="nav-link logout-link text-dark fw-semibold" href="<?= $pathPrefix ?>pages/kembali.php">Pengembalian</a></li>
        <li class="nav-item">
          <a class="nav-link logout-link text-dark fw-semibold" href="<?= $pathPrefix ?>logout.php">Logout</a>
        </li>
      </ul>

      <span class="navbar-text text-dark d-flex align-items-center">
        <img src="<?= $pathPrefix ?>gambar/logo_user.png" alt="User Icon" width="28" height="28">
        <?= htmlspecialchars($_SESSION['nama']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
      </span>
    </div>
  </div>
</nav>

<style>
  .logout-link {
    transition: all 0.3s ease;
    border-radius: 5px;
  }

  .logout-link:hover {
    background-color: #74652fff;
    color: #ecd033cf !important;
    transform: translateY(-1px);
  }

  .navbar-brand img {
    object-fit: cover;
  }
</style>
