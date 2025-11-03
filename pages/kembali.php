<?php
session_start();
include_once("../koneksi.php");

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Menu Pengembalian";
include("../includes/header.php");
include("../includes/navbar.php");
?>

<div class="container mt-4">
    <h3 class="mb-3">Menu Pengembalian</h3>
    <p class="text-muted">Silakan ajukan pengembalian di bawah ini.</p>

    
    <div class="card mt-5">
        <div class="card-header" style="background-color: #746616cf; color: #000;">
            <i class="bi bi-clock-history me-2"></i> Riwayat Peminjaman Anda
        </div>
        <div class="card-body">
            <?php
            $id_user = $_SESSION['id'];
            $query = $con->prepare("SELECT * FROM peminjaman WHERE id_user = ? ORDER BY created_at DESC");
            $query->bind_param("i", $id_user);
            $query->execute();
            $result = $query->get_result();
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
                                <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                                <td><?= ucfirst(htmlspecialchars($row['jenis'])); ?></td>
                                <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                                <td><?= htmlspecialchars($row['tanggal_kembali']); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'dipinjam'): ?>
                                        <span class="badge bg-warning text-dark">Dipinjam</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Dikembalikan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'dipinjam'): ?>
                                        <a href="kembali_form.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary">Kembalikan</a>
                                    <?php else: ?>
                                        <a href="cetak_BA.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-success">Cetak BA</a>
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
  background-color: #ffde0bcc !important;
}
</style>

<?php include("../includes/footer.php"); ?>
