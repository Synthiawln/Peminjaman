<?php
require('../fpdf/fpdf.php');
include '../koneksi.php';

// Cek parameter ID
if (!isset($_GET['id'])) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Parameter ID tidak ditemukan.</h3>");
}

$id = intval($_GET['id']);

// Ambil data lengkap peminjaman
$query = "
    SELECT 
        p.kode_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
        p.jenis,
        u.nama AS nama_pegawai,
        p.LO,
        b.nomor_ba,
        b.tanggal_dibuat
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id
    LEFT JOIN berita_acara b ON b.id_peminjaman = p.id
    WHERE p.id = ?
";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if (!$data) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>⚠️ Data tidak ditemukan di database.</h3>");
}

// ==================== GENERATE PDF ====================
class PDF extends FPDF {
    function Header() {
        // Logo instansi (opsional)
        if (file_exists('../assets/logo.png')) {
            $this->Image('../assets/logo.png', 15, 10, 25);
        }
        // Nama instansi
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, 'BADAN PEMERIKSA KEUANGAN', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, 'Subbag Umum dan Teknologi Informasi', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, 'Jl. HOS Cokroaminoto No.52, Tegalrejo, Kota Yogyakarta, D.I. Yogyakarta', 0, 1, 'C');
        $this->Ln(3);
        $this->Cell(0, 0, '', 'T'); // garis bawah header
        $this->Ln(10);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'BERITA ACARA PEMINJAMAN ' . strtoupper($data['jenis']), 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', '', 12);

// ====== DATA PEMINJAMAN ======
$pdf->Cell(60, 8, 'Nama Pegawai', 0, 0);
$pdf->Cell(0, 8, ': ' . $data['nama_pegawai'], 0, 1);
$pdf->Cell(60, 8, 'Kode Peminjaman', 0, 0);
$pdf->Cell(0, 8, ': ' . $data['kode_peminjaman'], 0, 1);
$pdf->Cell(60, 8, 'Jenis', 0, 0);
$pdf->Cell(0, 8, ': ' . ucfirst($data['jenis']), 0, 1);
$pdf->Cell(60, 8, 'Tanggal Pinjam', 0, 0);
$pdf->Cell(0, 8, ': ' . $data['tanggal_pinjam'], 0, 1);
$pdf->Cell(60, 8, 'Tanggal Kembali', 0, 0);
$pdf->Cell(0, 8, ': ' . $data['tanggal_kembali'], 0, 1);
$pdf->Cell(60, 8, 'Penanggung Jawab (LO)', 0, 0);
$pdf->Cell(0, 8, ': ' . $data['LO'], 0, 1);
$pdf->Ln(10);

// ====== ISI PERNYATAAN ======
$pdf->MultiCell(0, 8,
    "Dengan ini dinyatakan bahwa pegawai bernama " . $data['nama_pegawai'] .
    " telah melakukan peminjaman " . strtolower($data['jenis']) . 
    " dengan kode peminjaman " . $data['kode_peminjaman'] . 
    ". Peminjaman ini telah disetujui dan tercatat secara resmi oleh pihak penanggung jawab. " .
    "Diharapkan agar peminjaman ini digunakan dengan sebaik-baiknya dan dikembalikan sesuai jadwal yang telah ditetapkan.",
    0, 'J'
);
$pdf->Ln(15);

// ====== TEMPAT & TANGGAL (kanan sejajar) ======
$tanggal = !empty($data['tanggal_dibuat']) ? date('d F Y', strtotime($data['tanggal_dibuat'])) : date('d F Y');
$pdf->Cell(0, 8, 'Yogyakarta, ' . $tanggal, 0, 1, 'R');
$pdf->Ln(10);

// ====== TANDA TANGAN ======
$colWidth = 90;
$pdf->SetFont('Arial', '', 12);
$pdf->Cell($colWidth, 8, 'Peminjam,', 0, 0, 'C');
$pdf->Cell($colWidth, 8, 'Penanggung Jawab,', 0, 1, 'C');
$pdf->Ln(25);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($colWidth, 8, '(' . $data['nama_pegawai'] . ')', 0, 0, 'C');
$pdf->Cell($colWidth, 8, '(' . $data['LO'] . ')', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 0, 'C');
$pdf->Cell($colWidth, 10, 'NIP/NIK: ....................', 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Dokumen ini dibuat otomatis oleh sistem peminjaman aset instansi.', 0, 1, 'C');

// Output PDF ke browser
$pdf->Output('I', 'Berita_Acara_' . $data['kode_peminjaman'] . '.pdf');
exit;
?>
