<?php
session_start();
include_once("../koneksi.php");

// authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Kelola User";
include("../includes/header.php");
include("../includes/navbar.php");

// ============ PROSES SIMPAN / UPDATE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($_POST['id'])) {
        // Update user
        $id = $_POST['id'];
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $con->query("UPDATE user SET nama='$nama', username='$username', role='$role', password='$hash' WHERE id=$id");
        } else {
            $con->query("UPDATE user SET nama='$nama', username='$username', role='$role' WHERE id=$id");
        }
        $_SESSION['msg'] = "Data user berhasil diperbarui.";
    } else {
        // Tambah user baru
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("INSERT INTO user (nama, username, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $nama, $username, $hash, $role);
        $stmt->execute();
        $_SESSION['msg'] = "User baru berhasil ditambahkan.";
    }
    header("Location: user_crud.php");
    exit();
}

// ============ HAPUS USER ============
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $con->query("DELETE FROM user WHERE id=$id");
    $_SESSION['msg'] = "User berhasil dihapus.";
    header("Location: user_crud.php");
    exit();
}

// ============ EDIT USER ============
$editUser = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $con->query("SELECT * FROM user WHERE id=$id");
    $editUser = $res->fetch_assoc();
}

// Pesan notifikasi
if (isset($_SESSION['msg'])) {
    echo "<div class='alert alert-success text-center'>" . $_SESSION['msg'] . "</div>";
    unset($_SESSION['msg']);
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Kelola User</h3>
        <a href="../modules/admin/superadmin.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <!-- FORM TAMBAH / EDIT USER -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">
            <?= $editUser ? 'Edit User' : 'Tambah User Baru'; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?= $editUser['id']; ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($editUser['nama'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($editUser['username'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <?= $editUser ? '<small>(kosongkan jika tidak diubah)</small>' : ''; ?></label>
                    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required'; ?>>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <?php
                        $roles = ['super_admin', 'admin_ruangan', 'admin_kendaraan', 'user'];
                        $selectedRole = $editUser['role'] ?? '';
                        foreach ($roles as $r) {
                            $sel = ($selectedRole === $r) ? 'selected' : '';
                            echo "<option value='$r' $sel>" . ucfirst(str_replace('_', ' ', $r)) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary"><?= $editUser ? 'Simpan Perubahan' : 'Tambah User'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- DAFTAR USER -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">Daftar User</div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Dibuat Pada</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = $con->query("SELECT * FROM user ORDER BY created_at DESC");
                    while ($u = $users->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $u['id']; ?></td>
                            <td><?= htmlspecialchars($u['nama']); ?></td>
                            <td><?= htmlspecialchars($u['username']); ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($u['role']); ?></span></td>
                            <td><?= htmlspecialchars($u['created_at']); ?></td>
                            <td class="text-center">
                                <a href="user_crud.php?edit=<?= $u['id']; ?>" class="btn btn-sm btn-warning me-1">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="user_crud.php?hapus=<?= $u['id']; ?>" 
                                   onclick="return confirm('Yakin ingin menghapus user ini?')"
                                   class="btn btn-sm btn-danger">
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

<?php include("../includes/footer.php"); ?>
