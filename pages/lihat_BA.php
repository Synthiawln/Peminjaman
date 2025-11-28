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
// AMBIL DATA PEMINJAMAN
// ==========================
$query = "
    SELECT p.*, 
           u.nama AS peminjam, u.nip AS peminjam_nip, u.id AS id_peminjam,
           p.lo AS nama_lo,
           p.jenis, p.kode_peminjaman,
           p.tanggal_kembali AS kembali_real
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
// AMBIL NIP LO
// ==========================
$nip_lo = "-";

$qlo = $con->prepare("SELECT nip FROM user WHERE nama = ? LIMIT 1");
$qlo->bind_param("s", $data['nama_lo']);
$qlo->execute();
$lores = $qlo->get_result()->fetch_assoc();
if ($lores) $nip_lo = $lores['nip'];


// === FUNGSI TANGGAL LENGKAP ===
function namaHariIndo($date) {
    $hariInggris = date('l', strtotime($date));
    $daftar = [
        'Monday'=>"Senin",
        'Tuesday'=>"Selasa",
        'Wednesday'=>"Rabu",
        'Thursday'=>"Kamis",
        'Friday'=>"Jum'at",
        'Saturday'=>"Sabtu",
        'Sunday'=>"Minggu"
    ];
    return $daftar[$hariInggris];
}

function terbilang($angka) {
    $angka = abs($angka);
    $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

    if ($angka < 12) return $baca[$angka];
    elseif ($angka < 20) return terbilang($angka - 10) . " Belas";
    elseif ($angka < 100) return terbilang($angka/10) . " Puluh " . terbilang($angka % 10);
    elseif ($angka < 200) return "Seratus " . terbilang($angka - 100);
    elseif ($angka < 1000) return terbilang($angka/100) . " Ratus " . terbilang($angka % 100);
    elseif ($angka < 2000) return "Seribu " . terbilang($angka - 1000);
    elseif ($angka < 1000000) return terbilang($angka/1000) . " Ribu " . terbilang($angka % 1000);
}

function tanggalLengkapIndo($date) {
    $d = date('j', strtotime($date));
    $m = date('n', strtotime($date));
    $y = date('Y', strtotime($date));

    $bulan = [
        1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
        5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
        9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
    ];

    return terbilang($d) . " bulan " . $bulan[$m] . " tahun " . terbilang($y);
}

// ==========================
// PDF DENGAN HEADER RESMI
// ==========================
class PDF_BA_Kembali extends FPDF {
    function Header() {

        if (file_exists('../gambar/logo_BPK.png')) {
            $this->Image('../gambar/logo_BPK.png', 95, 10, 20);
        }

        $this->Ln(22);
        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'BADAN PEMERIKSA KEUANGAN',0,1,'C');
        $this->Cell(0,6,'PERWAKILAN PROVINSI DAERAH ISTIMEWA YOGYAKARTA',0,1,'C');

        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Jl. HOS Cokroaminoto No. 52 Yogyakarta 55244 Telp. (0274) 563635',0,1,'C');
        $this->Ln(1);

        // GARIS TEBAL
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        // GARIS TIPIS
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY()+1.5, 200, $this->GetY()+1.5);

        $this->Ln(5);
    }
}

$pdf = new PDF_BA_Kembali();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// ==========================
// JUDUL
// ==========================
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,7,"BERITA ACARA PENGEMBALIAN ".strtoupper($data['jenis']),0,1,'C');

$pdf->Ln(2);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor : ".$data['kode_peminjaman'],0,1,'C');

$pdf->Ln(5);


// === PARAGRAF PEMBUKA BARU ===
$tglToday = date('Y-m-d');
$hari = namaHariIndo($tglToday);
$tanggalPanjang = tanggalLengkapIndo($tglToday);
$tanggalAngka = date('d-m-Y', strtotime($tglToday));

$paragraf1 = 
"Pada hari {$hari} tanggal {$tanggalPanjang} ({$tanggalAngka}), yang bertanda tangan di bawah ini:";

$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,$paragraf1,0,'J');
$pdf->Ln(1);

// ===== PIHAK PERTAMA =====
$penanggung_jawab = $data['lo'] ?: "Penanggung Jawab";

$st = $con->prepare("SELECT nip FROM user WHERE nama=? LIMIT 1");
$st->bind_param("s",$penanggung_jawab);
$st->execute();
$res = $st->get_result()->fetch_assoc();
$lo_nip = $res['nip'] ?? '-';

$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$penanggung_jawab,0,1);

$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$lo_nip,0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pengadministrasi Umum',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Write(6, 'Selanjutnya disebut sebagai ');

$pdf->SetFont('Arial','B',11);
$pdf->Write(6, 'Pihak Pertama');

$pdf->Ln(6);

// ===== PIHAK KEDUA =====
$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$data['peminjam'],0,1);

$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$data['peminjam_nip'],0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Write(6, 'Selanjutnya disebut sebagai ');

$pdf->SetFont('Arial','B',11);
$pdf->Write(6, 'Pihak Kedua');

$pdf->Ln(8);

// ==========================
// ISI BERITA ACARA
// ==========================
$pdf->SetFont('Arial','',11);

$isi = 
"Bahwa Pihak Kedua telah mengembalikan ".strtolower($data['jenis'])." dengan kode peminjaman ".
$data['kode_peminjaman']." dalam keadaan baik dan lengkap. Pihak Pertama menyatakan bahwa ".
strtolower($data['jenis'])." tersebut telah diterima kembali dan proses peminjaman dinyatakan selesai.";

$pdf->MultiCell(0,6,$isi,0,'J');
$pdf->Ln(4);

$pdf->MultiCell(0,6,
"Demikian berita acara pengembalian ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.",
0,'J'
);

$pdf->Ln(12);


// ===== TANDA TANGAN =====
$col = 90;
$pdf->SetFont('Arial','',11);

$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(24);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$penanggung_jawab,0,0,'C');
$pdf->Cell($col,6,$data['peminjam'],0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'NIP. '.$lo_nip,0,0,'C');
$pdf->Cell($col,6,'NIP. '.$data['peminjam_nip'],0,1,'C');

$pdf->Ln(4);

// ===== MENGETAHUI =====
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'Mengetahui,',0,1,'C');
$pdf->Ln(25);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell(0,6,'Martin Ricardo Ferdinandus',0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'NIP. 196704262000031001',0,1,'C');

$pdf->Output("I", "BA_Pengembalian_".$data['kode_peminjaman'].".pdf");
exit;
?>
