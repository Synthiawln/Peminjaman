<?php
session_start();

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$pageTitle = "Login Sistem Peminjaman";
$error_message = $_SESSION['error_message'] ?? "";
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar {
            background-color: #8c7a2b;
        }

        .navbar-brand {
            font-weight: 600;
            color: #fff !important;
        }

        .navbar img {
            height: 32px;
            margin-right: 10px;
        }

        /* Login Card */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 85vh;
        }

        .login-card {
            background-color: #ffffffff;
            border: 1px solid #100f0fff;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .login-card h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
        }

        .btn-login {
            background-color: #8c7a2b;
            border: none;
            border-radius: 8px;
        }

        .btn-login:hover {
            background-color: #6e6220;
        }

        /* Footer */
        footer {
            background-color: #8c7a2b;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- Navbar mirip dashboard tapi tanpa menu -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="gambar/logo_BPK.png" alt="Logo">
            Sistem Peminjaman
        </a>
    </div>
</nav>

<!-- Login Form -->
<div class="login-container">
    <div class="login-card">
        <h3>Login Sistem Peminjaman</h3>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="login_proses.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn btn-login w-100 text-white">Login</button>
        </form>

        <p class="text-center mt-3 text-muted">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </p>
    </div>
</div>

<!-- Footer sama seperti dashboard -->
<footer>
    © 2025 Sistem Peminjaman Ruangan & Kendaraan
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
