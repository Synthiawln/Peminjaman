<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// Ambil user id
$stmt_user = $con->prepare("SELECT id, nama FROM user WHERE username = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user = $res_user->fetch_assoc();
if (!$user) {
    die("User tidak ditemukan.");
}
$id_user = $user['id'];

// id peminjaman bisa dari GET
$id_peminjaman = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id_peminjaman) {
    // Ambil data peminjaman dan info item (ruangan/kendaraan)
    $stmt = $con->prepare("
        SELECT p.*, u.nama AS nama_user
        FROM peminjaman p
        LEFT JOIN user u ON p.id_user = u.id
        WHERE p.id = ? AND p.id_user = ? AND p.status = 'dipinjam'
    ");
    $stmt->bind_param("ii", $id_peminjaman, $id_user);
    $stmt->execute();
    $peminjaman = $stmt->get_result()->fetch_assoc();

    if (!$peminjaman) {
        die("<script>alert('Data peminjaman tidak ditemukan atau bukan status dipinjam.'); window.location='peminjaman_saya.php';</script>");
    }

    // Ambil detail item dari tabel kendaraan atau ruangan
    $jenis = strtolower(trim($peminjaman['jenis']));
    $detail_label = "";
    if ($jenis === 'kendaraan' || $jenis === '1') {
        $q = $con->prepare("SELECT nama_kendaraan, no_polisi FROM kendaraan WHERE id = ?");
        $q->bind_param("i", $peminjaman['id_item']);
        $q->execute();
        $d = $q->get_result()->fetch_assoc();
        $detail_label = $d ? "Kendaraan - " . ($d['nama_kendaraan'] ?? '-') . " | " . ($d['no_polisi'] ?? '-') : "Kendaraan - detail tidak ditemukan";
    } else {
        $q = $con->prepare("SELECT nama_ruangan FROM ruangan WHERE id = ?");
        $q->bind_param("i", $peminjaman['id_item']);
        $q->execute();
        $d = $q->get_result()->fetch_assoc();
        $detail_label = $d ? "Ruangan - " . ($d['nama_ruangan'] ?? '-') : "Ruangan - detail tidak ditemukan";
    }
} else {
    // Jika tidak ada id, tampilkan daftar peminjaman user yang masih 'dipinjam'
    $peminjaman_list = $con->prepare("SELECT p.*, (CASE WHEN p.jenis='kendaraan' OR p.jenis='1' THEN (SELECT nama_kendaraan FROM kendaraan k WHERE k.id = p.id_item) ELSE (SELECT nama_ruangan FROM ruangan r WHERE r.id = p.id_item) END) AS item_nama FROM peminjaman p WHERE p.id_user = ? AND p.status = 'dipinjam' ORDER BY p.tanggal_pinjam DESC");
    $peminjaman_list->bind_param("i", $id_user);
    $peminjaman_list->execute();
    $peminjaman_list = $peminjaman_list->get_result();
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
    <h3 class="text-center mb-4">Form Pengembalian</h3>

    <?php if (isset($peminjaman)): ?>

      <form action="kembali_proses_secure.php" method="POST">
        <input type="hidden" name="id_peminjaman" value="<?= htmlspecialchars($peminjaman['id']) ?>">

        <div class="mb-3">
          <label class="form-label">Kode Peminjaman</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($peminjaman['kode_peminjaman']) ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">Barang yang Dikembalikan</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($detail_label) ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">Keterangan (opsional)</label>
          <textarea name="keterangan_user" class="form-control" rows="3" placeholder="Catatan tambahan untuk admin (opsional)"></textarea>
        </div>

        <button type="submit" class="btn btn-dark w-100">Kirim</button>
      </form>

    <?php else: ?>

      <form action="kembali_proses_secure.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Pilih Peminjaman yang Akan Dikembalikan</label>
          <select name="id_peminjaman" class="form-select" required>
            <option value="">-- Pilih --</option>
            <?php while ($row = $peminjaman_list->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>">
                <?= strtoupper($row['jenis']) ?> - <?= $row['kode_peminjaman'] ?> (<?= $row['item_nama'] ?>) - <?= $row['tanggal_pinjam'] ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Keterangan (opsional)</label>
          <textarea name="keterangan_user" class="form-control" rows="3" placeholder="Catatan tambahan untuk admin (opsional)"></textarea>
        </div>

        <button type="submit" class="btn btn-dark w-100">Kirim</button>
      </form>

    <?php endif; ?>

  </div>
</div>
</body>
</html>
