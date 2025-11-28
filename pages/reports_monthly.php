<?php
session_start();
include_once("../koneksi.php");

$adminRoles = ['super_admin', 'admin_ruangan', 'admin_kendaraan'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../modules/auth/login.php');
    exit();
}

$pageTitle = "Laporan Bulanan";
include("../includes/header.php");
include("../includes/navbar.php");

$where = "";
if ($_SESSION['role'] == 'admin_ruangan') {
    $where = "WHERE jenis = 'ruangan'";
} elseif ($_SESSION['role'] == 'admin_kendaraan') {
    $where = "WHERE jenis = 'kendaraan'";
}

$q = $con->query("
    SELECT 
        YEAR(tanggal_pinjam) AS tahun,
        MONTHNAME(tanggal_pinjam) AS bulan,
        COUNT(*) AS total
    FROM peminjaman
    $where
    GROUP BY tahun, MONTH(tanggal_pinjam)
    ORDER BY tahun DESC, MONTH(tanggal_pinjam) DESC
");

$qJenis = null;
if ($_SESSION['role'] == 'super_admin') {
    $qJenis = $con->query("
        SELECT 
            YEAR(tanggal_pinjam) AS tahun,
            MONTHNAME(tanggal_pinjam) AS bulan,
            jenis,
            COUNT(*) AS total
        FROM peminjaman
        GROUP BY tahun, MONTH(tanggal_pinjam), jenis
        ORDER BY tahun DESC, MONTH(tanggal_pinjam) DESC
    ");
}

$qKendaraan = null;
if ($_SESSION['role'] == 'admin_kendaraan') {
    $qKendaraan = $con->query("
        SELECT 
            YEAR(p.tanggal_pinjam) AS tahun,
            MONTHNAME(p.tanggal_pinjam) AS bulan,
            k.nama_kendaraan,k.no_polisi,
            COUNT(*) AS total
        FROM peminjaman p
        JOIN kendaraan k ON p.id_item = k.id
        WHERE p.jenis = 'kendaraan'
        GROUP BY tahun, MONTH(p.tanggal_pinjam), k.nama_kendaraan
        ORDER BY tahun DESC, MONTH(p.tanggal_pinjam) DESC, k.nama_kendaraan ASC
    ");
}

$qRuangan = null;
if ($_SESSION['role'] == 'admin_ruangan') {
    $qRuangan = $con->query("
        SELECT 
            YEAR(p.tanggal_pinjam) AS tahun,
            MONTHNAME(p.tanggal_pinjam) AS bulan,
            r.nama_ruangan,r.lokasi,
            COUNT(*) AS total
        FROM peminjaman p
        JOIN ruangan r ON p.id_item = r.id
        WHERE p.jenis = 'ruangan'
        GROUP BY tahun, MONTH(p.tanggal_pinjam), r.nama_ruangan
        ORDER BY tahun DESC, MONTH(p.tanggal_pinjam) DESC, r.nama_ruangan ASC
    ");
}
?>

<div class="container mt-4">
    <h3 class="mb-3">📅 Laporan Peminjaman Bulanan</h3>
    <p class="text-muted">
        Menampilkan jumlah peminjaman tiap bulan
        <?= $_SESSION['role'] == 'super_admin' ? 'untuk semua jenis (ruangan & kendaraan).' : 'berdasarkan jenis yang dikelola.' ?>
    </p>

    <button id="downloadCsv" class="btn btn-sm btn-success mb-3">
        ⬇️ Download CSV
    </button>

    <div class="card shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <h5 class="card-title">📊 Laporan Berdasarkan Bulan</h5>
            <table id="laporanTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $q->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['bulan']) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- ====================================================================Superadmin==================================================================== -->
    <?php if ($_SESSION['role'] == 'super_admin' && $qJenis): ?>
    <div class="card shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <h5 class="card-title">📈 Laporan Perbandingan Jenis Peminjaman</h5>
            <p class="text-muted">Menampilkan jumlah peminjaman tiap bulan berdasarkan jenis (ruangan vs kendaraan).</p>
            <table id="laporanJenisTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Jenis</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qJenis->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['bulan']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($row['jenis'])) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====================================================================Kendaraan==================================================================== -->
    <?php if ($_SESSION['role'] == 'admin_kendaraan' && $qKendaraan): ?>
    <?php
    // Handle filter pencarian
    $search = isset($_GET['search_kendaraan']) ? trim($_GET['search_kendaraan']) : '';

    // Query laporan dengan filter
    $queryKendaraan = "
    SELECT 
        YEAR(p.tanggal_pinjam) AS tahun, 
        MONTH(p.tanggal_pinjam) AS bulan, 
        k.nama_kendaraan, 
        k.no_polisi, 
        COUNT(*) AS total 
    FROM peminjaman p 
    JOIN kendaraan k ON p.id_item = k.id 
    WHERE p.jenis = 'kendaraan'
";

    if (!empty($search)) {
    $queryKendaraan .= " AND k.nama_kendaraan LIKE '%" . $con->real_escape_string($search) . "%'";
    }

    $queryKendaraan .= " GROUP BY tahun, bulan, k.id ORDER BY tahun DESC, bulan DESC";

    $qKendaraan = $con->query($queryKendaraan);

    // ... (lanjutkan dengan kode dashboard lainnya)
    ?>

<!-- Di bagian tempat tabel laporan ditampilkan -->
<?php if ($_SESSION['role'] == 'admin_kendaraan' && $qKendaraan): ?>
    <div class="card shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="card-title">🚗 Laporan Peminjaman Kendaraan per Bulan</h5>
            <p class="text-muted">Menampilkan nama kendaraan dan jumlah peminjaman tiap bulan.</p>
            
            <!-- Form Filter Pencarian -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <label for="search_kendaraan" class="form-label me-2 mb-0">Cari Nama Kendaraan:</label>
                <input type="text" class="form-control me-2" id="search_kendaraan" name="search_kendaraan" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan nama kendaraan..." style="max-width: 300px;">
                <button type="submit" class="btn btn-primary me-2">Cari</button>
                <a href="?search_kendaraan=" class="btn btn-secondary">Reset</a>
            </form>
            
            <table id="laporanKendaraanTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Nama Kendaraan</th>
                        <th>No Polisi</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qKendaraan->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['bulan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kendaraan']) ?></td>
                            <td><?= htmlspecialchars($row['no_polisi']) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

    <?php endif; ?>

    <!-- ====================================================================Ruangan==================================================================== -->
        <?php if ($_SESSION['role'] == 'admin_ruangan' && $qRuangan): ?>
            <?php
            // Handle filter pencarian
            $search = isset($_GET['search_ruangan']) ? trim($_GET['search_ruangan']) : '';

            // Query laporan dengan filter
            $queryRuangan = "
            SELECT 
            YEAR(p.tanggal_pinjam) AS tahun, 
            MONTH(p.tanggal_pinjam) AS bulan, 
            r.nama_ruangan, 
            r.lokasi, 
            COUNT(*) AS total 
        FROM peminjaman p 
        JOIN ruangan r ON p.id_item = r.id 
        WHERE p.jenis = 'ruangan'
    ";

    if (!empty($search)) {
    $queryRuangan .= " AND r.nama_ruangan LIKE '%" . $con->real_escape_string($search) . "%'";
    }

    $queryRuangan .= " GROUP BY tahun, bulan, r.id ORDER BY tahun DESC, bulan DESC";

    $qRuangan = $con->query($queryRuangan);

    // ... (lanjutkan dengan kode dashboard lainnya)
    ?>

    <!-- Di bagian tempat tabel laporan ditampilkan -->
    <?php if ($_SESSION['role'] == 'admin_ruangan' && $qRuangan): ?>
    <div class="card shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="card-title">🚗 Laporan Peminjaman Ruangan per Bulan</h5>
            <p class="text-muted">Menampilkan nama Ruangan dan jumlah peminjaman tiap bulan.</p>
            
            <!-- Form Filter Pencarian -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <label for="search_ruangan" class="form-label me-2 mb-0">Cari Nama Ruangan:</label>
                <input type="text" class="form-control me-2" id="search_ruangan" name="search_ruangan" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan nama ruangan..." style="max-width: 300px;">
                <button type="submit" class="btn btn-primary me-2">Cari</button>
                <a href="?search_ruangan=" class="btn btn-secondary">Reset</a>
            </form>
            
            <table id="laporanRuanganTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Nama Ruangan</th>
                        <th>Lokasi</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qRuangan->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['bulan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                            <td><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Script Export ke CSV -->
<script>
document.getElementById("downloadCsv").addEventListener("click", function () {
    const tables = document.querySelectorAll("table");
    let csv = [];

    tables.forEach((table, index) => {
        csv.push("");
        if (index === 0) {
            csv.push("Laporan Bulanan");
        } else if (index === 1 && document.getElementById("laporanJenisTable")) {
            csv.push("Laporan Berdasarkan Jenis");
        } else if (index === 2 && document.getElementById("laporanKendaraanTable")) {
            csv.push("Laporan Peminjaman Kendaraan per Bulan");
        }
        for (let i = 0; i < table.rows.length; i++) {
            let row = [], cols = table.rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
    });

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    const link = document.createElement("a");
    link.download = "laporan_bulanan.csv";
    link.href = window.URL.createObjectURL(csvFile);
    link.click();
});
</script>

<?php include("../includes/footer.php"); ?>
