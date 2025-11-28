<?php
session_start();
include '../koneksi.php';

// Validasi input
if (!isset($_POST['id_peminjaman'])) {
    die("Data tidak lengkap (ID peminjaman kosong).");
}

$id_peminjaman = intval($_POST['id_peminjaman']);
$keterangan_user = $_POST['keterangan_user'] ?? null;

// Ambil data peminjaman
$stmt = $con->prepare("SELECT p.*, u.nama AS peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id_peminjaman);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data peminjaman tidak ditemukan.");
}

$jenis = strtolower($data['jenis']);

// Jika sudah mengajukan, hentikan
if ($data['status'] === 'pending_return') {
    echo "<script>alert('Anda sudah mengajukan pengembalian untuk peminjaman ini. Menunggu persetujuan admin.'); window.location='../index.php';</script>";
    exit();
}

// Hanya peminjaman yang sedang dipinjam (atau memiliki status kosong) bisa diajukan
if (!in_array($data['status'], ['dipinjam', '', NULL], true)) {
    die("Hanya peminjaman dengan status 'dipinjam' yang dapat diajukan pengembalian.");
}

// Tentukan admin tujuan
$admin_role = ($jenis === 'kendaraan') ? 'admin_kendaraan' : 'admin_ruangan';
$q_admin = $con->prepare("SELECT id FROM user WHERE role = ? LIMIT 1");
$q_admin->bind_param("s", $admin_role);
$q_admin->execute();
$admin = $q_admin->get_result()->fetch_assoc();
$admin_to_notify = $admin ? intval($admin['id']) : null;

// Transaksi: update status + kirim notifikasi
$con->begin_transaction();
try {
    // Terima juga jika status kosong atau NULL (beberapa baris lama mungkin memiliki status kosong)
    $upd = $con->prepare("UPDATE peminjaman SET status='pending_return', keterangan_user=? WHERE id=? AND (status='dipinjam' OR status='' OR status IS NULL)");
    $upd->bind_param("si", $keterangan_user, $id_peminjaman);
    $upd->execute();

    if ($upd->affected_rows <= 0) {
        $con->rollback();
        die("Gagal mengajukan pengembalian. Silakan coba lagi atau hubungi admin.");
    }

    // pastikan tabel notifikasi ada
    $con->query("CREATE TABLE IF NOT EXISTS notifications (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        id_user INT,\n        message TEXT,\n        is_read TINYINT(1) DEFAULT 0,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        id_peminjaman INT DEFAULT NULL\n    ) ENGINE=InnoDB");

    if ($admin_to_notify) {
        $msg = "Pengajuan pengembalian $jenis (".$data['kode_peminjaman'].") menunggu persetujuan Anda.";
        $ins = $con->prepare("INSERT INTO notifications (id_user, message, id_peminjaman) VALUES (?, ?, ?)");
        $ins->bind_param("isi", $admin_to_notify, $msg, $id_peminjaman);
        $ins->execute();
    }

    $con->commit();
} catch (Exception $e) {
    $con->rollback();
    die('Terjadi kesalahan: ' . $e->getMessage());
}

// Sukses
echo "<script>alert('Permintaan pengembalian berhasil dikirim ke admin.'); window.location='../index.php';</script>";
exit();

?>
