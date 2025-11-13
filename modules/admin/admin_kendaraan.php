<?php
session_start();
include_once("../../koneksi.php");

// authentication
$adminRoles = ['admin_kendaraan', 'super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$pageTitle = "Dashboard Admin Kendaraan";
include("../../includes/header.php");
include("../../includes/navbar.php");

// statistik
$totalKendaraan = $con->query("SELECT COUNT(*) AS total FROM kendaraan")->fetch_assoc()['total'];
$kendaraanDipinjam = $con->query("SELECT COUNT(*) AS total FROM kendaraan WHERE status='dipinjam'")->fetch_assoc()['total'];
$kendaraanTersedia = $con->query("SELECT COUNT(*) AS total FROM kendaraan WHERE status='tersedia'")->fetch_assoc()['total'];
$totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];


// data line chart
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

// 📆 Data Line Chart per Minggu
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

<div class="container mt-4">
    <p class="text-muted">Kelola data peminjaman dan kendaraan.</p>

    <!-- Statistik -->
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
                        <h4><?= $totalPeminjaman; ?></h4>
                        <p class="mb-0">Total Peminjaman</p>
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

    <!-- Grafik -->
     <div class="row mb-4 g-4">
        <!-- Line Chart Kendaraan Per Minggu -->
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

        <!-- Line Chart Kendaraan Per Bulan -->
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


    <!-- CRUD KENDARAAN -->
    <div class="card shadow-sm mb-5">
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

    <!-- Riwayat Peminjaman -->
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
                            <td><?= htmlspecialchars($row['tanggal_kembali'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['status'] === 'dipinjam'): ?>
                                    <span class="status-label pinjam">Belum Dikembalikan</span>
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

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// === Bar Chart ===

// linechart bulanan
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

// line chart
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
/* Ukuran chart */
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
</style>

<?php include("../../includes/footer.php"); ?>
