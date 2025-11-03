<?php
session_start();
include_once("koneksi.php");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Peminjaman</title>
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

        /* Register Card */
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 85vh;
        }

        .register-card {
            background-color: #ffffff;
            border: 1px solid #100f0fff;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .register-card h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
        }

        .btn-register {
            background-color: #8c7a2b;
            border: none;
            border-radius: 8px;
        }

        .btn-register:hover {
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

<!-- Navbar mirip login -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="gambar/logo_BPK.png" alt="Logo">
            Sistem Peminjaman
        </a>
    </div>
</nav>

<!-- Register Form -->
<div class="register-container">
    <div class="register-card">
        <h3>Daftar Akun Baru</h3>

        <?php
        if (isset($_GET['error'])) {
            $error_message = match($_GET['error']) {
                'passwords_not_match' => "Password dan Konfirmasi Password tidak cocok!",
                'username_taken' => "Username sudah terdaftar!",
                default => "Terjadi kesalahan. Silakan coba lagi."
            };
            echo '<div class="alert alert-danger text-center" role="alert">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <form action="register_proses.php" method="POST">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Konfirmasi password">
            </div>
            <button type="submit" class="btn btn-register w-100 text-white">Daftar</button>
        </form>

        <p class="text-center mt-3 text-muted">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </p>
    </div>
</div>

<!-- Footer -->
<footer>
    © 2025 Sistem Peminjaman Ruangan & Kendaraan
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
