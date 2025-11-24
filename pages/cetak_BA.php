<?php
require_once '../koneksi.php';

// Ambil parameter GET
$file = $_GET['file'] ?? null;      // public URL, ex: /PinjamRuanganKendaraan/pdf-kembali/2025/TI/BA-123-RET.pdf
$nomor_ba = $_GET['nomor_ba'] ?? null;

// Validasi parameter
if (!$file || !$nomor_ba) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>⚠️ Data tidak lengkap.<br>Pastikan halaman ini dipanggil setelah proses generate Berita Acara.</h3>");
}

// Pastikan $file dimulai dengan / untuk mencegah path traversal
if (strpos($file, "\0") !== false) {
    die("Invalid file path.");
}
if ($file[0] !== '/') {
    // tambahkan leading slash jika perlu
    $file = '/' . $file;
}

// Cek apakah file benar-benar ada pada filesystem (debug / safety)
$full_fs_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $file;
$file_exists_fs = file_exists($full_fs_path);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Berita Acara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> .pdf-frame { width:100%; height:80vh; border:1px solid #ddd; } </style>
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
  <div class="card p-4 shadow-lg rounded-4">
    <h2 class="mb-2 text-success">✅ Berita Acara Berhasil Dibuat</h2>
    <p>Nomor Berita Acara: <b><?= htmlspecialchars($nomor_ba) ?></b></p>

    <?php if ($file_exists_fs): ?>
      <div class="mb-3">
        <a href="<?= htmlspecialchars($file) ?>" class="btn btn-dark" target="_blank" rel="noopener">Buka di Tab Baru (Lihat / Cetak PDF)</a>
        <a href="<?= htmlspecialchars($file) ?>" class="btn btn-outline-primary ms-2" download>Download PDF</a>
        <a href="../index.php" class="btn btn-outline-secondary ms-2">Kembali ke Dashboard</a>
      </div>

      <!-- tampilkan inline preview (iframe) -->
      <iframe src="<?= htmlspecialchars($file) ?>" class="pdf-frame" title="Preview PDF"></iframe>

    <?php else: ?>
      <div class="alert alert-danger">
        File PDF tidak ditemukan di server.<br>
        Path filesystem: <code><?= htmlspecialchars($full_fs_path) ?></code>
      </div>
      <a href="../index.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
