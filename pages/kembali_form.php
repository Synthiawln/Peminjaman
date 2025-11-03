<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// Ambil ID user yang login
$stmt_user = $con->prepare("SELECT id FROM user WHERE username = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user = $res_user->fetch_assoc();
$id_user = $user['id'];

// Cek apakah ada parameter ID (jika user klik tombol 'Kembalikan' langsung)
$id_peminjaman = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id_peminjaman) {
    // Ambil data peminjaman spesifik
    $stmt = $con->prepare("
        SELECT * FROM peminjaman 
        WHERE id = ? AND id_user = ? AND status = 'dipinjam'
    ");
    $stmt->bind_param("ii", $id_peminjaman, $id_user);
    $stmt->execute();
    $peminjaman = $stmt->get_result()->fetch_assoc();

    if (!$peminjaman) {
        die("<script>alert('Data peminjaman tidak ditemukan atau sudah dikembalikan.'); window.location='peminjaman_saya.php';</script>");
    }
} else {
    // Jika tidak ada ID → tampilkan semua
    $peminjaman_list = $con->query("
        SELECT * FROM peminjaman 
        WHERE id_user = $id_user AND status = 'dipinjam'
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Pengembalian Barang</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-4 shadow-lg rounded-4">
    <h3 class="text-center mb-4">Form Pengembalian Barang</h3>

    <?php if ($id_peminjaman): ?>
      <!-- Jika user langsung klik dari tombol "Kembalikan" -->
      <form action="kembali_proses.php" method="POST">
        <input type="hidden" name="id_peminjaman" value="<?= $peminjaman['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Kode Peminjaman</label>
          <input type="text" class="form-control" value="<?= $peminjaman['kode_peminjaman'] ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">Tanggal Pengembalian Aktual</label>
          <input type="date" name="tgl_kembali_aktual" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-dark w-100">Proses & Cetak Berita Acara</button>
      </form>

    <?php else: ?>
      <!-- Versi fallback jika user belum klik dari tombol -->
      <form action="kembali_proses.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Pilih Barang yang Dikembalikan</label>
          <select name="id_peminjaman" class="form-select" required>
            <option value="">-- Pilih --</option>
            <?php while ($row = $peminjaman_list->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>">
                <?= strtoupper($row['jenis']) ?> - <?= $row['kode_peminjaman'] ?> (Pinjam: <?= $row['tanggal_pinjam'] ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Tanggal Pengembalian Aktual</label>
          <input type="date" name="tgl_kembali_aktual" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-dark w-100">Proses & Cetak Berita Acara</button>
      </form>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
