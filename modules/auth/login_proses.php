<?php
session_start();
include_once("../../koneksi.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Username dan password harus diisi.";
        header('Location: login.php');
        exit();
    }

    
    $stmt = $con->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        
        if (password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nip'] = $user['nip'];

            
            $adminRuangan = array('admin_ruangan');
            $adminKendaraan = array('admin_kendaraan');
            $admin = array('super_admin');
            if (in_array($user['role'], $adminRuangan)) {
                
                header('Location: ../admin/admin_ruangan.php');
            }
            elseif (in_array($user['role'], $adminKendaraan) ){
                
                header('Location: ../admin/admin_kendaraan.php');
            } 
            elseif (in_array($user['role'], $admin) ){
                
                header('Location:../admin/superadmin.php');
            }
            else {
                header('Location: ../../index.php');
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Password atau Email salah!";
        }
    } else {
        $_SESSION['error_message'] = "Username tidak ditemukan!";
    }

    header('Location: login.php');
    exit();
}
?>
