<?php
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');


if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];

$stmt = $con->prepare("SELECT id, nama, nip, role FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$id_user = $user['id'];
$nama_user = $user['nama'];
$nip_user  = $user['nip'] ?? '-';


$id_item          = $_POST['id_item'];
$jenis            = $_POST['jenis'];
$tgl_pinjam       = $_POST['tgl_pinjam'];
$tgl_kembali      = $_POST['tgl_kembali'];

// Jika jenis kendaraan, otomatis set LO sebagai admin_kendaraan (nama pertama yang ditemukan)
$penanggung_jawab = '';
$admin_to_notify = null;
if ($jenis === 'kendaraan') {
    $admin = $con->query("SELECT id, nama FROM user WHERE role = 'admin_kendaraan' ORDER BY id ASC LIMIT 1")->fetch_assoc();
    if ($admin) {
        $penanggung_jawab = $admin['nama'];
        $admin_to_notify = $admin['id'];
    } else {
        // fallback jika tidak ada admin_kendaraan
        $penanggung_jawab = 'ADMIN KENDARAAN';
        $admin_to_notify = null;
    }
} elseif ($jenis === 'ruangan') {
    // untuk ruangan, LO otomatis diisi dengan admin_ruangan (nama pertama yang ditemukan)
    $adminR = $con->query("SELECT id, nama FROM user WHERE role = 'admin_ruangan' ORDER BY id ASC LIMIT 1")->fetch_assoc();
    if ($adminR) {
        $penanggung_jawab = $adminR['nama'];
        $admin_to_notify = $adminR['id'];
    } else {
        // fallback jika tidak ada admin_ruangan
        $penanggung_jawab = 'ADMIN RUANGAN';
        $admin_to_notify = null;
    }
} else {
    // default: gunakan input LO
    $penanggung_jawab = $_POST['penanggung_jawab'] ?? '-';
}


$bulanIndo = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
    '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];

function tanggalIndo($tgl, $bulanIndo) {
    $d = date('d', strtotime($tgl));
    $m = date('m', strtotime($tgl));
    $y = date('Y', strtotime($tgl));
    return "$d {$bulanIndo[$m]} $y";
}

// Helper: generate a BA number that's unique in `peminjaman.kode_peminjaman`
function generateUniqueBA($con) {
    do {
        $candidate = "BA." . date("Y") . "/TI/" . str_pad(rand(1,200), 3, "0", STR_PAD_LEFT);
        $stmtc = $con->prepare("SELECT COUNT(*) as cnt FROM peminjaman WHERE kode_peminjaman = ?");
        $stmtc->bind_param("s", $candidate);
        $stmtc->execute();
        $res = $stmtc->get_result()->fetch_assoc();
        $stmtc->close();
    } while ($res && $res['cnt'] > 0);
    return $candidate;
}


// buat nomor BA nanti saat admin menyetujui; jika jenis kendaraan, buat status 'pending'
$nomor_ba = null;

$status_to_insert = in_array($jenis, ['kendaraan', 'ruangan']) ? 'pending' : 'dipinjam';

// pastikan kolom status mendukung 'pending' (jika enum lama, coba alter table sekali)
try {
    $con->query("ALTER TABLE peminjaman CHANGE COLUMN status status ENUM('pending','approved','rejected','dipinjam','dikembalikan') DEFAULT 'pending'");
} catch (Exception $e) {
    // ignore jika gagal (mungkin sudah diubah)
}

$stmt_in = $con->prepare(
    "INSERT INTO peminjaman
    (kode_peminjaman, id_user, jenis, id_item, tanggal_pinjam, tanggal_kembali, lo, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt_in->bind_param("sisissss",
    $nomor_ba,
    $id_user,
    $jenis,
    $id_item,
    $tgl_pinjam,
    $tgl_kembali,
    $penanggung_jawab,
    $status_to_insert
);

$stmt_in->execute();

// jika jenis selain kendaraan/ruangan, langsung ubah status item jadi dipinjam
if (!in_array($jenis, ['kendaraan','ruangan'])) {
    $con->query("UPDATE $jenis SET status='dipinjam' WHERE id=$id_item");
}

// Jika jenis kendaraan atau ruangan, jangan generate BA sekarang — tampilkan konfirmasi dan kirim notifikasi ke admin
if (in_array($jenis, ['kendaraan','ruangan'])) {
        // buat tabel notifikasi jika belum ada
        $con->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT,
                message TEXT,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // kirim notifikasi ke admin yang sesuai (admin_kendaraan atau admin_ruangan)
        if (!empty($admin_to_notify)) {
            $msg = ($jenis === 'kendaraan') ? 'Permintaan peminjaman kendaraan baru menunggu persetujuan.' : 'Permintaan peminjaman ruangan baru menunggu persetujuan.';
            $stmt_notif = $con->prepare("INSERT INTO notifications (id_user, message) VALUES (?, ?)");
            $stmt_notif->bind_param("is", $admin_to_notify, $msg);
            @$stmt_notif->execute();
        }

        // Tampilkan halaman konfirmasi sederhana
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Permintaan Dikirim</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="card p-4 shadow-lg">
                    <h4>Permintaan Peminjaman <?= $jenis === 'kendaraan' ? 'Kendaraan' : 'Ruangan' ?> Terkirim</h4>
                    <p>Permintaan Anda telah dikirim ke admin <?= $jenis === 'kendaraan' ? 'kendaraan' : 'ruangan' ?> (<strong><?= htmlspecialchars($penanggung_jawab) ?></strong>) untuk disetujui.</p>
                    <p>Silakan tunggu notifikasi persetujuan. Anda dapat melihat status di halaman riwayat peminjaman.</p>
                <a href="../index.php" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
}

    // Untuk jenis selain kendaraan: buat nomor BA dan simpan ke record peminjaman yang baru dibuat
    if (!in_array($jenis, ['kendaraan','ruangan'])) {
        $nomor_ba = generateUniqueBA($con);
        $last_id = $con->insert_id;
        if ($last_id) {
            $con->query("UPDATE peminjaman SET kode_peminjaman = '".$con->real_escape_string($nomor_ba)."' WHERE id = " . intval($last_id));
        }
    }


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
$pdf->SetFont('Arial','B',12);


$pdf->Cell(0,7,'BERITA ACARA PINJAM PAKAI ' . strtoupper($jenis),0,1,'C');
$pdf->Ln(1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor: ".$nomor_ba,0,1,'C');
$pdf->Ln(8);


$tanggal_surat = tanggalIndo(date('Y-m-d'), $bulanIndo);

$pdf->MultiCell(0,6,
"Pada tanggal $tanggal_surat, yang bertanda tangan di bawah ini:",
0,'J'
);
$pdf->Ln(3);


$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK PERTAMA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$penanggung_jawab,0,1);

$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': -',0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Penanggung Jawab',0,1);

$pdf->Ln(5);


$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK KEDUA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': '.$nama_user,0,1);

$pdf->Cell(45,6,'NIP',0,0);
$pdf->Cell(0,6,': '.$nip_user,0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->Ln(5);


$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,'Telah dilakukan serah terima ' . strtoupper($jenis) . ':',0,'J');

$pdf->MultiCell(0,6,
"Kode Peminjaman  : ".$nomor_ba."\n".
"Tanggal Pinjam   : ".tanggalIndo($tgl_pinjam, $bulanIndo)."\n".
"Tanggal Kembali  : ".tanggalIndo($tgl_kembali, $bulanIndo),
0,'J');

$pdf->Ln(8);


$pdf->MultiCell(0,6,
 ucfirst($jenis) . " tersebut diserahkan oleh Pihak Pertama kepada Pihak Kedua dalam keadaan baik. Pihak Kedua berkewajiban menjaga dan bertanggung jawab apabila terjadi kerusakan atau kehilangan.

Demikian berita acara ini dibuat untuk dipergunakan sebagaimana mestinya.",
0,'J'
);

$pdf->Ln(10);


$col = 90;

$pdf->SetFont('Arial','',11);
$pdf->Cell($col,6,'Pihak Pertama,',0,0,'C');
$pdf->Cell($col,6,'Pihak Kedua,',0,1,'C');

$pdf->Ln(20);

$pdf->SetFont('Arial','BU',11);
$pdf->Cell($col,6,$penanggung_jawab,0,0,'C');
$pdf->Cell($col,6,$nama_user,0,1,'C');

$pdf->Ln(2);

$pdf->SetFont('Arial','',10);
$pdf->Cell($col,5,'NIP. -',0,0,'C');
$pdf->Cell($col,5,'NIP. '.$nip_user,0,1,'C');


$pdf->Ln(8);
$pdf->SetFont('Arial','I',9);
$pdf->Cell(0,6,'Dokumen ini dibuat otomatis oleh sistem peminjaman aset instansi.',0,1,'C');


$pdf->Output("I", "Berita_Acara_".$nomor_ba.".pdf");
exit;

?>
