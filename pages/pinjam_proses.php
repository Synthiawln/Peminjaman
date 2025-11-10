<?php
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];
$id_item = $_POST['id_item'];
$jenis = $_POST['jenis']; 
$tgl_pinjam = $_POST['tgl_pinjam'];
$tgl_kembali = $_POST['tgl_kembali'];
$penanggung_jawab = $_POST['penanggung_jawab'];

// Ambil id_user berdasarkan username
$stmt_user = $con->prepare("SELECT id, nama FROM user WHERE username = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$id_user = $user['id'];
$nama_user = $user['nama'];

// Generate nomor unik Berita Acara
$nomor_ba = 'BA-' . date('Ymd') . '-' . rand(100, 999);

// Simpan ke tabel peminjaman
$stmt = $con->prepare("
    INSERT INTO peminjaman 
    (kode_peminjaman, id_user, jenis, id_item, tanggal_pinjam, tanggal_kembali, lo, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'dipinjam')
");
$stmt->bind_param("sisssss", $nomor_ba, $id_user, $jenis, $id_item, $tgl_pinjam, $tgl_kembali, $penanggung_jawab);
$stmt->execute();

// Ubah status item (ruangan/kendaraan) jadi 'dipinjam'
$con->query("UPDATE $jenis SET status='dipinjam' WHERE id=$id_item");

// ==== GENERATE PDF BERITA ACARA ====
$pdf = new FPDF();
$pdf->AddPage();

// === KOP SURAT ===
if (file_exists('../gambar/logo_BPK.png')) {
    $pdf->Image('../gambar/logo_BPK.png', 15, 10, 25);
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

// === JUDUL SURAT ===
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'BERITA ACARA PEMINJAMAN ' . strtoupper($jenis), 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Nomor', 0, 0);
$pdf->Cell(0, 8, ': ' . $nomor_ba, 0, 1);
$pdf->Ln(4);

// === ISI NASKAH ===
$teks = "Pada hari ini, tanggal " . date('d-m-Y') . ", telah dilakukan kegiatan peminjaman "
      . $jenis . " oleh:\n"
      . "Nama Peminjam     : " . $nama_user . "\n"
      . "Penanggung Jawab  : " . $penanggung_jawab . "\n"
      . "Tanggal Pinjam    : " . $tgl_pinjam . "\n"
      . "Tanggal Kembali   : " . $tgl_kembali . "\n\n"
      . "Dengan ini, pihak peminjam bertanggung jawab penuh atas penggunaan fasilitas tersebut "
      . "dan bersedia mengembalikannya dalam kondisi baik dan tepat waktu.\n"
      . "Demikian berita acara ini dibuat dengan sebenarnya untuk dapat digunakan sebagaimana mestinya.";

$pdf->MultiCell(0, 6, $teks);
$pdf->Ln(10);

// === TANDA TANGAN ===
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Yogyakarta, ' . date('d F Y'), 0, 1, 'R');
$pdf->Ln(5);

$colWidth = 90;
$pdf->Cell($colWidth, 10, 'Peminjam,', 0, 0, 'C');
$pdf->Cell($colWidth, 10, 'Penanggung Jawab,', 0, 1, 'C');
$pdf->Ln(25); // ruang tanda tangan

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($colWidth, 10, '(' . $nama_user . ')', 0, 0, 'C');
$pdf->Cell($colWidth, 10, '(' . $penanggung_jawab . ')', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 0, 'C');
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Dokumen ini dibuat secara otomatis melalui sistem peminjaman aset instansi.', 0, 1, 'C');

// === SIMPAN PDF ===
if (!is_dir('pdf-pinjam')) {
    mkdir('pdf-pinjam', 0777, true);
}

$pdf_file = 'pdf-pinjam/' . $nomor_ba . '.pdf';
$pdf->Output('F', $pdf_file);

// Redirect ke halaman cetak
header("Location: cetak_BA.php?file=" . urlencode($pdf_file) . "&nomor_ba=" . urlencode($nomor_ba));
exit;
?>
