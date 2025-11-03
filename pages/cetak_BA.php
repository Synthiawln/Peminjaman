<?php
require_once '../koneksi.php';

// Ambil parameter GET
$file = $_GET['file'] ?? null;
$nomor_ba = $_GET['nomor_ba'] ?? null;

// Validasi parameter
if (!$file || !$nomor_ba) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>⚠️ Data tidak lengkap.<br>Pastikan halaman ini dipanggil setelah proses generate Berita Acara.</h3>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Berita Acara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
  <div class="card p-5 shadow-lg rounded-4">
    <h2 class="mb-3 text-success">✅ Berita Acara Berhasil Dibuat</h2>
    <p>Nomor Berita Acara: <b><?= htmlspecialchars($nomor_ba) ?></b></p>
    <a href="<?= htmlspecialchars($file) ?>" class="btn btn-dark" target="_blank">Lihat / Cetak PDF</a>
    <a href="../index.php" class="btn btn-outline-secondary ms-2">Kembali ke Dashboard</a>
  </div>
</div>
</body>
</html>
