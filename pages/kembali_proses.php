<?php
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');

// Pastikan data dikirim lewat POST
if (!isset($_POST['id_peminjaman']) || !isset($_POST['tgl_kembali_aktual'])) {
    die("Data tidak lengkap.");
}

$id_peminjaman = intval($_POST['id_peminjaman']);
$tgl_kembali_aktual = $_POST['tgl_kembali_aktual'];

// Ambil data peminjaman + user
$stmt = $con->prepare("
    SELECT p.*, u.nama AS nama_user 
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id_peminjaman);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data peminjaman tidak ditemukan.");
}

// Update status peminjaman jadi dikembalikan
$update_peminjaman = $con->prepare("UPDATE peminjaman SET status='dikembalikan' WHERE id = ?");
$update_peminjaman->bind_param("i", $id_peminjaman);
$update_peminjaman->execute();

// Tentukan tabel berdasarkan jenis
$jenis = strtolower(trim($data['jenis']));
if ($jenis == 'ruangan' || $jenis == '2') {
    $tabel = 'ruangan';
} elseif ($jenis == 'kendaraan' || $jenis == '1') {
    $tabel = 'kendaraan';
} else {
    die("Jenis tidak dikenali: " . htmlspecialchars($data['jenis']));
}

// Update status item jadi tersedia
$update_item = $con->prepare("UPDATE $tabel SET status='tersedia' WHERE id = ?");
$update_item->bind_param("i", $data['id_item']);
$update_item->execute();

// Generate nomor BA pengembalian
$nomor_ba_kembali = $data['kode_peminjaman'] . '-RET';

// ==================== GENERATE PDF ====================
$pdf = new FPDF();
$pdf->AddPage();

// === KOP SURAT ===
if (file_exists('../assets/logo.png')) {
    $pdf->Image('../assets/logo.png', 15, 10, 25);
}
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'BADAN PEMERIKSA KEUANGAN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Subbag Umum dan Teknologi Informasi', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Jl. HOS Cokroaminoto No.52, Tegalrejo, Kec. Tegalrejo, Kota Yogyakarta, Daerah Istimewa Yogyakarta', 0, 1, 'C');
$pdf->Cell(0, 6, 'Email: humastu.yogyakarta@bpk.go.id | Telp. 0274-563635', 0, 1, 'C');
$pdf->Ln(3);
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(10);

// === JUDUL DOKUMEN ===
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'BERITA ACARA PENGEMBALIAN ' . strtoupper($jenis), 0, 1, 'C');
$pdf->Ln(5);

// === INFORMASI BERITA ACARA ===
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Nomor', 0, 0);
$pdf->Cell(0, 10, ': ' . $nomor_ba_kembali, 0, 1);
$pdf->Ln(4);

// === ISI NASKAH FORMAL ===
$teks = "Pada hari ini, tanggal " . date('d-m-Y', strtotime($tgl_kembali_aktual)) . 
        ", telah dilakukan pengembalian " . $jenis . " oleh:\n" .
        "Nama Peminjam     : " . $data['nama_user'] . "\n" .
        "Penanggung Jawab  : " . $data['lo'] . "\n" .
        "Tanggal Pinjam    : " . $data['tanggal_pinjam'] . "\n" .
        "Tanggal Kembali   : " . $tgl_kembali_aktual . "\n\n" .
        "Dengan ini dinyatakan bahwa " . $jenis . " yang telah dipinjam sebelumnya "
        . "telah dikembalikan dalam keadaan baik dan lengkap. "
        . "Berita acara ini dibuat sebagai bukti sah bahwa pengembalian telah diterima oleh pihak penanggung jawab.";

$pdf->MultiCell(0, 6, $teks);
$pdf->Ln(10);

// === BAGIAN TANDA TANGAN ===
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Yogyakarta, ' . date('d F Y', strtotime($tgl_kembali_aktual)), 0, 1, 'R');
$pdf->Ln(5);

$colWidth = 90;
$pdf->Cell($colWidth, 10, 'Peminjam,', 0, 0, 'C');
$pdf->Cell($colWidth, 10, 'Penanggung Jawab,', 0, 1, 'C');
$pdf->Ln(25); // ruang tanda tangan

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($colWidth, 10, '(' . $data['nama_user'] . ')', 0, 0, 'C');
$pdf->Cell($colWidth, 10, '(' . $data['lo'] . ')', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 0, 'C');
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Dokumen ini dibuat secara otomatis melalui sistem peminjaman aset instansi.', 0, 1, 'C');

// === SIMPAN PDF ===
$pdf_dir = '../pdf-kembali/';
if (!file_exists($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

$pdf_file = $pdf_dir . $nomor_ba_kembali . '.pdf';
$pdf->Output('F', $pdf_file);

// Redirect ke halaman cetak
header("Location: cetak_BA.php?file=" . urlencode($pdf_file) . "&nomor_ba=" . urlencode($nomor_ba_kembali));
exit;
?>
