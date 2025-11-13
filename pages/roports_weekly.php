<?php
session_start();
include_once("../koneksi.php");

// Hak akses
$adminRoles = ['super_admin', 'admin_ruangan', 'admin_kendaraan'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../modules/auth/login.php');
    exit();
}

$pageTitle = "Laporan Mingguan";
include("../includes/header.php");
include("../includes/navbar.php");

// Filter berdasarkan role
$where = "";
if ($_SESSION['role'] == 'admin_ruangan') {
    $where = "WHERE jenis = 'ruangan'";
} elseif ($_SESSION['role'] == 'admin_kendaraan') {
    $where = "WHERE jenis = 'kendaraan'";
}

// Query laporan mingguan
$q = $con->query("
    SELECT 
        YEAR(tanggal_pinjam) AS tahun,
        WEEK(tanggal_pinjam, 1) AS minggu_ke,
        MIN(tanggal_pinjam) AS tanggal_awal,
        MAX(tanggal_pinjam) AS tanggal_akhir,
        COUNT(*) AS total
    FROM peminjaman
    $where
    GROUP BY tahun, minggu_ke
    ORDER BY tahun DESC, minggu_ke DESC
");

// Query tambahan untuk super_admin: perbandingan jenis per minggu
$qJenis = null;
if ($_SESSION['role'] == 'super_admin') {
    $qJenis = $con->query("
        SELECT 
            YEAR(tanggal_pinjam) AS tahun,
            WEEK(tanggal_pinjam, 1) AS minggu_ke,
            jenis,
            MIN(tanggal_pinjam) AS tanggal_awal,
            MAX(tanggal_pinjam) AS tanggal_akhir,
            COUNT(*) AS total
        FROM peminjaman
        GROUP BY tahun, minggu_ke, jenis
        ORDER BY tahun DESC, minggu_ke DESC
    ");
}
?>

<div class="container mt-4">
    <h3 class="mb-3">📆 Laporan Peminjaman Mingguan</h3>
    <p class="text-muted">
        Menampilkan jumlah peminjaman tiap minggu 
        <?= $_SESSION['role'] == 'super_admin' ? 'untuk semua jenis (ruangan & kendaraan).' : 'berdasarkan jenis yang dikelola.' ?>
    </p>

    <button id="downloadCsv" class="btn btn-sm btn-success mb-3">
        ⬇️ Download CSV
    </button>

    <div class="card shadow-sm mb-4 rounded-4">
        <div class="card-body">
            <h5 class="card-title">📊 Laporan Berdasarkan Minggu</h5>
            <table id="laporanTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Minggu Ke-</th>
                        <th>Rentang Tanggal</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $q->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['minggu_ke']) ?></td>
                            <td>
                                <?= date('d M', strtotime($row['tanggal_awal'])) ?> - 
                                <?= date('d M Y', strtotime($row['tanggal_akhir'])) ?>
                            </td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($_SESSION['role'] == 'super_admin' && $qJenis): ?>
    <div class="card shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="card-title">📈 Laporan Perbandingan Jenis Peminjaman</h5>
            <p class="text-muted">Menampilkan jumlah peminjaman tiap minggu berdasarkan jenis (ruangan vs kendaraan).</p>
            <table id="laporanJenisTable" class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tahun</th>
                        <th>Minggu Ke-</th>
                        <th>Rentang Tanggal</th>
                        <th>Jenis</th>
                        <th>Jumlah Peminjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qJenis->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                            <td><?= htmlspecialchars($row['minggu_ke']) ?></td>
                            <td>
                                <?= date('d M', strtotime($row['tanggal_awal'])) ?> - 
                                <?= date('d M Y', strtotime($row['tanggal_akhir'])) ?>
                            </td>
                            <td><?= ucfirst(htmlspecialchars($row['jenis'])) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Script Export ke CSV -->
<script>
document.getElementById("downloadCsv").addEventListener("click", function () {
    const tables = document.querySelectorAll("table");
    let csv = [];

    tables.forEach((table, index) => {
        csv.push("");
        csv.push(index === 0 ? "Laporan Mingguan" : "Laporan Berdasarkan Jenis");
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
    link.download = "laporan_mingguan.csv";
    link.href = window.URL.createObjectURL(csvFile);
    link.click();
});
</script>

<?php include("../includes/footer.php"); ?>
