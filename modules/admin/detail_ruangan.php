<?php
session_start();
require_once("../../koneksi.php");

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin_ruangan', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$pageTitle = "Detail Ruangan";
include("../../includes/header.php");
include("../../includes/navbar.php");

$filter = $_GET['filter'] ?? 'all';

$where = "";
$title = "Semua Ruangan";

if ($filter === 'dipinjam') {
    $where = "WHERE r.status = 'dipinjam'";
    $title = "Ruangan Sedang Dipinjam";
} elseif ($filter === 'tersedia') {
    $where = "WHERE r.status = 'tersedia'";
    $title = "Ruangan Tersedia";
}

$query = "
SELECT 
    r.*,
    u.nama AS peminjam,
    p.tanggal_pinjam,
    p.tanggal_kembali
FROM ruangan r
LEFT JOIN peminjaman p 
    ON p.id_item = r.id 
    AND p.jenis = 'ruangan'
    AND p.status = 'dipinjam'
LEFT JOIN user u ON p.id_user = u.id
$where
ORDER BY r.nama_ruangan ASC
";

$result = $con->query($query);
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🏢 <?= $title ?></h4>
        <a href="admin_ruangan.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                // FOTO RUANGAN
                $foto = $row['foto'] ?? '';
                $fotoPath = "../../" . $foto;

                if (empty($foto) || !file_exists($fotoPath)) {
                    $fotoPath = "../../uploads/no-image.png";
                }
                ?>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4">

                        <!-- FOTO -->
                        <img src="<?= htmlspecialchars($fotoPath) ?>"
                             class="card-img-top rounded-top-4"
                             alt="Foto Ruangan"
                             style="height:220px; object-fit:cover;">

                        <div class="card-body">
                            <h5 class="fw-bold mb-2">
                                <?= htmlspecialchars($row['nama_ruangan']) ?>
                            </h5>

                            <p class="mb-1 text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <?= htmlspecialchars($row['lokasi']) ?>
                            </p>

                            <p class="mb-1">
                                <i class="bi bi-people"></i>
                                <strong>Kapasitas:</strong>
                                <?= htmlspecialchars($row['kapasitas']) ?> orang
                            </p>

                            <p class="mb-2">
                                <strong>Status:</strong>
                                <?php if ($row['status'] === 'tersedia'): ?>
                                    <span class="badge bg-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Dipinjam</span>
                                <?php endif; ?>
                            </p>

                            <hr>

                            <p class="mb-1">
                                <strong>Peminjam:</strong>
                                <?= htmlspecialchars($row['peminjam'] ?? '-') ?>
                            </p>

                            <p class="mb-1">
                                <strong>Tgl Pinjam:</strong>
                                <?= htmlspecialchars($row['tanggal_pinjam'] ?? '-') ?>
                            </p>

                            <p class="mb-0">
                                <strong>Tgl Kembali:</strong>
                                <?= htmlspecialchars($row['tanggal_kembali'] ?? '-') ?>
                            </p>

                            <hr>

                            <p class="small text-muted">
                                <?= htmlspecialchars($row['keterangan'] ?? '-') ?>
                            </p>
                        </div>

                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    Data ruangan tidak ditemukan
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
