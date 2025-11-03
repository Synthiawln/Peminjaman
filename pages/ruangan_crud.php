<?php
session_start();
require_once("../koneksi.php");

// authentication
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

include("../includes/header.php");
include("../includes/navbar.php");

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// === TAMBAH DATA ===
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_ruangan'];
    $lokasi = $_POST['lokasi'];
    $kapasitas = $_POST['kapasitas'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];

    $foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../uploads/ruangan/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fotoName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFile = $targetDir . $fotoName;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile);
        $foto = "uploads/ruangan/" . $fotoName;
    }

    $stmt = $con->prepare("INSERT INTO ruangan (nama_ruangan, lokasi, kapasitas, status, keterangan, foto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $nama, $lokasi, $kapasitas, $status, $keterangan, $foto);
    $stmt->execute();

    echo "<script>alert('Data ruangan berhasil ditambahkan!');window.location='ruangan_crud.php';</script>";
    exit;
}

// === EDIT DATA ===
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_ruangan'];
    $lokasi = $_POST['lokasi'];
    $kapasitas = $_POST['kapasitas'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];

    $foto = $_POST['foto_lama'];
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../uploads/ruangan/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fotoName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFile = $targetDir . $fotoName;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile);
        $foto = "uploads/ruangan/" . $fotoName;
    }

    $stmt = $con->prepare("UPDATE ruangan SET nama_ruangan=?, lokasi=?, kapasitas=?, status=?, keterangan=?, foto=? WHERE id=?");
    $stmt->bind_param("ssisssi", $nama, $lokasi, $kapasitas, $status, $keterangan, $foto, $id);
    $stmt->execute();

    echo "<script>alert('Data ruangan berhasil diupdate!');window.location='ruangan_crud.php';</script>";
    exit;
}

// === HAPUS DATA ===
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $con->prepare("DELETE FROM ruangan WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>alert('Data ruangan berhasil dihapus!');window.location='ruangan_crud.php';</script>";
    exit;
}
?>

<div class="container mt-4">
    <h3>Kelola Data Ruangan</h3>
    <a href="?action=tambah" class="btn btn-success mb-3">+ Tambah Ruangan</a>
    <a href="../modules/admin/admin_ruangan.php" class="btn btn-secondary mb-3">← Kembali</a>

    <?php if ($action == 'tambah'): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="nama_ruangan" class="form-control mb-2" placeholder="Nama Ruangan" required>
            <input type="text" name="lokasi" class="form-control mb-2" placeholder="Lokasi" required>
            <input type="number" name="kapasitas" class="form-control mb-2" placeholder="Kapasitas" required>
            <select name="status" class="form-control mb-2">
                <option value="tersedia">Tersedia</option>
                <option value="dipinjam">Dipinjam</option>
            </select>
            <textarea name="keterangan" class="form-control mb-2" placeholder="Keterangan tambahan..."></textarea>
            <input type="file" name="foto" class="form-control mb-3" accept="image/*">
            <button name="tambah" class="btn btn-primary">Simpan</button>
        </form>

    <?php elseif ($action == 'edit' && $id): 
        $r = $con->query("SELECT * FROM ruangan WHERE id='$id'")->fetch_assoc();
    ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input type="hidden" name="foto_lama" value="<?= $r['foto'] ?>">
            <input type="text" name="nama_ruangan" class="form-control mb-2" value="<?= htmlspecialchars($r['nama_ruangan']) ?>" required>
            <input type="text" name="lokasi" class="form-control mb-2" value="<?= htmlspecialchars($r['lokasi']) ?>" required>
            <input type="number" name="kapasitas" class="form-control mb-2" value="<?= htmlspecialchars($r['kapasitas']) ?>" required>
            <select name="status" class="form-control mb-2">
                <option value="tersedia" <?= $r['status']=='tersedia'?'selected':'' ?>>Tersedia</option>
                <option value="dipinjam" <?= $r['status']=='dipinjam'?'selected':'' ?>>Dipinjam</option>
            </select>
            <textarea name="keterangan" class="form-control mb-2"><?= htmlspecialchars($r['keterangan']) ?></textarea>
            <div class="mb-2">
                <label>Foto Saat Ini:</label><br>
                <?php if ($r['foto'] && file_exists("../".$r['foto'])): ?>
                    <img src="../<?= $r['foto'] ?>" width="150">
                <?php else: ?>
                    <span class="text-muted">Tidak ada foto</span>
                <?php endif; ?>
            </div>
            <input type="file" name="foto" class="form-control mb-3" accept="image/*">
            <button name="edit" class="btn btn-primary">Update</button>
        </form>

    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Ruangan</th>
                    <th>Lokasi</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = $con->query("SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
            while($d=$res->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= htmlspecialchars($d['nama_ruangan']) ?></td>
                    <td><?= htmlspecialchars($d['lokasi']) ?></td>
                    <td><?= htmlspecialchars($d['kapasitas']) ?></td>
                    <td><span class="badge bg-<?= $d['status']=='tersedia'?'success':'danger' ?>"><?= ucfirst($d['status']) ?></span></td>
                    <td><?= htmlspecialchars($d['keterangan']) ?></td>
                    <td>
                        <?php if ($d['foto'] && file_exists("../".$d['foto'])): ?>
                            <img src="../<?= $d['foto'] ?>" width="80">
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?action=edit&id=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?hapus=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus ruangan ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>
