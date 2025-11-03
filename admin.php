<?php
session_start();
include_once("koneksi.php");

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$pageTitle = "Dashboard Admin";
include("includes/header.php");
include("includes/navbar.php");
?>

<div class="container mt-4">
    <h3>Dashboard Admin</h3>
    <p class="text-muted">Kelola data peminjaman, ruangan, dan kendaraan.</p>

    <!-- Total Statistik -->
    <?php
    $totalUser = $con->query("SELECT COUNT(*) AS total FROM user")->fetch_assoc()['total'];
    $totalRuangan = $con->query("SELECT COUNT(*) AS total FROM ruangan")->fetch_assoc()['total'];
    $totalKendaraan = $con->query("SELECT COUNT(*) AS total FROM kendaraan")->fetch_assoc()['total'];
    $totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];
    ?>

    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><?= $totalUser; ?></h5>
                    <p class="text-muted">User Terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><?= $totalRuangan; ?></h5>
                    <p class="text-muted">Ruangan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><?= $totalKendaraan; ?></h5>
                    <p class="text-muted">Kendaraan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><?= $totalPeminjaman; ?></h5>
                    <p class="text-muted">Total Peminjaman</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Semua Peminjaman -->
    <div class="card">
        <div class="card-header bg-dark text-white">Data Peminjaman Terbaru</div>
        <div class="card-body">
            <?php
            $result = $con->query("
                SELECT p.*, u.nama 
                FROM peminjaman p
                JOIN user u ON p.id_user = u.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Tanggal Pinjam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['jenis']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'dipinjam'): ?>
                                    <span class="badge bg-warning text-dark">Dipinjam</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
