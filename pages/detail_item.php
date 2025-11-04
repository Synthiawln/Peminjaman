<?php
session_start();
require_once("../koneksi.php");

// Cek login
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Cek parameter ID
if (!isset($_GET['id'])) {
    echo "<h4>Data tidak ditemukan. ID tidak diberikan.</h4>";
    exit();
}

$id = (int)$_GET['id'];
$jenis = $_GET['jenis'] ?? ''; // bisa kosong

// Coba ambil data sesuai jenis
if ($jenis === 'ruangan') {
    $stmt = $con->prepare("SELECT *, 'ruangan' AS jenis FROM ruangan WHERE id = ?");
} elseif ($jenis === 'kendaraan') {
    $stmt = $con->prepare("SELECT *, 'kendaraan' AS jenis FROM kendaraan WHERE id = ?");
} else {
    // Jika jenis tidak ada, coba deteksi otomatis
    $stmt = $con->prepare("SELECT *, 'kendaraan' AS jenis FROM kendaraan WHERE id = ?");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<h4>Item tidak ditemukan.</h4>";
    exit();
}

$item = $res->fetch_assoc();
$jenis = $item['jenis']; // hasil deteksi otomatis

$pageTitle = "Detail Item";
include("../includes/header.php");
include("../includes/navbar.php");

// Path gambar
$file = $item['foto'] ?? '';
$fotoPath = "../" . $file; // langsung dari database

// Pastikan file ada
if (empty($file) || !file_exists($fotoPath)) {
    $fotoPath = "../uploads/no-image.png"; // default image
}

// Link kembali
$backLink = ($jenis === 'ruangan') ? 'ruangan.php' : 'kendaraan.php';
?>

<style>
    body {
        background-color: #f7f7f7ff;
    }

    .detail-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s ease;
    }

    .detail-card:hover {
        transform: scale(1.01);
    }

    .detail-img {
        width: 100%;
        height: 350px;
        object-fit: cover;
        border-bottom: 3px solid #d0b84c;
    }

    .detail-content {
        padding: 25px;
    }

    .btn-primary {
        background-color: #746616cf;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-primary:hover {
        background-color: #74652fff;
    }

    .btn-secondary {
        border-radius: 8px;
    }

    .btn-back {
        background-color: #746616cf;
        color: white;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-back:hover {
        background-color: #74652fff;
        color: #fff;
    }

    .badge {
        font-size: 0.9em;
        padding: 0.4em 0.6em;
    }

    .fade-in {
        animation: fadeIn 0.6s ease-in;
    }

    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(10px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style>

<div class="container mt-5 fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="detail-card">
                <!-- Gambar -->
                <img src="<?= htmlspecialchars($fotoPath) ?>" class="detail-img" alt="Foto <?= htmlspecialchars($jenis) ?>">

                <!-- Konten -->
                <div class="detail-content">
                    <h3 class="mb-3">
                        <?= htmlspecialchars($jenis === 'ruangan' ? $item['nama_ruangan'] : $item['nama_kendaraan']) ?>
                    </h3>

                    <?php if ($jenis === 'ruangan'): ?>
                        <p><strong>Lokasi:</strong> <?= htmlspecialchars($item['lokasi']) ?></p>
                        <p><strong>Kapasitas:</strong> <?= htmlspecialchars($item['kapasitas']) ?> orang</p>
                    <?php else: ?>
                        <p><strong>No. Polisi:</strong> <?= htmlspecialchars($item['no_polisi']) ?></p>
                        <p><strong>Jenis Kendaraan:</strong> <?= htmlspecialchars($item['jenis_kendaraan'] ?? '-') ?></p>
                    <?php endif; ?>

                    <p><strong>Status:</strong>
                        <?php if($item['status'] === 'tersedia'): ?>
                            <span class="badge bg-success">Tersedia</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Dipinjam</span>
                        <?php endif; ?>
                    </p>

                    <div class="mt-4 d-flex gap-2">
                        <?php if ($item['status'] === 'tersedia'): ?>
                            <a href="pinjam_form.php?jenis=<?= $jenis ?>&id=<?= $id ?>" class="btn btn-primary px-4">Pinjam</a>
                        <?php else: ?>
                            <button class="btn btn-secondary px-4" disabled>Sudah Dipinjam</button>
                        <?php endif; ?>

                        <!-- Tombol Kembali -->
                        <a href="<?= htmlspecialchars($backLink) ?>" class="btn btn-back px-4">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
