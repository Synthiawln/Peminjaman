<?php
session_start();
include_once("../../koneksi.php");

//authentication
$adminRoles = ['admin_ruangan','super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$pageTitle = "Dashboard Admin Ruangan";
include("../../includes/header.php");
include("../../includes/navbar.php");

// Statistik jumlah ruangan & peminjaman
$totalRuangan = $con->query("SELECT COUNT(*) AS total FROM ruangan")->fetch_assoc()['total'];
$ruanganDipinjam = $con->query("SELECT COUNT(*) AS total FROM ruangan WHERE status='dipinjam'")->fetch_assoc()['total'];
$ruanganTersedia = $con->query("SELECT COUNT(*) AS total FROM ruangan WHERE status='tersedia'")->fetch_assoc()['total'];
$totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];


// Data untuk Bar Chart
$chartLabels = ['Total Ruangan', 'Ruangan Dipinjam'];
$chartData = [$totalRuangan, $ruanganDipinjam];

// Data untuk Line Chart (Bulanan)
$peminjamanPerBulan = $con->query("
    SELECT DATE_FORMAT(tanggal_pinjam, '%M %Y') AS bulan, COUNT(*) AS total
    FROM peminjaman
    WHERE jenis = 'ruangan'
    GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
    ORDER BY MIN(tanggal_pinjam)
");

$lineLabels = [];
$lineData = [];
while ($r = $peminjamanPerBulan->fetch_assoc()) {
    $lineLabels[] = $r['bulan'];
    $lineData[] = (int)$r['total'];
}

// Data untuk Line Chart (Mingguan)
$peminjamanPerMinggu = $con->query("
    SELECT 
        YEAR(tanggal_pinjam) AS tahun,
        WEEK(tanggal_pinjam, 1) AS minggu_ke,
        CONCAT('Minggu ', WEEK(tanggal_pinjam, 1), ' (', DATE_FORMAT(tanggal_pinjam, '%b %Y'), ')') AS label,
        COUNT(*) AS total
    FROM peminjaman
    WHERE jenis = 'ruangan'
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
    <h3 class="mb-3">Dashboard Admin Ruangan</h3>
    <p class="text-muted">Kelola data peminjaman dan ruangan.</p>

    <!-- Statistik -->
    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow-sm border-0">
                <div class="card-body">
                    <h4><?= $totalRuangan; ?></h4>
                    <p>Total Ruangan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm border-0">
                <div class="card-body">
                    <h4><?= $ruanganDipinjam; ?></h4>
                    <p>Ruangan Dipinjam</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm border-0">
                <div class="card-body">
                    <h4><?= $ruanganTersedia; ?></h4>
                    <p>Ruangan Tersedia</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="row mb-4">
        <!-- Bar Chart -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header text-white" style="background-color: #746616cf">Perbandingan Total vs Ruangan Dipinjam</div>
                <div class="card-body">
                    <canvas id="ruanganChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Line Chart Bulanan -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header text-white" style="background-color: #746616cf">Tren Peminjaman Ruangan Per Bulan</div>
                <div class="card-body">
                    <canvas id="lineChartRuanganBulan"></canvas>
                </div>
            </div>
        </div>

        <!-- Line Chart Mingguan -->
        <div class="col-md-12 mt-4">
            <div class="card shadow-sm">
                <div class="card-header text-white" style="background-color: #746616cf">Tren Peminjaman Ruangan Per Minggu</div>
                <div class="card-body">
                    <canvas id="lineChartRuanganMinggu"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- CRUD RUANGAN -->
    <div class="card shadow-sm mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #746616cf">
            <span>Kelola Data Ruangan</span>
            <a href="../../pages/ruangan_crud.php" class="btn btn-sm btn-light fw-semibold">+ Tambah Ruangan</a>
        </div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Nama Ruangan</th><th>Lokasi</th><th>Status</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php
                    $ruangan = $con->query("SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
                    while ($r = $ruangan->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $r['id']; ?></td>
                        <td><?= htmlspecialchars($r['nama_ruangan']); ?></td>
                        <td><?= htmlspecialchars($r['lokasi']); ?></td>
                        <td>
                            <?php if ($r['status'] == 'tersedia'): ?>
                                <span class="status-label tersedia">Tersedia</span>
                            <?php else: ?>
                                <span class="status-label dipinjam">Dipinjam</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <a href="../../pages/ruangan_crud.php?edit=<?= $r['id']; ?>" class="btn btn-sm me-1" style="background-color: #9e8a40ff; color: #ffffffff;">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="../../pages/ruangan_crud.php?hapus=<?= $r['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus ruangan ini?')">
                               <i class="bi bi-trash"></i> Hapus
                            </a>
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
// bar chart
const ctxBar = document.getElementById('ruanganChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Jumlah Ruangan',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: ['#007bff', '#dc3545'],
        }]
    },
    options: { 
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
    }
});

// linechart bulanan
const ctxLineBulan = document.getElementById('lineChartRuanganBulan').getContext('2d');
new Chart(ctxLineBulan, {
    type: 'line',
    data: {
        labels: <?= json_encode($lineLabels) ?>,
        datasets: [{
            label: 'Peminjaman per Bulan',
            data: <?= json_encode($lineData) ?>,
            fill: true,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.2)',
            tension: 0.3
        }]
    },
    options: { 
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: true } }
    }
});

// linechart perminggu
const ctxLineMinggu = document.getElementById('lineChartRuanganMinggu').getContext('2d');
new Chart(ctxLineMinggu, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Peminjaman per Minggu',
            data: <?= json_encode($totals) ?>,
            fill: true,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255,193,7,0.2)',
            tension: 0.3
        }]
    },
    options: { 
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: true } }
    }
});


</script>
<style>
/* Atur ukuran container chart */
#lineChartRuanganMinggu {
    max-width: 500px;
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
</style>
<?php include("../../includes/footer.php"); ?>
