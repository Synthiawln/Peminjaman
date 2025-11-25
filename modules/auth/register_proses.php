<?php
include_once(__DIR__ . "/../../koneksi.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nip = trim($_POST['nip']);
    $confirm_password = trim($_POST['confirm_password']);

    
    if ($password !== $confirm_password) {
        header('Location: register.php?error=passwords_not_match');
        exit();
    }

    
    $check_username = $con->prepare("SELECT * FROM user WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result = $check_username->get_result();

    if ($result->num_rows > 0) {
        header('Location: register.php?error=username_taken');
        exit();
    }

    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    
    $stmt = $con->prepare("
        INSERT INTO user (nama, username, password, role, nip)
        VALUES (?, ?, ?, 'user', ?)
    ");
    $stmt->bind_param("ssss", $nama, $username, $hashed_password, $nip);

    if ($stmt->execute()) {
        header('Location: login.php');
        exit();
    } else {
        header('Location: register.php?error=registration_failed');
        exit();
    }
}
?>
