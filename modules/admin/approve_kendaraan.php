<?php
session_start();
include_once("../../koneksi.php");
require('../../fpdf/fpdf.php');

// === CEK ROLE ADMIN ===
$adminRoles = ['admin_kendaraan', 'super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

// === LOAD DATA PEMINJAMAN ===
$stmt = $con->prepare("SELECT p.*, 
        u.nama AS peminjam, u.nip AS peminjam_nip, u.id AS peminjam_id, 
        k.nama_kendaraan, k.no_polisi, k.id AS id_item
    FROM peminjaman p 
    JOIN user u ON p.id_user = u.id 
    JOIN kendaraan k ON p.id_item = k.id 
    WHERE p.id = ? AND p.jenis='kendaraan' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header('Location: admin_kendaraan.php');
    exit();
}

// === DATA PEMINJAM & LO ===
$penanggung_jawab = $data['lo'] ?? 'Pengadministrasi Umum';
$peminjam_name = $data['peminjam'] ?? '-';
$peminjam_nip = $data['peminjam_nip'] ?? '-';
$peminjam_id = $data['peminjam_id'] ?? 0;

$lo_nip = '-';
if (!empty($penanggung_jawab)) {
    $stlo = $con->prepare("SELECT nip FROM user WHERE nama = ? LIMIT 1");
    $stlo->bind_param("s", $penanggung_jawab);
    $stlo->execute();
    $reslo = $stlo->get_result()->fetch_assoc();
    if (!empty($reslo['nip'])) $lo_nip = $reslo['nip'];
    $stlo->close();
}

// === JIKA DITOLAK ADMIN ===
if ($action === 'reject') {

    $upd = $con->prepare("UPDATE peminjaman SET status='rejected' WHERE id = ?");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();

    // table notif (buat jika belum ada)
    $con->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $msg = 'Permintaan peminjaman kendaraan Anda ditolak oleh admin.';
    $stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
    $stmtn->bind_param("is", $peminjam_id, $msg);
    $stmtn->execute();
    $stmtn->close();

    header('Location: admin_kendaraan.php');
    exit();
}

// === GENERATE NOMOR BA ===
function generateBA($con) {
    $month = date('m');
    $year  = date('Y');

    // Cari kode_peminjaman terakhir yang berformat .../MM/YYYY
    $like = '%' . '/' . $month . '/' . $year;
    $sql = "SELECT kode_peminjaman FROM peminjaman WHERE kode_peminjaman LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $counter = 1;

    if ($res && $res->num_rows > 0) {
        $lastBA = $res->fetch_assoc()['kode_peminjaman'];
        // Ekspektasi format: NN/BA/XVIII.YOG.1.4/MM/YYYY
        $parts = explode('/', $lastBA);
        if (count($parts) >= 1) {
            $numPart = preg_replace('/[^0-9]/', '', $parts[0]);
            $numInt = intval($numPart);
            $counter = $numInt + 1;
        }
    }
    $stmt->close();

    return sprintf("%02d/BA/XVIII.YOG.1.4/%02d/%d", $counter, intval($month), intval($year));
}

$nomor_ba = generateBA($con);

// === Update status + kode BA dan kendaraan ===
// Gunakan transaksi agar atomic
$con->begin_transaction();

try {
    $upd1 = $con->prepare("UPDATE peminjaman SET status = 'dipinjam', kode_peminjaman = ? WHERE id = ?");
    $upd1->bind_param("si", $nomor_ba, $id);
    $upd1->execute();
    $upd1->close();

    $upd2 = $con->prepare("UPDATE kendaraan SET status = 'dipinjam' WHERE id = ?");
    $upd2->bind_param("i", $data['id_item']);
    $upd2->execute();
    $upd2->close();

    $con->commit();
} catch (Exception $e) {
    $con->rollback();
    // Jika gagal update, redirect balik
    header('Location: admin_kendaraan.php?error=update_failed');
    exit();
}

// === BUAT FOLDER TAHUN ===
$yearDir = date('Y');
$dir = __DIR__ . '/../../pdf-kembali/' . $yearDir;

if (!is_dir($dir)) mkdir($dir, 0755, true);

// === CLASS FPDF HEADER ===
class PDF2 extends FPDF {
    function Header() {
        if (file_exists('../../gambar/logo_BPK.png')) {
            $this->Image('../../gambar/logo_BPK.png', 95, 10, 20);
        }
        $this->Ln(22);

        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'BADAN PEMERIKSA KEUANGAN',0,1,'C');
        $this->Cell(0,6,'PERWAKILAN PROVINSI DAERAH ISTIMEWA YOGYAKARTA',0,1,'C');

        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Jl. HOS Cokroaminoto No. 52 Yogyakarta 55244 Telepon 0274-563635',0,1,'C');
        $this->Ln(1);

        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY() + 1.5, 200, $this->GetY() + 1.5);

        $this->Ln(1);
    }
}

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
    return $daftar[$hariInggris] ?? $hariInggris;
}

