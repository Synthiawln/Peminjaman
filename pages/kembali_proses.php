<?php
session_start();
include '../koneksi.php';

// === CEK INPUT ===
if (!isset($_POST['id_peminjaman'])) {
    die("Data tidak lengkap (ID peminjaman kosong).");
}

$id_peminjaman = intval($_POST['id_peminjaman']);
$keterangan_user = $_POST['keterangan_user'] ?? null;

// === AMBIL DATA PEMINJAMAN ===
$stmt = $con->prepare("
    SELECT p.*, u.nama AS peminjam
    FROM peminjaman p
    JOIN user u ON p.id_user = u.id
    WHERE p.id = ? AND p.status = 'dipinjam'
");
$stmt->bind_param("i", $id_peminjaman);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data peminjaman tidak ditemukan atau status bukan 'dipinjam'.");
}

$jenis = strtolower($data['jenis']); // kendaraan / ruangan

// === CARI ADMIN SESUAI JENIS ===
$admin_role = ($jenis === 'kendaraan') ? 'admin_kendaraan' : 'admin_ruangan';

$q_admin = $con->prepare("SELECT id FROM user WHERE role=? LIMIT 1");
$q_admin->bind_param("s", $admin_role);
$q_admin->execute();
$admin = $q_admin->get_result()->fetch_assoc();

$admin_to_notify = $admin ? intval($admin['id']) : null;

// === UPDATE STATUS PENDING RETURN ===
$update = $con->prepare("
    UPDATE peminjaman 
    SET status='pending_return', keterangan_user=? 
    WHERE id=?
");
$update->bind_param("si", $keterangan_user, $id_peminjaman);
$update->execute();

// === NOTIFIKASI KE ADMIN ===
if ($admin_to_notify) {
    $msg = "Pengajuan pengembalian $jenis (".$data['kode_peminjaman'].") menunggu persetujuan Anda.";
    $ins = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
    $ins->bind_param("is", $admin_to_notify, $msg);
    $ins->execute();
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permintaan Dikirim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4 shadow-lg">
        <h4>Permintaan Pengembalian Terkirim</h4>
        <p>Permintaan Anda telah dikirim ke admin untuk disetujui.</p>
        <a href="../index.php" class="btn btn-primary">Kembali ke Beranda</a>
    </div>
</div>
</body>
</html>
