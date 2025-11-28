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
$action = $_GET['action'] ?? 'approve';

// Ambil data peminjaman
$stmt = $con->prepare("
    SELECT p.*, u.nama AS peminjam, u.nip AS peminjam_nip, u.id AS peminjam_id,
           k.nama_kendaraan, k.no_polisi
    FROM peminjaman p
    JOIN user u ON p.id_user = u.id
    JOIN kendaraan k ON p.id_item = k.id
    WHERE p.id = ? AND p.jenis='kendaraan' AND p.status=''
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header('Location: admin_kendaraan.php');
    exit();
}

// Jika admin menolak pengembalian
if ($action === 'reject') {

    // Update status peminjaman menjadi 'dipinjam'
    $stmt_rej = $con->prepare("UPDATE peminjaman SET status='dipinjam' WHERE id=?");
    $stmt_rej->bind_param("i", $id);
    $stmt_rej->execute();
    
    // Ambil id_user dari tabel peminjaman berdasarkan id peminjaman
    $stmt_get_user = $con->prepare("SELECT id_user FROM peminjaman WHERE id=?");
    $stmt_get_user->bind_param("i", $id);
    $stmt_get_user->execute();
    $result_user = $stmt_get_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $id_user = $user_data['id_user'] ?? null;
    
    if ($id_user === null) {
        die("Error: User ID tidak ditemukan untuk peminjaman ini.");
    }
    
    // Buat pesan dinamis berdasarkan keterangan admin, sehingga user bisa melihat komentar
    $msgrej = 'Permintaan pengembalian kendaraan Anda ditolak oleh admin.' ;
    
    $stmtnr = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
    $stmtnr->bind_param("is", $id_user, $msgrej);
    $stmtnr->execute();
    
    header('Location: admin_kendaraan.php');
    exit();
}

// Ambil LO
$penanggung_jawab = $data['lo'] ?? 'PENANGGUNG JAWAB';
$lo_nip = '-';

if (!empty($penanggung_jawab)) {
    $stlo = $con->prepare("SELECT nip FROM user WHERE nama = ? LIMIT 1");
    $stlo->bind_param("s", $penanggung_jawab);
    $stlo->execute();
    $reslo = $stlo->get_result()->fetch_assoc();
    if (!empty($reslo['nip'])) $lo_nip = $reslo['nip'];
    $stlo->close();
}

// Generate Nomor BA Pengembalian
function generateUniqueBA($con) {
    do {
        $candidate = "BA.Kembali." . date("Y") . "/TI/" . str_pad(rand(1,9999), 4, "0", STR_PAD_LEFT);
        $r = $con->query("SELECT COUNT(*) AS cnt FROM berita_acara WHERE nomor_ba = '".$con->real_escape_string($candidate)."'");
        $cnt = (int)$r->fetch_assoc()['cnt'];
    } while ($cnt > 0);
    return $candidate;
}

$nomor_ba = generateUniqueBA($con);

// Update status menjadi dikembalikan
$stmt2 = $con->prepare("
    UPDATE peminjaman 
    SET status='dikembalikan', tanggal_kembali_aktual = NOW()
    WHERE id=?
");
$stmt2->bind_param("i", $id);
$stmt2->execute();

// Ubah kendaraan jadi tersedia
$con->query("UPDATE kendaraan SET status='tersedia' WHERE id=".$data['id_item']);

// Buat folder PDF
$yearDir = date('Y');
$dir = __DIR__ . '/../../pdf-kembali/' . $yearDir;
if (!is_dir($dir)) mkdir($dir, 0755, true);

class PDF2 extends FPDF {
    function Header() {
        if (file_exists('../../gambar/logo_BPK.png')) {
            $this->Image('../../gambar/logo_BPK.png', 95, 10, 20);
        }
        $this->Ln(23);
        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'BERITA ACARA PENGEMBALIAN KENDARAAN',0,1,'C');
        $this->Ln(2);
    }
}

function tanggalIndo($tgl) {
    $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    return date('d', strtotime($tgl)).' '.$bulan[date('m', strtotime($tgl))].' '.date('Y', strtotime($tgl));
}

// Mulai generate PDF
$pdf = new PDF2();
$pdf->AddPage();

$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,"Nomor: $nomor_ba",0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,"Pada hari ini ".tanggalIndo(date('Y-m-d'))." telah dilakukan pengembalian kendaraan dari:",0,'J');
$pdf->Ln(3);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK KEDUA (Peminjam):',0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(50,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$data['peminjam'],0,1);
$pdf->Cell(50,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$data['peminjam_nip'],0,1);

$pdf->Ln(3);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK PERTAMA (Penanggung Jawab):',0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(50,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$penanggung_jawab,0,1);
$pdf->Cell(50,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$lo_nip,0,1);

$pdf->Ln(8);
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,
"Telah mengembalikan kendaraan berikut:\n\n".
"Nama Kendaraan : ".$data['nama_kendaraan']."\n".
"No. Polisi     : ".($data['no_polisi'] ?? '-')."\n".
"Tanggal Pinjam : ".tanggalIndo($data['tanggal_pinjam'])."\n".
"Tanggal Kembali (Aktual): ".tanggalIndo(date('Y-m-d'))."\n",
0,'J');

$pdf->Ln(5);
$pdf->MultiCell(0,6,
"Kendaraan telah diterima kembali dalam kondisi baik. Jika ditemukan kerusakan, maka akan ditindaklanjuti sesuai ketentuan.",
0,'J');

$pdf->Ln(15);

$col = 90;
$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');
$pdf->Ln(25);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$penanggung_jawab,0,0,'C');
$pdf->Cell($col,6,$data['peminjam'],0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell($col,6,'NIP. '.$lo_nip,0,0,'C');
$pdf->Cell($col,6,'NIP. '.$data['peminjam_nip'],0,1,'C');

// Simpan PDF
$filename = "BA_Kembali_".$nomor_ba.".pdf";
$filepath = $dir.'/'.$filename;

$pdf->Output('F', $filepath);

// Simpan notifikasi
$con->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
ENGINE=InnoDB");

$link = 'http://localhost/PinjamRuanganKendaraan/pdf-kembali/'.$yearDir.'/'.$filename;
$msg = 'Pengembalian kendaraan Anda telah disetujui. Lihat Berita Acara: '.$link;

$stmtn = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
$stmtn->bind_param("is", $data['peminjam_id'], $msg);
$stmtn->execute();

header('Location: admin_kendaraan.php');
exit();
