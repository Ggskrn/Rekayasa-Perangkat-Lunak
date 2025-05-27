<?php
require_once 'config.php';

// Fungsi login
function login($username, $password) {
    global $conn;
    
    $username = clean_input($username);
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['client_id'] = $user['client_id'];
            return true;
        }
    }
    return false;
}

// Fungsi logout
function logout() {
    session_unset();
    session_destroy();
}

// Fungsi cek login
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fungsi cek role
function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

function is_engineer() {
    return is_logged_in() && $_SESSION['role'] === 'engineer';
}

function is_client() {
    return is_logged_in() && $_SESSION['role'] === 'client';
}

// Fungsi untuk mendapatkan data user saat ini
function get_current_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nama_lengkap' => $_SESSION['nama_lengkap'],
            'role' => $_SESSION['role'],
            'client_id' => $_SESSION['client_id']
        ];
    }
    return null;
}
?>