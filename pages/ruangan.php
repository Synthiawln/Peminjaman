<?php
session_start();
require_once("../koneksi.php");

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Katalog Ruangan";
include("../includes/header.php");
include("../includes/navbar.php");

$stmt = $con->prepare("SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="container mt-4">
    <h3 class="mb-4">Katalog Ruangan</h3>

    <?php if ($res->num_rows > 0): ?>
        <div class="row">
            <?php while ($r = $res->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($r['foto']) && file_exists("../" . $r['foto'])): ?>
                            <img src="../<?= htmlspecialchars($r['foto']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($r['nama_ruangan']) ?>" 
                                 style="object-fit: cover; height: 200px;">
                        <?php else: ?>
                            <img src="../gambar/ruangan/no-image.png" 
                                 class="card-img-top" 
                                 alt="Tidak ada gambar" 
                                 style="object-fit: cover; height: 200px;">
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title mb-2"><?= htmlspecialchars($r['nama_ruangan']) ?></h5>
                                <p class="card-text text-muted mb-1">
                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($r['lokasi']) ?><br>
                                    <i class="bi bi-people"></i> Kapasitas: <?= htmlspecialchars($r['kapasitas']) ?>
                                </p>
                                <p>
                                    <?php if ($r['status'] === 'tersedia'): ?>
                                        <span class="badge-tersedia">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge-dipinjam">Dipinjam</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <a href="detail_item.php?jenis=ruangan&id=<?= $r['id'] ?>" class="btn btn-dark w-100 mt-2">
                                   Detail & Pinjam
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">Belum ada data ruangan.</div>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>

<style>

.btn-dark {
  background-color: #746616cf !important;
  color: #000 !important;
  border: none !important;
}
.btn-dark:hover {
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
