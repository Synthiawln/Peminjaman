<?php
session_start();
include_once("../koneksi.php");

// authentication
$adminRoles = ['super_admin', 'admin_ruangan', 'admin_kendaraan'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $adminRoles)) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Laporan Mingguan";
include("../includes/header.php");
include("../includes/navbar.php");

// Ambil data laporan dari database (berdasarkan minggu dan tahun)
$q = $con->query("
    SELECT 
        YEAR(tanggal_pinjam) AS tahun,
        WEEK(tanggal_pinjam, 1) AS minggu_ke, 
        MIN(tanggal_pinjam) AS tanggal_awal,
        MAX(tanggal_pinjam) AS tanggal_akhir,
        COUNT(*) AS total
    FROM peminjaman
    GROUP BY tahun, minggu_ke
    ORDER BY tahun DESC, minggu_ke DESC
");
?>

<div class="container mt-4">
    <h3 class="mb-3">📆 Laporan Peminjaman Mingguan</h3>
    <p class="text-muted">Menampilkan jumlah peminjaman tiap minggu berdasarkan tanggal pinjam.</p>

    <button id="downloadCsv" class="btn btn-sm btn-success mb-3">
        ⬇️ Download CSV
    </button>

    <div class="card shadow-sm">
        <div class="card-body">
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
                                <?= date('d M', strtotime($row['tanggal_awal'])) ?> 
                                - <?= date('d M Y', strtotime($row['tanggal_akhir'])) ?>
                            </td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script Export ke CSV -->
<script>
document.getElementById("downloadCsv").addEventListener("click", function () {
    const table = document.getElementById("laporanTable");
    let csv = [];
    for (let i = 0; i < table.rows.length; i++) {
        let row = [], cols = table.rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/"/g, '""'); // escape quotes
            row.push('"' + data + '"');
        }
        csv.push(row.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    const link = document.createElement("a");
    link.download = "laporan_mingguan.csv";
    link.href = window.URL.createObjectURL(csvFile);
    link.click();
});
</script>

<?php include("../includes/footer.php"); ?>
