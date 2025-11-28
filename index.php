<?php
session_start();
include_once("koneksi.php");

if (!isset($_SESSION['username'])) {
    header('Location: modules/auth/login.php');
    exit();
}

$pageTitle = "Dashboard Pegawai";
include("includes/header.php");
include("includes/navbar.php");
?>

<div class="container mt-4">
    <h3 class="mb-3">Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?> 👋</h3>
    <p class="text-muted">Silakan pilih modul peminjaman di bawah ini.</p>

    <!-- Modul Pilihan -->
    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 hover-shadow">
                <div class="card-body text-center">
                    <img src="gambar/ruangRapat.jpg" alt="Ruangan" width="240" class="mb-3">
                    <h5 class="card-title">Peminjaman Ruangan</h5>
                    <p class="card-text text-muted">Lihat daftar ruangan yang tersedia dan ajukan peminjaman.</p>
                    <a href="pages/ruangan.php" class="btn btn-dark w-100 mt-2">Lihat Ruangan</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 hover-shadow">
                <div class="card-body text-center">
                    <img src="gambar/mobil.jpg" alt="Kendaraan" width="300" class="mb-3">
                    <h5 class="card-title">Peminjaman Kendaraan</h5>
                    <p class="card-text text-muted">Lihat kendaraan yang tersedia untuk digunakan.</p>
                    <a href="pages/kendaraan.php" class="btn btn-dark w-100 mt-2">Lihat Kendaraan</a>
                </div>
            </div>
        </div>
    </div>


    <div id="riwayat" class="card mt-5">
        <div class="card-header" style="background-color: #746616cf; color: #000;">
            <i class="bi bi-clock-history me-2"></i> Riwayat Peminjaman Anda
        </div>
        <div class="card-body">

        <?php
        $id_user = $_SESSION['id'];

        // Ambil semua peminjaman user
        $q = $con->prepare(
            "SELECT * FROM peminjaman 
             WHERE id_user = ? 
             ORDER BY created_at DESC"
        );
        $q->bind_param("i", $id_user);
        $q->execute();
        $result = $q->get_result();

        ?>

        <?php if ($result->num_rows > 0): ?>
            
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Jenis</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['kode_peminjaman']; ?></td>
                            <td><?= ucfirst($row['jenis']); ?></td>
                            <td><?= $row['tanggal_pinjam']; ?></td>
                            <td><?= $row['tanggal_kembali']; ?></td>

                            <td>
                                <?php
                                if ($row['status'] === 'pending'){
                                    echo '<span class="badge bg-secondary">Proses</span>';
                                } elseif ($row['status'] === '') {
                                    echo '<span class="badge bg-secondary">Proses Pengembalian</span>';
                                } elseif ($row['status'] === 'dipinjam') {
                                    echo '<span class="badge bg-danger">Dipinjam</span>';
                                } elseif ($row['status'] === 'dikembalikan') {
                                    echo '<span class="badge bg-success">Dikembalikan</span>';
                                } elseif ($row['status'] === 'rejected') {
                                    echo '<span class="badge bg-danger">Ditolak</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Proses</span>';
                                }
                                ?>
                            </td>

                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Menunggu Persetujuan</button>

                                <?php elseif ($row['status'] === ''): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Menunggu Persetujuan Pengembalian</button>

                                <?php elseif ($row['status'] === 'dipinjam'): ?>
                                    <a href="pages/kembali_form.php?id=<?= $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger">Kembalikan</a>

                                <?php elseif ($row['status'] === 'dikembalikan'): ?>
                                    <a href="pages/lihat_BA.php?id=<?= $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-success" target="_blank">Cetak BA</a>

                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p class="text-muted">Belum ada riwayat peminjaman.</p>
        <?php endif; ?>

        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transform: translateY(-4px);
    transition: 0.2s;
}
.btn-dark {
  background-color: #746616cf !important;
  color: #000 !important;
  border: none !important;
}
.btn-dark:hover {
  background-color: #74652fff !important;
}
</style>

<?php include("includes/footer.php"); ?>
