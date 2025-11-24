<?php
require('../fpdf/fpdf.php');
include '../koneksi.php';

// ==========================
// CEK PARAMETER
// ==========================
if (!isset($_GET['id'])) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Parameter ID tidak ditemukan.</h3>");
}

$id = intval($_GET['id']);

// ==========================
// AMBIL DATA
// ==========================
$query = "
    SELECT 
        p.kode_peminjaman,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        p.status,
        p.jenis,
        u.nama AS nama_pegawai,
        p.LO,
        p.tanggal_kembali AS tgl_kembali_real
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id
    WHERE p.id = ?
";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>⚠️ Data tidak ditemukan.</h3>");
}

// ==========================
// PDF
// ==========================
class PDF extends FPDF {
    function Header() {
        if (file_exists('../gambar/logo_BPK.png')) {
            $this->Image('../gambar/logo_BPK.png', 95, 10, 20);
        }
        $this->Ln(23);
        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'BADAN PEMERIKSA KEUANGAN',0,1,'C');
        $this->Cell(0,6,'PERWAKILAN PROVINSI DAERAH ISTIMEWA YOGYAKARTA',0,1,'C');

        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Jl. HOS Cokroaminoto No. 52 Yogyakarta 55244 Telp. (0274) 563635',0,1,'C');
        $this->Ln(2);
        $this->Cell(0,0,'','T');
        $this->Ln(8);
    }
}

$pdf = new PDF();
$pdf->AddPage();

// ==========================
// JUDUL
// ==========================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,'BERITA ACARA PENGEMBALIAN ' . strtoupper($data['jenis']),0,1,'C');
$pdf->Ln(1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor: ".$data['kode_peminjaman'],0,1,'C');
$pdf->Ln(8);

// ==========================
// TANGGAL
// ==========================
$tanggal = date('d F Y', strtotime($data['tgl_kembali_real'] ?? date('Y-m-d')));

$pdf->MultiCell(0,6,
"Pada tanggal $tanggal, yang bertanda tangan di bawah ini:",
0,'J'
);
$pdf->Ln(3);

// ==========================
// PIHAK PERTAMA / LO
// ==========================
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK PERTAMA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$data['LO'],0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Penanggung Jawab',0,1);

$pdf->Ln(5);

// ==========================
// PIHAK KEDUA / PEMINJAM
// ==========================
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK KEDUA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$data['nama_pegawai'],0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->Ln(5);

// ==========================
// ISI POKOK – SUDAH DIKEMBALIKAN
// ==========================
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,
"Bahwa Pihak Kedua telah mengembalikan " . strtolower($data['jenis']) . 
" dengan kode peminjaman ".$data['kode_peminjaman']." dalam keadaan baik dan lengkap. ".
"Pihak Pertama menyatakan bahwa barang/ruangan tersebut telah diterima dan proses peminjaman dinyatakan selesai.",
0,'J'
);

$pdf->Ln(8);

$pdf->MultiCell(0,6,
"Demikian berita acara pengembalian ini dibuat dengan sebenarnya untuk digunakan sebagaimana mestinya.",
0,'J'
);

$pdf->Ln(10);

// ==========================
// TANDA TANGAN
// ==========================
$col = 90;

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(20);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$data['LO'],0,0,'C');
$pdf->Cell($col,6,$data['nama_pegawai'],0,1,'C');

$pdf->Ln(2);

$pdf->SetFont('Arial','',10);
$pdf->Cell($col,5,'NIP. -',0,0,'C');
$pdf->Cell($col,5,'NIP. -',0,1,'C');

$pdf->Output("I", "BA_Pengembalian_".$data['kode_peminjaman'].".pdf");
exit;
?>
