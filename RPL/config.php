<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sinar_baja_bumi');

// Membuat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Fungsi untuk membersihkan input
function clean_input($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data)));
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Mulai session
session_start();
?>