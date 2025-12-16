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

// --------------------------- LAPORAN BULANAN -----------------------------

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

// ------------------------ LAPORAN SUPER ADMIN ----------------------------

$qJenis = $con->query("
    SELECT 
        YEAR(p.tanggal_pinjam) AS tahun,
        MONTHNAME(p.tanggal_pinjam) AS bulan,
        p.jenis,
        COALESCE(k.nama_kendaraan, r.nama_ruangan) AS nama_item,
        GROUP_CONCAT(DISTINCT u.nama SEPARATOR ', ') AS nama_user,
        COUNT(*) AS total
    FROM peminjaman p
    LEFT JOIN kendaraan k ON p.id_item = k.id AND p.jenis = 'kendaraan'
    LEFT JOIN ruangan r ON p.id_item = r.id AND p.jenis = 'ruangan'
    JOIN user u ON p.id_user = u.id
    GROUP BY tahun, MONTH(p.tanggal_pinjam), p.jenis, p.id_item
    ORDER BY tahun DESC, MONTH(p.tanggal_pinjam) DESC
");


// ------------------------ LAPORAN KENDARAAN ----------------------------

$qKendaraan = null;
if ($_SESSION['role'] == 'admin_kendaraan') {

    // Ambil search dari GET
    $search = isset($_GET['search_kendaraan']) ? trim($_GET['search_kendaraan']) : '';

    // Query dasar kendaraan
    $queryKendaraan = "
        SELECT 
            YEAR(p.tanggal_pinjam) AS tahun, 
            MONTH(p.tanggal_pinjam) AS bulan, 
            p.tanggal_pinjam,
            k.nama_kendaraan,
            k.no_polisi,
            COUNT(*) AS total,
            GROUP_CONCAT(u.nama SEPARATOR ', ') AS daftar_user
        FROM peminjaman p
        JOIN kendaraan k ON p.id_item = k.id
        JOIN user u ON p.id_user = u.id
        WHERE p.jenis = 'kendaraan'
    ";

    // Search multi kolom
    if (!empty($search)) {
        $safe = $con->real_escape_string($search);
        $queryKendaraan .= "
            AND (
                k.nama_kendaraan LIKE '%$safe%' 
                OR k.no_polisi LIKE '%$safe%'
                OR u.nama LIKE '%$safe%'
            )
        ";
    }

    $queryKendaraan .= "
        GROUP BY tahun, bulan, k.id
        ORDER BY tahun DESC, bulan DESC
    ";

    $qKendaraan = $con->query($queryKendaraan);
}

// ------------------------ LAPORAN RUANGAN ----------------------------

$qRuangan = null;
if ($_SESSION['role'] == 'admin_ruangan') {

    $search = isset($_GET['search_ruangan']) ? trim($_GET['search_ruangan']) : '';

    $queryRuangan = "
        SELECT 
            YEAR(p.tanggal_pinjam) AS tahun, 
            MONTH(p.tanggal_pinjam) AS bulan, 
            p.tanggal_pinjam,
            r.nama_ruangan,
            r.lokasi,
            COUNT(*) AS total,
            GROUP_CONCAT(u.nama SEPARATOR ', ') AS daftar_user
        FROM peminjaman p
        JOIN ruangan r ON p.id_item = r.id
        JOIN user u ON p.id_user = u.id
        WHERE p.jenis = 'ruangan'
    ";

    if (!empty($search)) {
        $safe = $con->real_escape_string($search);
        $queryRuangan .= "
            AND (
                r.nama_ruangan LIKE '%$safe%' 
                OR r.lokasi LIKE '%$safe%'
                OR u.nama LIKE '%$safe%'
            )
        ";
    }

    $queryRuangan .= "
        GROUP BY tahun, bulan, r.id
        ORDER BY tahun DESC, bulan DESC
    ";

    $qRuangan = $con->query($queryRuangan);
}
?>

<div class="container mt-4">

    <h3 class="mb-3">📅 Laporan Peminjaman Bulanan</h3>
    <p class="text-muted">
        Menampilkan jumlah peminjaman tiap bulan
        <?= $_SESSION['role'] == 'super_admin' ? 'untuk semua jenis.' : 'berdasarkan jenis yang dikelola.' ?>
    </p>

    <button id="downloadCsv" class="btn btn-sm btn-success mb-3">
        ⬇️ Download CSV
    </button>

    <!-- ==================== TABEL LAPORAN BULANAN ==================== -->
    <div class="card shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <h5 class="card-title">📊 Laporan Berdasarkan Bulan</h5>
            <table id="laporanTable" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $q->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['tahun'] ?></td>
                            <td><?= $row['bulan'] ?></td>
                            <td><?= $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= SUPER ADMIN LAPORAN JENIS ================= -->
    <?php if ($_SESSION['role'] == 'super_admin' && $qJenis): ?>
    <div class="card shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <h5 class="card-title">📈 Laporan Perbandingan Jenis</h5>

            <table id="laporanJenisTable" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                    <th>Tahun</th>
                    <th>Bulan</th>
                    <th>Jenis</th>
                    <th>Nama Kendaraan / Ruangan</th>
                    <th>User Peminjam</th>
                    <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $qJenis->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['tahun'] ?></td>
                            <td><?= $row['bulan'] ?></td>
                            <td><?= ucfirst($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['nama_item']) ?></td>
                            <td><?= htmlspecialchars($row['nama_user']) ?></td>
                            <td><?= $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================= ADMIN KENDARAAN ================= -->
    <?php if ($_SESSION['role'] == 'admin_kendaraan'): ?>
    <div class="card shadow-sm rounded-4 mt-3">
        <div class="card-body">

            <h5 class="card-title">🚗 Laporan Peminjaman Kendaraan</h5>

            <!-- FORM SEARCH -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <label for="search_kendaraan" class="form-label me-2 mb-0">Cari:</label>
                <input type="text" class="form-control me-2" id="search_kendaraan" name="search_kendaraan"
                    placeholder="nama peminjam / kendaraan / nopol..."
                    value="<?= htmlspecialchars($_GET['search_kendaraan'] ?? '') ?>"
                    style="max-width:300px;">
                <button type="submit" class="btn btn-primary me-2">Cari</button>
                <a href="?" class="btn btn-secondary">Reset</a>
            </form>

            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nama User</th>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Tanggal</th>
                        <th>Nama Kendaraan</th>
                        <th>No Polisi</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qKendaraan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['daftar_user'] ?></td>
                            <td><?= $row['tahun'] ?></td>
                            <td><?= $row['bulan'] ?></td>
                            <td><?= $row['tanggal_pinjam'] ?></td>
                            <td><?= $row['nama_kendaraan'] ?></td>
                            <td><?= $row['no_polisi'] ?></td>
                            <td><?= $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>
    <?php endif; ?>

    <!-- ================= ADMIN RUANGAN ================= -->
    <?php if ($_SESSION['role'] == 'admin_ruangan'): ?>
    <div class="card shadow-sm rounded-4">
        <div class="card-body">

            <h5 class="card-title">📌 Laporan Ruangan</h5>

            <!-- FORM SEARCH -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <label for="search_ruangan" class="form-label me-2 mb-0">Cari:</label>
                <input type="text" class="form-control me-2" id="search_ruangan" name="search_ruangan"
                    placeholder="nama ruangan / lokasi / nama peminjam..."
                    value="<?= htmlspecialchars($_GET['search_ruangan'] ?? '') ?>"
                    style="max-width:300px;">
                <button type="submit" class="btn btn-primary me-2">Cari</button>
                <a href="?" class="btn btn-secondary">Reset</a>
            </form>

            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nama User</th>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Tanggal</th>
                        <th>Nama Ruangan</th>
                        <th>Lokasi</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qRuangan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['daftar_user'] ?></td>
                            <td><?= $row['tahun'] ?></td>
                            <td><?= $row['bulan'] ?></td>
                            <td><?= $row['tanggal_pinjam'] ?></td>
                            <td><?= $row['nama_ruangan'] ?></td>
                            <td><?= $row['lokasi'] ?></td>
                            <td><?= $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>
    <?php endif; ?>

</div>

<!-- EXPORT CSV -->
<script>
document.getElementById("downloadCsv").addEventListener("click", function () {
    const tables = document.querySelectorAll("table");
    let csv = [];

    tables.forEach((table, index) => {
        csv.push("");

        for (let i = 0; i < table.rows.length; i++) {
            let row = [];
            let cols = table.rows[i].querySelectorAll("td, th");

            for (let j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }

            csv.push(row.join(","));
        }
    });

    const blob = new Blob([csv.join("\n")], { type: "text/csv" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "laporan.csv";
    link.click();
});
</script>

<?php include("../includes/footer.php"); ?>
