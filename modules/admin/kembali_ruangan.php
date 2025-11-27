<?php
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin_ruangan') {
    die("Unauthorized.");
}

if (!isset($_GET['id'])) {
    die("ID peminjaman tidak ditemukan.");
}

$id = intval($_GET['id']);

// ambil data
$stmt = $con->prepare("
    SELECT p.*, u.nama AS nama_user, r.nama_ruangan, r.lokasi
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id
    LEFT JOIN ruangan r ON p.id_item = r.id
    WHERE p.id=? AND jenis='ruangan'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data peminjaman ruangan tidak ditemukan.");
}

if ($data['status'] !== 'menunggu_persetujuan') {
    die("Status tidak valid untuk approve.");
}

// update status
$up = $con->prepare("UPDATE peminjaman SET status='dikembalikan', tanggal_kembali_aktual=NOW() WHERE id=?");
$up->bind_param("i", $id);
$up->execute();

// PDF
$folder = "../pdf-kembali/ruangan/".date("Y");
if (!is_dir($folder)) mkdir($folder, 0777, true);

$filename = $folder."/BA_RUANGAN_".$data['id'].".pdf";

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Berita Acara Pengembalian Ruangan",0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,
"Tanggal: ".date("d-m-Y")."\n\n".
"Nama Peminjam: ".$data['nama_user']."\n".
"Ruangan: ".$data['nama_ruangan']."\n".
"Lokasi: ".$data['lokasi']."\n\n".
"Dengan ini dinyatakan bahwa ruangan telah dikembalikan dalam keadaan:\n- Bersih\n- Tertata\n- Tidak ada kerusakan yang dilaporkan\n"
);

$pdf->Output('F', $filename);

// notif
$link = "/PinjamRuanganKendaraan/pdf-kembali/ruangan/".date("Y")."/BA_RUANGAN_".$data['id'].".pdf";

$con->query("
    INSERT INTO notifications (id_user, message, created_at)
    VALUES ({$data['id_user']}, 'Pengembalian ruangan disetujui. $link', NOW())
");

echo "<script>alert('Pengembalian ruangan berhasil disetujui.'); window.location='admin_ruangan.php';</script>";
?>
