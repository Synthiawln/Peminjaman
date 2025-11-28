<?php
session_start();
include_once("../../koneksi.php");
$minDate = date('Y-m-d');

$adminRoles = ['admin_kendaraan', 'super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$pageTitle = "Dashboard Admin Kendaraan";
include("../../includes/header.php");
include("../../includes/navbar.php");

// / Handle form submission for direct loan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjamkan_kendaraan'])) {
    $id_user = $_POST['id_user'];
    $id_kendaraan = $_POST['id_kendaraan'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $keterangan = $_POST['keterangan'] ?? '';

    // Validate inputs
    if (empty($id_user) || empty($id_kendaraan) || empty($tanggal_pinjam) || empty($tanggal_kembali)) {
        $error = "Semua field harus diisi.";
    } elseif (strtotime($tanggal_pinjam) >= strtotime($tanggal_kembali)) {
        $error = "Tanggal kembali harus setelah tanggal pinjam.";
    } else {
        // Check if vehicle is available
        $check_vehicle = $con->prepare("SELECT status FROM kendaraan WHERE id = ?");
        $check_vehicle->bind_param("i", $id_kendaraan);
        $check_vehicle->execute();
        $vehicle_status = $check_vehicle->get_result()->fetch_assoc()['status'];
        if ($vehicle_status !== 'tersedia') {
            $error = "Kendaraan tidak tersedia.";
        } else {
            // Generate unique kode_peminjaman
            $kode_peminjaman = 'ADM-' . date('Ymd') . '-' . rand(1000, 9999);

            // Insert into peminjaman
            $stmt = $con->prepare("INSERT INTO peminjaman (id_user, id_item, jenis, tanggal_pinjam, tanggal_kembali, status, kode_peminjaman, keterangan_user, created_at) VALUES (?, ?, 'kendaraan', ?, ?, 'dipinjam', ?, ?, NOW())");
            $stmt->bind_param("iissss", $id_user, $id_kendaraan, $tanggal_pinjam, $tanggal_kembali, $kode_peminjaman, $keterangan);
            if ($stmt->execute()) {
                // Update vehicle status
                $update_vehicle = $con->prepare("UPDATE kendaraan SET status = 'dipinjam' WHERE id = ?");
                $update_vehicle->bind_param("i", $id_kendaraan);
                $update_vehicle->execute();
                $success = "Kendaraan berhasil dipinjamkan.";
            } else {
                $error = "Gagal meminjamkan kendaraan.";
            }
        }
    }
}


$totalKendaraan = $con->query("SELECT COUNT(*) AS total FROM kendaraan")->fetch_assoc()['total'];
$kendaraanDipinjam = $con->query("SELECT COUNT(*) AS total FROM kendaraan WHERE status='dipinjam'")->fetch_assoc()['total'];
$kendaraanTersedia = $con->query("SELECT COUNT(*) AS total FROM kendaraan WHERE status='tersedia'")->fetch_assoc()['total'];
$totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];


