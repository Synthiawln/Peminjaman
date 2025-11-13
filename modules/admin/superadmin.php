<?php
session_start();
include_once("../../koneksi.php");

// atuhentication
$adminRoles = array('super_admin');
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$pageTitle = "Dashboard Super Admin";
include("../../includes/header.php");
include("../../includes/navbar.php");
?>

<div class="container mt-4">
    <h3 class="mb-3">Dashboard Super Admin</h3>
    <p class="text-muted">Pantau dan kelola seluruh aktivitas sistem (user, ruangan, kendaraan, dan peminjaman).</p>

    <!-- Statistik Utama -->
    <?php
    $totalUser = $con->query("SELECT COUNT(*) AS total FROM user")->fetch_assoc()['total'];
    $totalRuangan = $con->query("SELECT COUNT(*) AS total FROM ruangan")->fetch_assoc()['total'];
    $totalKendaraan = $con->query("SELECT COUNT(*) AS total FROM kendaraan")->fetch_assoc()['total'];
    $totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];
    ?>

    <div class="row text-center mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-danger shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h2 class="fw-bold"><?=$totalUser;?></h2>
                    <p>User Terdaftar</p>
                </div>
                <i class="bi bi-people fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h2 class="fw-bold"><?=$totalRuangan;?></h2>
                    <p>Total Ruangan</p>
                </div>
                <i class="bi bi-door-closed fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h2 class="fw-bold"><?=$totalKendaraan;?></h2>
                    <p>Total Kendaraan</p>
                </div>
                <i class="bi bi-car-front fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h2 class="fw-bold"><?=$totalPeminjaman;?></h2>
                    <p>Total Peminjaman</p>
                </div>
                <i class="bi bi-person-check-fill fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
    </div>

    <!-- Navigasi -->
    <div class="mb-4">
        <h5>🔧 Aksi Cepat</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="../../pages/user_crud.php" class="btn btn-primary">
                <i class="bi bi-people"></i> Kelola User
            </a>
            <a href="admin_ruangan.php" class="btn btn-success">
                <i class="bi bi-door-closed"></i> Dashboard Ruangan
            </a>
            <a href="admin_kendaraan.php" class="btn btn-warning text-dark">
                <i class="bi bi-car-front"></i> Dashboard Kendaraan
            </a>
        </div>
    </div>

     <!-- Data User -->
    <div class="card shadow-sm mb-5">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #746616cf;">
            <span>👥 Data User</span>
            <a href="../../pages/user_crud.php" class="btn btn-sm btn-light fw-semibold">+ Tambah User</a>
        </div>
        <div class="card-body table-responsive">
            <?php
            $users = $con->query("SELECT * FROM user ORDER BY created_at DESC");
            ?>
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <!-- <th>Email</th> -->
                        <th>Role</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['id']; ?></td>
                            <td><?= htmlspecialchars($u['nama']); ?></td>
                            <td><?= htmlspecialchars($u['username']); ?></td>
                            <td><span class="badge text-dark" style="background-color : #d7d0a7cf"><?= htmlspecialchars($u['role']); ?></span></td>
                            <td class="text-center">
                                <a href="../../pages/user_crud.php?edit=<?= $u['id']; ?>" class="btn btn-sm me-1" style="background-color: #9e8a40ff; color: #ffffffff;">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="../../pages/user_crud.php?hapus=<?= $u['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Hapus user ini?')">
                                   <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daftar Semua Peminjaman -->
    <div class="card shadow-sm mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #746616cf;">
            <span>📋 Data Peminjaman Terbaru</span>
            <!-- <a href="pages/peminjaman_crud.php" class="btn btn-sm btn-light fw-semibold">+ Tambah Peminjaman</a> -->
        </div>
        <div class="card-body table-responsive">
            <?php
            $result = $con->query("
                SELECT p.*, u.nama 
                FROM peminjaman p
                JOIN user u ON p.id_user = u.id
                ORDER BY p.created_at DESC
                LIMIT 15
            ");
            ?>
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Peminjam</th>
                        <th>Jenis</th>
                        <!-- <th>Item</th> -->
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <!-- <th class="text-center">Aksi</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= ucfirst($row['jenis']); ?></td>
                            <!-- <td><?= htmlspecialchars($row['nama_barang'] ?? '-'); ?></td> -->
                            <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_kembali'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['status'] === 'dipinjam'): ?>
                                      <span class="status-label tersedia">Belum Dikembalikan</span>
                            <?php else: ?>
                                <span class="status-label dipinjam">Sudah Dikembalikan</span>
                            <?php endif; ?>
                            </td>
                            <!-- <td class="text-center">
                                <a href="pages/peminjaman_crud.php?edit=<?= $row['id']; ?>" class="btn btn-sm btn-warning me-1">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="pages/peminjaman_crud.php?id=<?= $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Yakin ingin menghapus data ini?')">
                                   <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td> -->
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
    .status-label.tersedia {
    background-color: #dc3545;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
}
.status-label.dipinjam {
    background-color: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
}

.card h5 {
  color: #5a4722;
}
</style>
<?php include("../../includes/footer.php"); ?>

