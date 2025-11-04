<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$id_item = $_GET['id'];
$jenis = $_GET['jenis']; // ruangan atau kendaraan
$minDate = date('Y-m-d'); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Peminjaman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-4 shadow-lg rounded-3">
    <h3 class="text-center mb-4">Form Peminjaman <?= ucfirst($jenis) ?></h3>

    <form action="pinjam_proses.php" method="POST">
      <input type="hidden" name="id_item" value="<?= htmlspecialchars($id_item) ?>">
      <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis) ?>">

      <div class="mb-3">
        <label class="form-label">Tanggal Pinjam</label>
        <input type="date" name="tgl_pinjam" class="form-control" required min="<?= $minDate ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Tanggal Kembali</label>
        <input type="date" name="tgl_kembali" class="form-control" required min="<?= $minDate ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Penanggung Jawab (LO)</label>
        <input type="text" name="penanggung_jawab" class="form-control" placeholder="Masukkan nama LO" required>
      </div>

      <button type="submit" class="btn btn-dark w-100">Simpan & Generate Berita Acara</button>
    </form>
  </div>
</div>
</body>
</html>
