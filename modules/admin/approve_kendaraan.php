<?php
session_start();
include_once("../../koneksi.php");
require('../../fpdf/fpdf.php');

$adminRoles = ['admin_kendaraan', 'super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

$stmt = $con->prepare("SELECT p.*, u.nama AS peminjam, u.nip AS peminjam_nip, u.id AS peminjam_id, k.nama_kendaraan, k.no_polisi 
    FROM peminjaman p 
    JOIN user u ON p.id_user = u.id 
    JOIN kendaraan k ON p.id_item = k.id 
    WHERE p.id = ? AND p.jenis='kendaraan'");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) {
    header('Location: admin_kendaraan.php');
    exit();
}

$penanggung_jawab = $data['lo'] ?? 'PENANGGUNG JAWAB';
$peminjam_name = $data['peminjam'] ?? '-';
$peminjam_nip = $data['peminjam_nip'] ?? '-';

$lo_nip = '-';
if (!empty($penanggung_jawab)) {
    $stlo = $con->prepare("SELECT nip FROM user WHERE nama = ? LIMIT 1");
    $stlo->bind_param("s", $penanggung_jawab);
    $stlo->execute();
    $reslo = $stlo->get_result()->fetch_assoc();
    if (!empty($reslo['nip'])) $lo_nip = $reslo['nip'];
    $stlo->close();
}

if ($action === 'reject') {
    $con->query("UPDATE peminjaman SET status='rejected' WHERE id=$id");
    $con->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $msg = 'Permintaan peminjaman kendaraan Anda ditolak oleh admin.';
    $stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
    $stmtn->bind_param("is", $data['peminjam_id'], $msg);
    $stmtn->execute();

    header('Location: admin_kendaraan.php');
    exit();
}

function generateUniqueBA($con) {
    do {
        $candidate = "BA." . date("Y") . "/TI/" . str_pad(rand(1,9999), 4, "0", STR_PAD_LEFT);
        $r = $con->query("SELECT COUNT(*) AS cnt FROM peminjaman WHERE kode_peminjaman = '".$con->real_escape_string($candidate)."'");
        $cnt = (int)$r->fetch_assoc()['cnt'];
    } while ($cnt > 0);
    return $candidate;
}

$nomor_ba = generateUniqueBA($con);
$con->query("UPDATE peminjaman SET status='dipinjam', kode_peminjaman='".$con->real_escape_string($nomor_ba)."' WHERE id=$id");
$con->query("UPDATE kendaraan SET status='dipinjam' WHERE id=".$data['id_item']);

$yearDir = date('Y');
$dir = __DIR__ . '/../../pdf-kembali/' . $yearDir;
$webPathDir = '/PinjamRuanganKendaraan/pdf-kembali/' . $yearDir;

if (!is_dir($dir)) mkdir($dir, 0755, true);

class PDF2 extends FPDF {
    function Header() {
        if (file_exists('../../gambar/logo_BPK.png')) {
            $this->Image('../../gambar/logo_BPK.png', 95, 10, 20);
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

function tanggalIndo2($tgl) {
    $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    return date('d', strtotime($tgl)).' '.$bulan[date('m', strtotime($tgl))].' '.date('Y', strtotime($tgl));
}

$pdf = new PDF2();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,'BERITA ACARA PINJAM PAKAI KENDARAAN',0,1,'C');
$pdf->Ln(1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor: ".$nomor_ba,0,1,'C');
$pdf->Ln(8);

$pdf->MultiCell(0,6,"Pada tanggal ".tanggalIndo2(date('Y-m-d')).", yang bertanda tangan di bawah ini:",0,'J');
$pdf->Ln(3);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK PERTAMA',0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$penanggung_jawab,0,1);
$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$lo_nip,0,1);
$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Penanggung Jawab',0,1);

$pdf->Ln(5);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK KEDUA',0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$peminjam_name,0,1);
$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$peminjam_nip,0,1);
$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->Ln(5);
$pdf->MultiCell(0,6,'Telah dilakukan serah terima kendaraan sebagai berikut:',0,'J');

$pdf->MultiCell(0,6,
"Kode Peminjaman  : $nomor_ba\n".
"Nama Kendaraan   : ".$data['nama_kendaraan']."\n".
"No. Polisi       : ".($data['no_polisi'] ?? '-')."\n".
"Tanggal Pinjam   : ".tanggalIndo2($data['tanggal_pinjam'])."\n".
"Tanggal Kembali  : ".tanggalIndo2($data['tanggal_kembali']),
0,'J');

$pdf->Ln(8);
$pdf->MultiCell(0,6,
"Kendaraan tersebut diserahkan dalam keadaan baik. Pihak Kedua bertanggung jawab apabila terjadi kerusakan atau kehilangan.\n\nDemikian berita acara ini dibuat untuk dipergunakan sebagaimana mestinya.",
0,'J');


$pdf->Ln(10);
$col = 90;

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(30);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6, ($data['lo'] ?? '-'),0,0,'C');
$pdf->Cell($col,6, ($data['nama_user'] ?? '-'),0,1,'C');

$pdf->Ln(4);
$pdf->SetFont('Arial','',10);
$pdf->Cell($col,5,'NIP. -',0,0,'C');
$pdf->Cell($col,5,'NIP. -',0,1,'C');


$pdf->Ln(8);
$pdf->SetFont('Arial','I',9);
$pdf->Cell(0,6,'Dokumen ini dibuat otomatis oleh sistem peminjaman aset instansi.',0,1,'C');


$filename = "Berita_Acara_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $nomor_ba) . ".pdf";
$filepath = $dir . '/' . $filename;

$pdf->Output('F', $filepath);

$con->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$link = 'http://localhost/PinjamRuanganKendaraan/pdf-kembali/'.$yearDir.'/'.$filename;
$msg = 'Permintaan peminjaman Anda disetujui. Klik untuk melihat/cetak Berita Acara: '.$link;

$stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
$stmtn->bind_param("is", $data['peminjam_id'], $msg);
$stmtn->execute();

header('Location: admin_kendaraan.php');
exit();
