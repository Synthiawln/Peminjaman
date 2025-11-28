<?php
session_start();
include_once("../../koneksi.php");


$adminRoles = ['admin_ruangan','super_admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../auth/login.php');
    exit();
}

$pageTitle = "Dashboard Admin Ruangan";
include("../../includes/header.php");
include("../../includes/navbar.php");


$totalRuangan = $con->query("SELECT COUNT(*) AS total FROM ruangan")->fetch_assoc()['total'];
$ruanganDipinjam = $con->query("SELECT COUNT(*) AS total FROM ruangan WHERE status='dipinjam'")->fetch_assoc()['total'];
$ruanganTersedia = $con->query("SELECT COUNT(*) AS total FROM ruangan WHERE status='tersedia'")->fetch_assoc()['total'];
$totalPeminjaman = $con->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch_assoc()['total'];


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

   
<div class="row text-center mb-4 g-4">
    
    <div class="col-md-3">
        <div class="card text-white bg-danger shadow-sm border-0 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0"><?= $totalRuangan; ?></h4>
                    <p class="mb-0">Total Ruangan</p>
                </div>
                <i class="bi bi-building fs-1 opacity-75"></i>
            </div>
        </div>
    </div>

   
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm border-0 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0"><?= $ruanganDipinjam; ?></h4>
                    <p class="mb-0">Ruangan Dipinjam</p>
                </div>
                <i class="bi bi-door-open-fill fs-1 opacity-75"></i>
            </div>
        </div>
    </div>

   
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm border-0 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0"><?= $ruanganTersedia; ?></h4>
                    <p class="mb-0">Ruangan Tersedia</p>
                </div>
                <i class="bi bi-door-closed-fill fs-1 opacity-75"></i>
            </div>
        </div>
    </div>
</div>


    
    <div class="row mb-4 g-4">
   
    <div class="col-md-6">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header text-white fw-semibold" style="background: linear-gradient(90deg, #8b7d2f, #b6a84c);">
                <i class="bi bi-graph-up-arrow me-2"></i> Tren Peminjaman Ruangan Per Minggu
            </div>
            <div class="card-body p-4">
                <canvas id="lineChartRuanganMinggu" style="min-height: 295px;"></canvas>
            </div>
        </div>
    </div>

   
    <div class="col-md-6">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header text-white fw-semibold" style="background: linear-gradient(90deg, #556b2f, #9dc183);">
                <i class="bi bi-calendar3 me-2"></i> Tren Peminjaman Ruangan Per Bulan
            </div>
            <div class="card-body p-4">
                <canvas id="lineChartRuanganBulan" style="min-height: 280px;"></canvas>
            </div>
        </div>
    </div>
    </div>

    <!-- Permintaan pending -->
    <div class="row">
    <div class="col-lg-6 mb-5">
        <div class="card-header text-white d-flex align-items-center" style="background-color: #746616cf;">
            <i class="bi bi-hourglass-split me-2"></i>
            <span>Permintaan Peminjaman Ruangan (Menunggu Persetujuan)</span>
        </div>
        <div class="card-body table-responsive">
            <?php
            $pending = $con->query("SELECT p.*, u.nama AS peminjam, r.nama_ruangan FROM peminjaman p JOIN user u ON p.id_user = u.id JOIN ruangan r ON p.id_item = r.id WHERE p.jenis='ruangan' AND p.status='pending' ORDER BY p.created_at ASC");
            ?>
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Kode Temp</th>
                        <th>Nama Peminjam</th>
                        <th>Ruangan</th>
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
                            <td><?= htmlspecialchars($p['nama_ruangan']) ?></td>
                            <td><?= htmlspecialchars($p['tanggal_pinjam']) ?></td>
                            <td><?= htmlspecialchars($p['tanggal_kembali']) ?></td>
                            <td class="text-center">
                                <a href="approve_ruangan.php?id=<?= $p['id'] ?>&action=approve" class="btn btn-sm btn-success" onclick="return confirm('Setujui permintaan ini?')">Setujui</a>
                                <a href="approve_ruangan.php?id=<?= $p['id'] ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('Tolak permintaan ini?')">Tolak</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6 mb-5">
        <div class="card-header text-white d-flex align-items-center" style="background-color: #5e4c2e;">
            <i class="bi bi-reply-all me-2"></i>
            <span>Permintaan Pengembalian Ruangan (Menunggu Persetujuan)</span>
        </div>

        <div class="card-body table-responsive">
            <?php
            $pendingReturn = $con->prepare("
            SELECT p.id, p.kode_peminjaman, p.tanggal_kembali,p.keterangan_user,
            u.nama AS peminjam, k.nama_ruangan
            FROM peminjaman p
            JOIN user u ON p.id_user = u.id
            JOIN ruangan k ON p.id_item = k.id
            WHERE p.jenis = 'ruangan'
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
                        <th>Ruangan</th>
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
                            <td><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                            <td><?= htmlspecialchars($r['tanggal_kembali']) ?></td>
                            <td><?= htmlspecialchars($r['keterangan_user'] ?: '-') ?></td>

                            <td class="text-center">
                                <a href="kembali_ruangan.php?id=<?= $r['id'] ?>&action=approve"
                                class="btn btn-sm btn-success"
                                onclick="return confirm('Setujui pengembalian kendaraan ini?')">
                                Setujui
                                </a>

                                <a href="approve_kembali_ruangan.php?id=<?= $r['id'] ?>&action=reject"
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

    
    <div class="card shadow-sm mb-5">
        <div class="card-header text-white d-flex align-items-center" style="background-color: #746616cf;">
            <i class="bi bi-clock-history me-2"></i> 
            <span>📋 Riwayat Peminjaman Ruangan Terbaru</span>
        </div>
        <div class="card-body table-responsive">
            <?php
            $result = $con->query("
                SELECT p.*, u.nama 
                FROM peminjaman p
                JOIN user u ON p.id_user = u.id
                WHERE jenis = 'ruangan'
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
