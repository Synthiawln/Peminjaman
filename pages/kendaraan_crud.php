<?php
session_start();
include_once("../koneksi.php");

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin_kendaraan'])) {
    header("Location: ../login.php");
    exit();
}

// Handle POST 'tambah' before any output
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_kendaraan'];
    $plat = $_POST['no_polisi'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $kode = $_POST['kode_barang'];

    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fotoName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFile = $targetDir . $fotoName;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile);
        $foto = "uploads/" . $fotoName; // simpan path relatif
    }

    $stmt = $con->prepare("INSERT INTO kendaraan (kode_barang, nama_kendaraan, no_polisi, status, keterangan, foto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $kode, $nama, $plat, $status, $keterangan, $foto);
    $stmt->execute();

    header("Location: kendaraan_crud.php");
    exit;
}

// Handle POST 'edit' before any output
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_kendaraan'];
    $plat = $_POST['no_polisi'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $foto = $_POST['foto_lama'];
    $kode = $_POST['kode_barang'];

    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fotoName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFile = $targetDir . $fotoName;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile);
        $foto = "uploads/" . $fotoName;
    }

    $stmt = $con->prepare("UPDATE kendaraan SET kode_barang=?, nama_kendaraan=?, no_polisi=?, status=?, keterangan=?, foto=? WHERE id=?");
    $stmt->bind_param("ssssssi", $kode, $nama, $plat, $status, $keterangan, $foto, $id);
    $stmt->execute();

    header("Location: kendaraan_crud.php");
    exit;
}

// Handle GET 'hapus' before any output
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $con->query("DELETE FROM kendaraan WHERE id='$id'");
    header("Location: kendaraan_crud.php");
    exit;
}

include("../includes/header.php");
include("../includes/navbar.php");

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
?>

<div class="container mt-4">
    <h3 class="mb-3">Kelola Data Kendaraan</h3>
    <div class="mb-3">
        <a href="?action=tambah" class="btn btn-success">+ Tambah Kendaraan</a>
        <a href="../modules/admin/admin_kendaraan.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($action == 'tambah'): ?>
        <div class="card">
            <div class="card-header bg-dark text-white">Tambah Kendaraan</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-2">
                        <label>Kode Barang</label>
                        <input type="text" name="kode_barang" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Nama Kendaraan</label>
                        <input type="text" name="nama_kendaraan" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Nomor Polisi</label>
                        <input type="text" name="no_polisi" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="tersedia">Tersedia</option>
                            <option value="dipinjam">Dipinjam</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Masukkan deskripsi kendaraan..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Upload Foto</label>
                        <input type="file" name="foto" class="form-control">
                    </div>
                    <button name="tambah" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>

    <?php elseif ($action == 'edit' && $id): 
        $k = $con->query("SELECT * FROM kendaraan WHERE id='$id'")->fetch_assoc();
    ?>
        <div class="card">
            <div class="card-header bg-warning text-dark">Edit Kendaraan</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <input type="hidden" name="foto_lama" value="<?= $k['foto'] ?>">
                    <div class="mb-2">
                        <label>Kode Barang</label>
                        <input type="text" name="kode_barang" class="form-control" value="<?= $k['kode_barang'] ?>">
                    </div>
                    <div class="mb-2">
                        <label>Nama Kendaraan</label>
                        <input type="text" name="nama_kendaraan" class="form-control" value="<?= $k['nama_kendaraan'] ?>">
                    </div>
                    <div class="mb-2">
                        <label>Nomor Polisi</label>
                        <input type="text" name="no_polisi" class="form-control" value="<?= $k['no_polisi'] ?>">
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="tersedia" <?= $k['status']=='tersedia'?'selected':'' ?>>Tersedia</option>
                            <option value="dipinjam" <?= $k['status']=='dipinjam'?'selected':'' ?>>Dipinjam</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"><?= $k['keterangan'] ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Foto</label><br>
                        <?php if ($k['foto']): ?>
                            <img src="../<?= $k['foto'] ?>" width="120" class="mb-2 rounded">
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control">
                    </div>
                    <button name="edit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Foto</th>
                    <th>Kode Barang</th>
                    <th>Nama</th>
                    <th>Plat</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = $con->query("SELECT * FROM kendaraan ORDER BY id DESC");
            while($d = $res->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td>
                        <?php if ($d['foto']): ?>
                            <img src="../<?= $d['foto'] ?>" width="80" class="rounded">
                        <?php else: ?>
                            <span class="text-muted">Tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['kode_barang']) ?></td>
                    <td><?= htmlspecialchars($d['nama_kendaraan']) ?></td>
                    <td><?= htmlspecialchars($d['no_polisi']) ?></td>
                    <td>
                        <span class="badge bg-<?= $d['status']=='tersedia'?'success':'warning' ?>">
                            <?= ucfirst($d['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($d['keterangan']) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?hapus=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>
