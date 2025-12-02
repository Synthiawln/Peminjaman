<?php
session_start();
include_once("../../koneksi.php");
require('../../fpdf/fpdf.php');

// === CEK ROLE ADMIN ===
$adminRoles = ['admin_ruangan', 'super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

// === AMBIL DATA ===
$stmt = $con->prepare("
    SELECT p.*, 
           u.nama AS peminjam, u.nip AS peminjam_nip, u.id AS peminjam_id,
           r.nama_ruangan, r.lokasi,
           p.lo
    FROM peminjaman p
    JOIN user u ON p.id_user = u.id
    JOIN ruangan r ON p.id_item = r.id
    WHERE p.id = ? AND p.jenis='ruangan'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header('Location: admin_ruangan.php');
    exit();
}

// === REJECT ===
if ($action === 'reject') {
    $con->query("UPDATE peminjaman SET status='rejected' WHERE id=$id");
    $con->query("CREATE TABLE IF NOT EXISTS notifications(
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $msg = "Permintaan peminjaman ruangan Anda ditolak oleh admin.";
    $stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
    $stmtn->bind_param("is", $data['peminjam_id'], $msg);
    $stmtn->execute();

    header('Location: admin_ruangan.php');
    exit();
}

// === NOMOR BA RUANGAN ===
function generateBA_Ruangan($con) {
    $bulan = date("m");
    $tahun = date("Y");

    // cek BA terakhir khusus ruangan
    $sql = "
        SELECT kode_peminjaman 
        FROM peminjaman 
        WHERE jenis='ruangan' AND kode_peminjaman IS NOT NULL
        ORDER BY id DESC 
        LIMIT 1
    ";
    $q = $con->query($sql);
    $row = $q->fetch_assoc();

    if ($row && preg_match('/^(\d+)\//', $row['kode_peminjaman'], $m)) {
        $next = intval($m[1]) + 1;
    } else {
        $next = 1;
    }

    $no = str_pad($next, 2, '0', STR_PAD_LEFT);

    return "$no/BA-RUANG/XVIII.YOG.1.4/$bulan/$tahun";
}

$nomor_ba = generateBA_Ruangan($con);

// === UPDATE STATUS ===
$con->query("UPDATE peminjaman SET status='dipinjam', kode_peminjaman='$nomor_ba' WHERE id=$id");
$con->query("UPDATE ruangan SET status='dipinjam' WHERE id=".$data['id_item']);

// === HEADER PDF ===
class PDF_Ruangan extends FPDF {
    function Header() {

        if (file_exists('../../gambar/logo_BPK.png')) {
            $this->Image('../../gambar/logo_BPK.png', 95, 10, 20);
        }

        $this->Ln(22);
        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'BADAN PEMERIKSA KEUANGAN',0,1,'C');
        $this->Cell(0,6,'PERWAKILAN PROVINSI DAERAH ISTIMEWA YOGYAKARTA',0,1,'C');

        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Jl. HOS Cokroaminoto No. 52 Yogyakarta 55244 Telp. (0274) 563635',0,1,'C');
        $this->Ln(1);

        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY() + 1.5, 200, $this->GetY() + 1.5);

        $this->Ln(2);
    }
}

// === FUNGSI TANGGAL ===
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


// === PDF ===
$pdf = new PDF_Ruangan();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// ===== JUDUL =====
$pdf->Ln(2);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,7,'BERITA ACARA PINJAM PAKAI',0,1,'C');
$pdf->Cell(0,7,'RUANGAN',0,1,'C');

$pdf->Ln(2);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor : $nomor_ba",0,1,'C');
$pdf->Ln(2);

// === PARAGRAF PEMBUKA ===
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

// ===== ISI =====
$pdf->SetFont('Arial','',11);

$detailRuangan =
"Telah dilakukan serah terima Barang Milik Negara (BMN) berupa Ruangan ".$data['nama_ruangan']." ".
"yang berlokasi di ".$data['lokasi']." dari pihak Pertama kepada pihak Kedua dalam keadaan baik yang untuk selanjutnya ruangan tersebut akan digunakan oleh Pihak Kedua selama melaksanakan kegiatan di BPK Perwakilan Provinsi Daerah Istimewa Yogyakarta. Selanjutnya Pihak Kedua bertanggung jawab untuk menjaga, menggunakan, dan mengembalikan ruangan dalam kondisi baik serta bertanggungjawab apabila terjadi kerusakan akibat kelalaiannya.";

$pdf->MultiCell(0,6,$detailRuangan,0,'J');
$pdf->Ln(3);

$pdf->MultiCell(0,6,
"Demikian berita acara ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.",
0,'J');

$pdf->Ln(4);

// ===== TANDA TANGAN =====
$col = 90;
$pdf->SetFont('Arial','',11);

$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(23);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$penanggung_jawab,0,0,'C');
$pdf->Cell($col,6,$data['peminjam'],0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'NIP. '.$lo_nip,0,0,'C');
$pdf->Cell($col,6,'NIP. '.$data['peminjam_nip'],0,1,'C');

$pdf->Ln(4);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'Mengetahui,',0,1,'C');
$pdf->Ln(23);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell(0,6,'Martin Ricardo Ferdinandus',0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'NIP. 196704262000031001',0,1,'C');

// === SIMPAN PDF ===
$yearDir = date('Y');
$dir = __DIR__ . '/../../pdf-kembali/'.$yearDir;

if (!is_dir($dir)) mkdir($dir,0755,true);

$filename = "Berita_Acara_Ruangan_".preg_replace('/[^A-Za-z0-9._-]/','_',$nomor_ba).".pdf";
$filepath = $dir.'/'.$filename;

$pdf->Output('F',$filepath);

// === NOTIFIKASI ===
$con->query("CREATE TABLE IF NOT EXISTS notifications(
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$link = "http://localhost/PinjamRuanganKendaraan/pdf-kembali/$yearDir/$filename";

$msg = "Permintaan peminjaman ruangan Anda disetujui. Klik untuk melihat/cetak Berita Acara: $link";

$stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?,?)");
$stmtn->bind_param("is",$data['peminjam_id'],$msg);
$stmtn->execute();

header("Location: admin_ruangan.php");
exit();

?>