function terbilang($angka) {
    $angka = abs(intval($angka));
    $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

    if ($angka < 12) return $baca[$angka];
    elseif ($angka < 20) return terbilang($angka - 10) . " Belas";
    elseif ($angka < 100) return terbilang(intval($angka/10)) . " Puluh " . terbilang($angka % 10);
    elseif ($angka < 200) return "Seratus " . terbilang($angka - 100);
    elseif ($angka < 1000) return terbilang(intval($angka/100)) . " Ratus " . terbilang($angka % 100);
    elseif ($angka < 2000) return "Seribu " . terbilang($angka - 1000);
    elseif ($angka < 1000000) return terbilang(intval($angka/1000)) . " Ribu " . terbilang($angka % 1000);

    return strval($angka);
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

    return terbilang($d) . " bulan " . ($bulan[$m] ?? $m) . " tahun " . terbilang($y);
}

// ======================================================
//               DETEKSI RODA 2 / 3 / 4
// ======================================================
$nama = strtolower($data['nama_kendaraan']);
$jenisRoda = "KENDARAAN RODA EMPAT"; // default

if (strpos($nama, 'sepeda motor') !== false || strpos($nama, 'motor') !== false) {
    $jenisRoda = "KENDARAAN RODA DUA";
} elseif (strpos($nama, 'roda tiga') !== false) {
    $jenisRoda = "KENDARAAN RODA TIGA";
}

// ======================================================

$pdf = new PDF2();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// === JUDUL ===
$pdf->Ln(2);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,7,'BERITA ACARA PINJAM PAKAI',0,1,'C');
$pdf->Cell(0,7,$jenisRoda,0,1,'C');

$pdf->Ln(2);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor :  $nomor_ba",0,1,'C');

$pdf->Ln(2);

// === PARAGRAF PEMBUKA BARU ===
$tglToday = date('Y-m-d');
$hari = namaHariIndo($tglToday);
$tanggalPanjang = tanggalLengkapIndo($tglToday);
$tanggalAngka = date('d-m-Y', strtotime($tglToday));

$paragraf1 = "Pada hari {$hari} tanggal {$tanggalPanjang} ({$tanggalAngka}), yang bertanda tangan di bawah ini:";
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,$paragraf1,0,'J');
$pdf->Ln(1);

// === DATA PIHAK PERTAMA ===
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

// === PIHAK KEDUA ===
$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$peminjam_name,0,1);

$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$peminjam_nip,0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Write(6, 'Selanjutnya disebut sebagai ');

$pdf->SetFont('Arial','B',11);
$pdf->Write(6, 'Pihak Kedua');

$pdf->Ln(8);

// === DETAIL KENDARAAN ===
$pdf->SetFont('Arial','',11);
$detailKendaraan =
"Telah dilakukan serah terima Barang Milik Negara (BMN) berupa ".$data['nama_kendaraan']." ".
"dengan Nomor Polisi ".$data['no_polisi']." dari pihak Pertama kepada pihak Kedua dalam keadaan baik yang untuk selanjutnya BMN tersebut akan dipinjam oleh Pihak Kedua selama melaksanakan tugas di BPK Perwakilan Provinsi Daerah Istimewa Yogyakarta. Selanjutnya Pihak Kedua akan menjaga dan memelihara barang tersebut serta bertanggungjawab apabila terjadi kehilangan atau kerusakan akibat kelalaian Pihak Kedua.";

$pdf->MultiCell(0, 6, $detailKendaraan, 0, 'J');
$pdf->Ln(3);

// === PENUTUP PARAGRAF ===
$pdf->MultiCell(0,6,
"Demikian berita acara ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.",
0,'J');

$pdf->Ln(4);

// === TANDA TANGAN ===
$col = 90;
$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(24);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$penanggung_jawab,0,0,'C');
$pdf->Cell($col,6,$peminjam_name,0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'NIP. '.$lo_nip,0,0,'C');
$pdf->Cell($col,6,'NIP. '.$peminjam_nip,0,1,'C');

$pdf->Ln(4);

// === MENGETAHUI ===
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'Mengetahui,',0,1,'C');
$pdf->Ln(23);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell(0,6,'Martin Ricardo Ferdinandus',0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'NIP. 196704262000031001',0,1,'C');

// === SIMPAN PDF ===
$filename = "Berita_Acara_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $nomor_ba) . ".pdf";
$filepath = $dir . '/' . $filename;

$pdf->Output('F', $filepath);

// === NOTIFIKASI ===
// buat table notif jika belum ada
$con->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$link = 'http://localhost/PinjamRuanganKendaraan/pdf-kembali/'.$yearDir.'/'.$filename;
$msg = 'Permintaan peminjaman Anda disetujui. Klik untuk melihat/cetak Berita Acara: '.$link;

$stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
$stmtn->bind_param("is", $peminjam_id, $msg);
$stmtn->execute();
$stmtn->close();

header('Location: admin_kendaraan.php');
exit();
?>
