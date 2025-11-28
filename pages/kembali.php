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

    // cek pending
    $pendQ = $con->prepare("SELECT * FROM peminjaman WHERE id_user = ? AND status = 'pending' ORDER BY created_at DESC");
    $pendQ->bind_param("i", $id_user);
    $pendQ->execute();
    $pendRes = $pendQ->get_result();

    if ($pendRes->num_rows > 0): ?>
        
        <div class="alert alert-info">
            Anda memiliki <strong><?= $pendRes->num_rows ?></strong> permintaan yang sedang diproses.
            Riwayat penuh disembunyikan sampai semua permintaan selesai.
        </div>

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
                <?php while ($row = $pendRes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['jenis'])); ?></td>
                        <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['tanggal_kembali']); ?></td>
                        <td><span class="badge bg-secondary">Proses</span></td>
                        <td><button class="btn btn-sm btn-outline-secondary" disabled>Menunggu</button></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else:

        // KHUSUS KENDARAAN (JOIN)
        $kendQ = $con->prepare("
            SELECT p.*, k.nama_kendaraan 
            FROM peminjaman p
            LEFT JOIN kendaraan k ON p.id_item = k.id
            WHERE p.id_user = ? AND p.jenis = 'kendaraan'
            ORDER BY p.created_at DESC
        ");
        $kendQ->bind_param("i", $id_user);
        $kendQ->execute();
        $kendaraan = $kendQ->get_result();

        // KHUSUS RUANGAN (JOIN)
        $ruangQ = $con->prepare("
            SELECT p.*, r.nama_ruangan
            FROM peminjaman p
            LEFT JOIN ruangan r ON p.id_item = r.id
            WHERE p.id_user = ? AND p.jenis = 'ruangan'
            ORDER BY p.created_at DESC
        ");
        $ruangQ->bind_param("i", $id_user);
        $ruangQ->execute();
        $ruangan = $ruangQ->get_result();

    ?>

    <!-- RIWAYAT KENDARAAN -->
    <h5 class="mt-4">Riwayat Peminjaman Kendaraan</h5>
    <?php if ($kendaraan->num_rows > 0): ?>
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Kode</th>
                    <th>Nama Kendaraan</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $kendaraan->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                    <td><?= htmlspecialchars($row['nama_kendaraan'] ?? ''); ?><?= empty($row['nama_kendaraan']) ? "<i class='text-muted'>Tidak ditemukan</i>" : "" ?></td>
                    <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                    <td><?= htmlspecialchars($row['tanggal_kembali']); ?></td>
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
        <p class="text-muted">Belum ada peminjaman kendaraan.</p>
    <?php endif; ?>


    <!-- RIWAYAT RUANGAN -->
    <h5 class="mt-4">Riwayat Peminjaman Ruangan</h5>
    <?php if ($ruangan->num_rows > 0): ?>
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Kode</th>
                    <th>Nama Ruangan</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $ruangan->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                        <td><?= htmlspecialchars($row['nama_ruangan'] ?? ''); ?><?= empty($row['nama_ruangan']) ? "<i class='text-muted'>Tidak ditemukan</i>" : "" ?></td>
                        <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['tanggal_kembali']); ?></td>
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
        <p class="text-muted">Belum ada peminjaman ruangan.</p>
    <?php endif; ?>

    <?php endif; ?>

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
