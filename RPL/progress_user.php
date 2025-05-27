<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'users.php';

if (!is_admin()) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? 0,
        'nama_lengkap' => $_POST['nama_lengkap'] ?? '',
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role'] ?? '',
        'client_id' => ($_POST['role'] === 'client') ? ($_POST['client_id'] ?? null) : null
    ];

    if (save_user($data)) {
        $_SESSION['success_message'] = "Pengguna berhasil disimpan!";
        header("Location: index.php?view=users");
        exit();
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan pengguna. Silakan coba lagi.";
        header("Location: index.php?view=users");
        exit();
    }
}

// Redirect if accessed directly
header("Location: index.php");
exit();
?>