$peminjamanPerBulan = $con->query("
    SELECT DATE_FORMAT(tanggal_pinjam, '%M %Y') AS bulan, COUNT(*) AS total
    FROM peminjaman
    WHERE jenis = 'kendaraan'
    GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
    ORDER BY MIN(tanggal_pinjam)
");

$lineLabels = [];
$lineData = [];
while ($r = $peminjamanPerBulan->fetch_assoc()) {
    $lineLabels[] = $r['bulan'];
    $lineData[] = (int)$r['total'];
}


$peminjamanPerMinggu = $con->query("
    SELECT 
        YEAR(tanggal_pinjam) AS tahun,
        WEEK(tanggal_pinjam, 1) AS minggu_ke,
        CONCAT('Minggu ', WEEK(tanggal_pinjam, 1), ' (', DATE_FORMAT(tanggal_pinjam, '%b %Y'), ')') AS label,
        COUNT(*) AS total
    FROM peminjaman
    WHERE jenis = 'kendaraan'
    GROUP BY tahun, minggu_ke
    ORDER BY tahun, minggu_ke
");

$labels = [];
$totals = [];
while ($row = $peminjamanPerMinggu->fetch_assoc()) {
    $labels[] = $row['label'];
    $totals[] = (int)$row['total'];
}
?>
    <!-- Form Pinjamkan Kendaraan untuk User Tertentu -->
<div class="container mt-4">
    <p class="text-muted">Kelola data peminjaman dan kendaraan.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-header text-white d-flex align-items-center" style="background-color: #746616cf;">
            <i class="bi bi-plus-circle me-2"></i>
            <span>Pinjamkan Kendaraan untuk User Tertentu</span>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3">
                        <label for="id_user" class="form-label">Pilih User</label>
                        <select class="form-select" id="id_user" name="id_user" required>
                            <option value="">-- Pilih User --</option>
                            <?php
                            $users = $con->query("SELECT id, nama, username FROM user ORDER BY nama ASC");
                            while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['username']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="id_kendaraan" class="form-label">Pilih Kendaraan</label>
                        <select class="form-select" id="id_kendaraan" name="id_kendaraan" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            <?php
                            $vehicles = $con->query("SELECT id, nama_kendaraan, no_polisi FROM kendaraan WHERE status='tersedia' ORDER BY nama_kendaraan ASC");
                            while ($v = $vehicles->fetch_assoc()): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nama_kendaraan']) ?> (<?= htmlspecialchars($v['no_polisi']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam</label>
                        <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" required min="<?= $minDate ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="tanggal_kembali" class="form-label">Tanggal Kembali</label>
                        <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali" required min="<?= $minDate ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="1"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="pinjamkan_kendaraan" class="btn btn-primary">Pinjamkan Kendaraan</button>
                </div>
            </form>
        </div>
    </div>

<div class="container mt-4">
    <p class="text-muted">Kelola data peminjaman dan kendaraan.</p>

    
    <div class="row text-center mb-4 g-4">
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow-sm border-0 rounded-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                    <h4><?= $totalKendaraan; ?></h4>
                    <p class = "mb-0">Total Kendaraan</p>
                    </div>
                </div>
                <i class="bi bi-truck fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm border-0 rounded-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4><?= $kendaraanDipinjam; ?></h4>
                        <p class="mb-0">Kendaraan Dipinjam</p>
                    </div>
                </div>
                <i class="bi bi-arrow-repeat fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm border-0 rounded-4 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                    <h4><?= $kendaraanTersedia; ?></h4>
                    <p>Kendaraan Tersedia</p>
                    </div>
                </div>
                  <i class="bi bi-car-front-fill fs-1 opacity-75 position-absolute bottom-0 end-0 m-3"></i>
            </div>
        </div>
    </div>

   
     <div class="row mb-4 g-4">
       
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header text-white fw-semibold d-flex align-items-center" style="background: linear-gradient(90deg, #c9890b, #f5c542);">
                <i class="bi bi-graph-up-arrow me-2"></i> Tren Peminjaman Kendaraan Per Minggu
                </div>
            <div class="card-body p-4">
                <canvas id="lineChartKendaraanMinggu" style="min-height: 295px;"></canvas>
            </div>
        </div>
        </div>

      
        <div class="col-md-6">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header text-white fw-semibold d-flex align-items-center"style="background: linear-gradient(90deg, #556b2f, #9dc183);">
                <i class="bi bi-calendar3 me-2"></i> Tren Peminjaman Kendaraan Per Bulan
            </div>
        <div class="card-body p-4">
            <canvas id="lineChartKendaraan" style="min-height: 280px;"></canvas>
        </div>
        </div>
        </div>
    </div>

   <div class="row">
    <div class="col-lg-6 mb-5">
        <div class="card shadow-sm">
            <div class="card-header text-white d-flex align-items-center" style="background-color: #746616cf;">
                <i class="bi bi-hourglass-split me-2"></i>
                <span>Permintaan Peminjaman Kendaraan (Menunggu Persetujuan)</span>
            </div>
            <div class="card-body table-responsive">
                <?php
                $pending = $con->query("SELECT p.*, u.nama AS peminjam, k.nama_kendaraan FROM peminjaman p JOIN user u ON p.id_user = u.id JOIN kendaraan k ON p.id_item = k.id WHERE p.jenis='kendaraan' AND p.status='pending' ORDER BY p.created_at ASC");
                ?>
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode Temp</th>
                            <th>Nama Peminjam</th>
                            <th>Kendaraan</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $pending->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['kode_peminjaman'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($p['peminjam']) ?></td>
                                <td><?= htmlspecialchars($p['nama_kendaraan']) ?></td>
                                <td><?= htmlspecialchars($p['tanggal_pinjam']) ?></td>
                                <td><?= htmlspecialchars($p['tanggal_kembali']) ?></td>
                                <td class="text-center">
                                    <a href="approve_kendaraan.php?id=<?= $p['id'] ?>&action=approve" class="btn btn-sm btn-success" onclick="return confirm('Setujui permintaan ini?')">Setujui</a>
                                    <a href="approve_kendaraan.php?id=<?= $p['id'] ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('Tolak permintaan ini?')">Tolak</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-5">
        <div class="card shadow-sm">
            <div class="card-header text-white d-flex align-items-center" style="background-color: #5e4c2e;">
                <i class="bi bi-reply-all me-2"></i>
                <span>Permintaan Pengembalian Kendaraan (Menunggu Persetujuan)</span>
            </div>

            <div class="card-body table-responsive">
                <?php
                $pendingReturn = $con->prepare("
                SELECT p.id, p.kode_peminjaman, p.tanggal_kembali,p.keterangan_user,
                u.nama AS peminjam, k.nama_kendaraan
                FROM peminjaman p
                JOIN user u ON p.id_user = u.id
                JOIN kendaraan k ON p.id_item = k.id
                WHERE p.jenis = 'kendaraan'
                AND p.status = ''
                ORDER BY p.tanggal_kembali ASC
                ");

                $pendingReturn->execute();
                $result = $pendingReturn->get_result();
                ?>

                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode Pinjam</th>
                            <th>Peminjam</th>
                            <th>Kendaraan</th>
                            <th>Tgl. Kembali Diajukan</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['kode_peminjaman']) ?></td>
                                <td><?= htmlspecialchars($r['peminjam']) ?></td>
                                <td><?= htmlspecialchars($r['nama_kendaraan']) ?></td>
                                <td><?= htmlspecialchars($r['tanggal_kembali']) ?></td>
                                <td><?= htmlspecialchars($r['keterangan_user'] ?: '-') ?></td>

                                <td class="text-center">
                                    <a href="kembali_kendaraan.php?id=<?= $r['id'] ?>&action=approve"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Setujui pengembalian kendaraan ini?')">
                                    Setujui
                                    </a>

                                    <a href="approve_kembali_kendaraan.php?id=<?= $r['id'] ?>&action=reject"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Tolak pengembalian ini?')">
                                    Tolak
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #746616cf;">
            <span>Kelola Data Kendaraan</span>
            <a href="../../pages/kendaraan_crud.php" class="btn btn-sm btn-light fw-semibold">+ Tambah Kendaraan</a>
        </div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Nama</th><th>Plat Nomor</th><th>Status</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php
                    $kendaraan = $con->query("SELECT * FROM kendaraan ORDER BY nama_kendaraan ASC");
                    while ($k = $kendaraan->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $k['id']; ?></td>
                        <td><?= htmlspecialchars($k['nama_kendaraan']); ?></td>
                        <td><?= htmlspecialchars($k['no_polisi']); ?></td>
                        <td>
                            <?php if ($k['status'] == 'tersedia'): ?>
                                <span class="status-label tersedia">Tersedia</span>
                            <?php else: ?>
                                <span class="status-label dipinjam">Dipinjam</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="../../pages/kendaraan_crud.php?edit=<?= $k['id']; ?>" class="btn btn-sm me-1" style="background-color: #9e8a40ff; color: #ffffffff;">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="../../pages/kendaraan_crud.php?hapus=<?= $k['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus kendaraan ini?')">
                               <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    

    <div class="card shadow-sm mb-5">
        <div class="card-header text-white d-flex align-items-center" style="background-color: #746616cf;">
            <i class="bi bi-clock-history me-2"></i> 
            <span>📋 Riwayat Peminjaman Kendaraan Terbaru</span>
        </div>
        <div class="card-body table-responsive">
            <?php
            $result = $con->query("
                SELECT p.*, u.nama 
                FROM peminjaman p
                JOIN user u ON p.id_user = u.id
                WHERE jenis = 'kendaraan'
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            ?>
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Peminjam</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_peminjaman']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_pinjam']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_kembali_aktual'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['status'] === 'dipinjam' || $row['status'] === ''): ?>
                                    <span class="status-label pinjam">Belum Dikembalikan</span>
                                <?php elseif ($row['status'] === 'pending'): ?>
                                    <span class="status-label tolak">Menunggu persetujuan</span>
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <span class="status-label tolak">Ditolak</span>
                                <?php else: ?>
                                    <span class="status-label kembali">Sudah Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxLine = document.getElementById('lineChartKendaraan').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?= json_encode($lineLabels) ?>,
        datasets: [{
            label: 'Peminjaman Kendaraan per Bulan',
            data: <?= json_encode($lineData) ?>,
            fill: true,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.2)',
            tension: 0.3
        }]
    },
    options: { scales: { y: { beginAtZero: true } } }
});


const ctxLineMinggu = document.getElementById('lineChartKendaraanMinggu').getContext('2d');
new Chart(ctxLineMinggu, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Peminjaman Kendaraan per Minggu',
            data: <?= json_encode($totals) ?>,
            fill: true,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255,193,7,0.2)',
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6,
        }]
    },
    options: { 
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: true } }
    }
});
</script>

<style>

#lineChartKendaraanMinggu {
    max-width: 600px;
    height: 250px;
    margin: 0 auto;
}
.status-label.tersedia {
    background-color: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
}
.status-label.dipinjam {
    background-color: #dc3545;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
} 

.status-label.kembali {
    background-color: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
}

.status-label.pinjam {
    background-color: #dc3545;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
} 

.status-label.tolak {
    background-color: #454544ff;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.9em;
} 
</style>

<?php include("../../includes/footer.php"); ?>
