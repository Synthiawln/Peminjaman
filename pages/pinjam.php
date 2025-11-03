<?php
session_start();
require_once("../koneksi.php");

if (!isset($_GET['kode'])) {
    header('Location: ../index.php'); exit();
}
$kode = $_GET['kode'];

$stmt = $con->prepare("SELECT p.*, u.nama as peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id WHERE p.kode_peminjaman = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo "Peminjaman tidak ditemukan."; exit();
}

$pageTitle = "Detail Peminjaman";
include("includes/header.php");
include("includes/navbar.php");
?>

<div class="container mt-4">
    <h3>Detail Peminjaman - <?= htmlspecialchars($row['kode_peminjaman']) ?></h3>
    <table class="table">
        <tr><th>Peminjam</th><td><?= htmlspecialchars($row['peminjam']) ?></td></tr>
        <tr><th>Jenis</th><td><?= htmlspecialchars($row['jenis']) ?></td></tr>
        <tr><th>Tanggal Pinjam</th><td><?= htmlspecialchars($row['tanggal_pinjam']) ?></td></tr>
        <tr><th>Tanggal Kembali</th><td><?= htmlspecialchars($row['tanggal_kembali']) ?></td></tr>
        <tr><th>LO</th><td><?= htmlspecialchars($row['lo']) ?></td></tr>
        <tr><th>Status</th><td><?= htmlspecialchars($row['status']) ?></td></tr>
    </table>

    <?php if ($row['status'] === 'dipinjam' && $row['id_user'] == $_SESSION['id']): ?>
        <a href="kembali_form.php?id_peminjaman=<?= $row['id'] ?>" class="btn btn-warning">Pengembalian</a>
    <?php endif; ?>

    <a href="generate_ba.php?id_peminjaman=<?= $row['id'] ?>" class="btn btn-secondary">Cetak Berita Acara</a>
</div>

<?php include("includes/footer.php"); ?>
