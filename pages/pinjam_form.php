<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$id_item = $_GET['id'];
$jenis = $_GET['jenis']; 
$minDate = date('Y-m-d');

$id = (int)$_GET['id'];
$jenis = $_GET['jenis'] ?? ''; 


if ($jenis === 'ruangan') {
    $stmt = $con->prepare("SELECT *, 'ruangan' AS jenis FROM ruangan WHERE id = ?");
} elseif ($jenis === 'kendaraan') {
    $stmt = $con->prepare("SELECT *, 'kendaraan' AS jenis FROM kendaraan WHERE id = ?");
} else {
    
    $stmt = $con->prepare("SELECT *, 'kendaraan' AS jenis FROM kendaraan WHERE id = ?");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<h4>Item tidak ditemukan.</h4>";
    exit();
}

$item = $res->fetch_assoc();
$jenis = $item['jenis']; 

$file = $item['foto'] ?? '';
$fotoPath = "../" . $file; 


if (empty($file) || !file_exists($fotoPath)) {
    $fotoPath = "../uploads/no-image.png"; 
}


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
    <div class="mb-3">
    <a href="../index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    </div>
    <h3 class="text-center mb-4">Form Peminjaman <?= ucfirst($jenis) ?></h3>
      <img src="<?= htmlspecialchars($fotoPath) ?>" class="detail-img" alt="Foto <?= htmlspecialchars($jenis) ?>">

    <form action="pinjam_proses.php" method="POST">
      <input type="hidden" name="id_item" value="<?= htmlspecialchars($id_item) ?>">
      <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis) ?>">
    
       <?php if ($jenis === 'ruangan'): ?>
                        <p><strong>Nama:</strong> <?= htmlspecialchars($item['nama_ruangan']) ?> orang</p>
                        <p><strong>Kapasitas:</strong> <?= htmlspecialchars($item['kapasitas'] ?? '-') ?></p>
                    <?php else: ?>
                        <p><strong>Nama:</strong> <?= htmlspecialchars($item['nama_kendaraan']) ?></p>
                        <p><strong>No. Polisi:</strong> <?= htmlspecialchars($item['no_polisi']) ?></p>
                    <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Tanggal Pinjam</label>
        <input type="date" name="tgl_pinjam" class="form-control" required min="<?= $minDate ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Tanggal Kembali</label>
        <input type="date" name="tgl_kembali" class="form-control" required min="<?= $minDate ?>">
      </div>

      <?php if ($jenis === 'kendaraan' || $jenis === 'ruangan'): ?>
        <div class="mb-3">
          <label class="form-label">Penanggung Jawab (LO)</label>
          <input type="text" class="form-control" value="(otomatis: admin <?= $jenis === 'kendaraan' ? 'kendaraan' : 'ruangan' ?>)" disabled>
          <div class="form-text">Nama LO akan diisi otomatis oleh sistem (admin <?= $jenis === 'kendaraan' ? 'kendaraan' : 'ruangan' ?>).</div>
        </div>
      <?php else: ?>
        <div class="mb-3">
          <label class="form-label">Penanggung Jawab (LO)</label>
          <input type="text" name="penanggung_jawab" class="form-control" placeholder="Masukkan nama LO" required>
        </div>
      <?php endif; ?>

      <?php if ($jenis === 'ruangan'): ?>
        <div class="mb-3">
          <label class="form-label">Permintaan Kelengkapan Sarana/Prasarana</label>
          <textarea name="tambah_fasilitas" class="form-control" rows="3"
            placeholder="Contoh: proyektor, tambahan kursi 30, sound system, meja rapat, dll."></textarea>
          <div class="form-text">Opsional. Isi jika membutuhkan fasilitas tambahan.</div>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-dark w-100">Kirim Permintaan Peminjaman</button>
    </form>
  </div>
</div>
  <script>
    document.querySelectorAll('input[type="date"]').forEach(function(input) {
        input.addEventListener('click', function() {
            if (this.showPicker) {
                this.showPicker();
            }
        });
    });

    // script redirect setelah submit
    document.querySelector("form").addEventListener("submit", function() {
        setTimeout(function() {
            window.location.href = "../index.php";
        }, 500);
    });
  </script>
  <style>
    .detail-img {
            width: 50%;
            height: 350px;
            object-fit: cover;
            border-bottom: 3px solid #d0b84c;

            display: block;     /* biar bisa di-auto margin */
            margin: 0 auto;     /* ini yang bikin ke tengah */
        }
  </style>
</body>
</html>
