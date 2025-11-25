<?php
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');


if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];


$stmt = $con->prepare("SELECT id, nama, nip FROM user WHERE username = ?");
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
$penanggung_jawab = $_POST['penanggung_jawab'];


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


$nomor_ba = "BA." . date("Y") . "/TI/" . str_pad(rand(1,200), 3, "0", STR_PAD_LEFT);


$stmt_in = $con->prepare("
    INSERT INTO peminjaman 
    (kode_peminjaman, id_user, jenis, id_item, tanggal_pinjam, tanggal_kembali, lo, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'dipinjam')
");

$stmt_in->bind_param("sisssss",
    $nomor_ba,
    $id_user,
    $jenis,
    $id_item,
    $tgl_pinjam,
    $tgl_kembali,
    $penanggung_jawab
);

$stmt_in->execute();


$con->query("UPDATE $jenis SET status='dipinjam' WHERE id=$id_item");


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
