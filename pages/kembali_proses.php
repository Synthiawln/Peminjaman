<?php
// kembali_proses.php
// Full proses: terima form pengembalian -> update DB -> generate nomor BA (format B) -> generate PDF resmi -> simpan -> redirect ke cetak_BA.php
// Pastikan file ini ditempatkan di folder pages atau sesuai struktur project kamu.

// konfigurasi koneksi
session_start();
include '../koneksi.php';
require('../fpdf/fpdf.php');

// ---------------------
// VALIDASI INPUT
// ---------------------
if (!isset($_POST['id_peminjaman']) || !isset($_POST['tgl_kembali_aktual'])) {
    die("Data tidak lengkap.");
}

$id_peminjaman = intval($_POST['id_peminjaman']);
$tgl_kembali_aktual = $_POST['tgl_kembali_aktual']; // expected format: YYYY-MM-DD

// ---------------------
// AMBIL DATA PEMINJAMAN
// ---------------------
$stmt = $con->prepare("
    SELECT p.*, u.nama AS nama_user 
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id_peminjaman);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!$data) die("Data peminjaman tidak ditemukan.");

// ---------------------
// UPDATE STATUS PEMINJAMAN
// ---------------------
$update_peminjaman = $con->prepare("
    UPDATE peminjaman 
    SET status='dikembalikan', tanggal_kembali_aktual=? 
    WHERE id=?
");
$update_peminjaman->bind_param("si", $tgl_kembali_aktual, $id_peminjaman);
$ok_update = $update_peminjaman->execute();
if ($ok_update === false) {
    die("Gagal mengupdate status peminjaman.");
}

// ---------------------
// TENTUKAN TABEL ITEM (ruangan/kendaraan)
// ---------------------
$jenis = strtolower(trim($data['jenis']));
if ($jenis == 'ruangan' || $jenis === '2') {
    $tabel_item = 'ruangan';
} elseif ($jenis == 'kendaraan' || $jenis === '1') {
    $tabel_item = 'kendaraan';
} else {
    // fallback: jika isi kolom 'jenis' bukan kata, coba deteksi numeric
    $tabel_item = 'ruangan';
}

// ---------------------
// UPDATE STATUS ITEM MENJADI TERSEDIA
// ---------------------
$update_item = $con->prepare("UPDATE $tabel_item SET status='tersedia' WHERE id=?");
$update_item->bind_param("i", $data['id_item']);
$update_item->execute();

// ---------------------
// GENERATE NOMOR BA (Format B: contoh 038/BA-PENGEMBALIAN/TI/2025)
// - kita buat nomor urut berdasarkan jumlah BA pengembalian pada tahun ini + 1
// ---------------------
$year = date("Y", strtotime($tgl_kembali_aktual));
$prefix_instansi = "BA-PENGEMBALIAN";
$subfolder_kode = "TI"; // seperti permintaanmu, gunakan subfolder TI

// hitung urut tahun ini (berdasarkan record peminjaman yang sudah dikembalikan pada tahun ini)
$countStmt = $con->prepare("
    SELECT COUNT(*) as cnt FROM peminjaman 
    WHERE status = 'dikembalikan' AND YEAR(tanggal_kembali_aktual) = ?
");
$countStmt->bind_param("i", $year);
$countStmt->execute();
$cntRes = $countStmt->get_result()->fetch_assoc();
$urut = intval($cntRes['cnt']) + 1;
$urut_padded = str_pad($urut, 3, "0", STR_PAD_LEFT);

// nomor final
$nomor_ba_kembali = "{$urut_padded}/{$prefix_instansi}/{$subfolder_kode}/{$year}";

// optional: simpan nomor BA ke tabel peminjaman (kolom kode_ba_kembali) jika ingin
if (isset($con)) {
    // cek apakah kolom ada, jika tidak silakan tambahkan di DB. Kita lakukan UPDATE ke kolom kode_ba_kembali jika ada.
    $checkCol = $con->query("SHOW COLUMNS FROM peminjaman LIKE 'kode_ba_kembali'");
    if ($checkCol && $checkCol->num_rows > 0) {
        $upd = $con->prepare("UPDATE peminjaman SET kode_ba_kembali = ? WHERE id = ?");
        $upd->bind_param("si", $nomor_ba_kembali, $id_peminjaman);
        $upd->execute();
    }
}

// ---------------------
// FOLDER & NAMA FILE PDF
// ---------------------
// Tentukan nama project (folder di htdocs) secara dinamis
$projectDir = basename(realpath(__DIR__ . '/..')); // ex: PinjamRuanganKendaraan

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$base_dir = $docRoot . '/' . $projectDir . '/pdf-kembali/' . $year . '/' . $subfolder_kode . '/';

// Buat folder jika belum ada
if (!is_dir($base_dir)) {
    if (!mkdir($base_dir, 0777, true) && !is_dir($base_dir)) {
        die("Gagal membuat folder PDF: $base_dir");
    }
}

// Nama file aman
$safe_name = preg_replace('/[\/\\\\\s]+/', '_', $nomor_ba_kembali) . '.pdf';
$pdf_path = $base_dir . $safe_name;

// ---------------------
// GENERATE PDF (LAYOUT RESMI INSTANSI - SAMA DENGAN FORMAT BA PEMINJAMAN)
// ---------------------
class PDF_BA extends FPDF {
    public $logoPathOverride = null;
    function Header() {
        // Jika tersedia logo di project, gunakan ../gambar/logo_BPK.png
        // Jika tidak, coba path lokal dari container (developer-provided image)
        $logo = $this->logoPathOverride && file_exists($this->logoPathOverride) ? $this->logoPathOverride : '../gambar/logo_BPK.png';
        // Developer-provided image path (in container) — included as fallback reference (may not be accessible by web server)
        $dev_image = '/mnt/data/855f7296-8f58-44b4-a1e4-122bbae98dbe.png';
        if (!file_exists($logo) && file_exists($dev_image)) {
            $logo = $dev_image;
        }

        if (file_exists($logo)) {
            // center logo
            $this->Image($logo, 95, 10, 20);
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

// create PDF
$pdf = new PDF_BA();
$pdf->logoPathOverride = '../gambar/logo_BPK.png'; // override jika perlu
$pdf->AddPage();

// Judul
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,'BERITA ACARA PENGEMBALIAN ' . strtoupper($data['jenis']),0,1,'C');
$pdf->Ln(1);

// nomor BA (tampilkan di tengah)
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Nomor: ".$nomor_ba_kembali,0,1,'C');
$pdf->Ln(8);

// Tanggal (format dd Month YYYY — contoh: 21 Februari 2025)
function tanggal_indo_format($tgl){
    $bulan = [
        "01"=>"Januari","02"=>"Februari","03"=>"Maret","04"=>"April","05"=>"Mei","06"=>"Juni",
        "07"=>"Juli","08"=>"Agustus","09"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"
    ];
    $d = date('d', strtotime($tgl));
    $m = date('m', strtotime($tgl));
    $y = date('Y', strtotime($tgl));
    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

$tanggal_display = tanggal_indo_format($tgl_kembali_aktual);

$pdf->MultiCell(0,6,
"Pada tanggal $tanggal_display, yang bertanda tangan di bawah ini:",
0,'J'
);
$pdf->Ln(3);

// PIHAK PERTAMA
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK PERTAMA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': ' . ($data['lo'] ?? '-'),0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Penanggung Jawab',0,1);

$pdf->Ln(5);

// PIHAK KEDUA
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,'PIHAK KEDUA',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(45,6,'Nama',0,0);
$pdf->Cell(0,6,': ' . ($data['nama_user'] ?? '-'),0,1);

$pdf->Cell(45,6,'Jabatan',0,0);
$pdf->Cell(0,6,': Pegawai',0,1);

$pdf->Ln(5);

// RINCIAN PEMINJAMAN (opsional: tampilkan kode peminjaman, tgl pinjam, tgl kembali awal)
$pdf->SetFont('Arial','',11);
$rincian = "Kode Peminjaman  : " . ($data['kode_peminjaman'] ?? '-') . "\n" .
           "Tanggal Pinjam   : " . (!empty($data['tanggal_pinjam']) ? tanggal_indo_format($data['tanggal_pinjam']) : '-') . "\n" .
           "Tanggal Kembali (Jadwal)  : " . (!empty($data['tanggal_kembali']) ? tanggal_indo_format($data['tanggal_kembali']) : '-') . "\n" .
           "Tanggal Kembali (Aktual)  : " . tanggal_indo_format($tgl_kembali_aktual);
$pdf->MultiCell(0,6, $rincian, 0, 'J');

$pdf->Ln(6);

// ISI POKOK – SUDAH DIKEMBALIKAN
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,6,
"Bahwa Pihak Kedua telah mengembalikan " . strtolower($data['jenis']) .
" dengan kode peminjaman " . ($data['kode_peminjaman'] ?? '-') . " dalam keadaan baik dan lengkap. " .
"Pihak Pertama menyatakan bahwa barang/ruangan tersebut telah diterima dan proses peminjaman dinyatakan selesai.",
0,'J'
);

$pdf->Ln(8);

$pdf->MultiCell(0,6,
"Demikian berita acara pengembalian ini dibuat dengan sebenarnya untuk digunakan sebagaimana mestinya.",
0,'J'
);

// TANDA TANGAN
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

// FOOTER NOTE (opsional)
$pdf->Ln(8);
$pdf->SetFont('Arial','I',9);
$pdf->Cell(0,6,'Dokumen ini dibuat otomatis oleh sistem peminjaman aset instansi.',0,1,'C');

// SIMPAN FILE PDF KE FILESYSTEM
$pdf->Output("F", $pdf_path);

// cek file berhasil dibuat
if (!file_exists($pdf_path)) {
    die("Gagal membuat file PDF pada: $pdf_path");
}

// ---------------------
// BANGUN PUBLIC URL (relatif terhadap document root)
// ---------------------
$public_url = '/' . $projectDir . '/pdf-kembali/' . $year . '/' . $subfolder_kode . '/' . rawurlencode($safe_name);

// REDIRECT KE halaman cetak_BA.php (atau langsung buka file) — kita redirect ke cetak_BA.php dengan parameter file
header("Location: cetak_BA.php?file=" . urlencode($public_url) . "&nomor_ba=" . urlencode($nomor_ba_kembali));
exit;
?>
