<?php
session_start();
require_once("../koneksi.php");

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Katalog Kendaraan";

// Pastikan include path-nya benar (karena file ini di dalam folder /pages)
include("../includes/header.php");
include("../includes/navbar.php");

// Ambil list kendaraan dari database
$stmt = $con->prepare("SELECT * FROM kendaraan ORDER BY nama_kendaraan ASC");
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="container mt-4">
    <h3 class="mb-4">Katalog Kendaraan</h3>

    <div class="row">
        <?php if ($res->num_rows > 0): ?>
            <?php while ($r = $res->fetch_assoc()): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm border=0">
                        <?php if (!empty($r['foto'])): ?>
                            <img src="../<?= htmlspecialchars($r['foto']) ?>" class="card-img-top" alt="Foto Kendaraan">
                        <?php else: ?>
                            <img src="../gambar/kendaraan/no-image.png" class="card-img-top" alt="No Image">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($r['nama_kendaraan']) ?></h5>
                            <p class="card-text mb-1">
                                <strong>No. Polisi:</strong> <?= htmlspecialchars($r['no_polisi']) ?>
                            </p>

                            <p class="mb-2">
                                <?php if ($r['status'] === 'tersedia'): ?>
                                    <span class="badge-tersedia">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge-dipinjam">Dipinjam</span>
                                <?php endif; ?>
                            </p>

                            <a href="detail_item.php?jenis=kendaraan&id=<?= $r['id'] ?>" class="btn btn-primary w-100">
                                Detail & Pinjam
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">Belum ada data kendaraan.</div>
        <?php endif; ?>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

<style>

.btn-primary {
  background-color: #746616cf !important;
  color: #000 !important;
  border: none !important;
}
.btn-primary:hover {
  background-color: #74652fff !important;
}

/* 🔹 Badge tampilan lembut */
.badge-tersedia {
  background-color: #28a745 !important;
  color: #fff !important;
  border-radius: 50px !important; /* bikin bentuk lonjong */
  padding: 6px 14px !important;
  font-size: 0.9rem;
  font-weight: 500;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}

.badge-dipinjam {
  background-color: #dc3545 !important;
  color: #fff !important;
  border-radius: 50px !important;
  padding: 6px 14px !important;
  font-size: 0.9rem;
  font-weight: 500;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
</style>
