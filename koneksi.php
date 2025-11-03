<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "peminjaman";

$con = new mysqli($host, $user, $pass, $db);

if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}
?>
