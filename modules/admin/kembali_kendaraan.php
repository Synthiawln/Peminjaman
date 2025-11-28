<?php
session_start();
include_once("../../koneksi.php");
require('../../fpdf/fpdf.php');

// === CEK ROLE ADMIN ===
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin_kendaraan') {
    die("Unauthorized.");
}

// === CEK ID ===
if (!isset($_GET['id'])) {
    die("ID peminjaman tidak ditemukan.");
}

$id = intval($_GET['id']);

// === AMBIL DATA ===
$stmt = $con->prepare("
    SELECT p.*, u.nama AS nama_user, k.id AS id_kendaraan
    FROM peminjaman p
    JOIN user u ON p.id_user = u.id
    JOIN kendaraan k ON p.id_item = k.id
    WHERE p.id=? AND p.jenis='kendaraan'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data peminjaman kendaraan tidak ditemukan.");
}

// === HANYA BISA DI-APPROVE JIKA STATUS pending_return ===
if ($data['status'] !== '') {
    die("Status tidak valid untuk approve.");
}

// === UPDATE PEMINJAMAN JADI DIKEMBALIKAN ===
$up = $con->prepare("
    UPDATE peminjaman 
    SET status='dikembalikan', tanggal_kembali_aktual = NOW() 
    WHERE id=?
");
$up->bind_param("i", $id);
$up->execute();

// === UPDATE STATUS KENDARAAN MENJADI TERSEDIA ===
$up_item = $con->prepare("UPDATE kendaraan SET status='tersedia' WHERE id=?");
$up_item->bind_param("i", $data['id_kendaraan']);
$up_item->execute();

// === GENERATE PDF ===
$folder = "../pdf-kembali/" . date("Y");
if (!is_dir($folder)) mkdir($folder, 0777, true);

$filename = $folder . "/BA_" . $data['id'] . ".pdf";

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Berita Acara Pengembalian Kendaraan",0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,"Telah dikembalikan kendaraan dengan kode peminjaman ".$data['kode_peminjaman']);

$pdf->Output('F', $filename);

// === KIRIM NOTIF KE USER ===
$link = "/PinjamRuanganKendaraan/pdf-kembali/" . date("Y") . "/BA_" . $data['id'] . ".pdf";

$con->query("
    INSERT INTO notifications (id_user, message) 
    VALUES ({$data['id_user']}, 'Pengembalian kendaraan disetujui. $link', NOW())
");

// === REDIRECT ===
echo "<script>alert('Pengembalian kendaraan berhasil disetujui.'); window.location='admin_kendaraan.php';</script>";
?>
