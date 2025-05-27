<?php
require_once 'config.php';
require_once 'auth.php';

// Fungsi untuk mendapatkan semua pengguna
function get_all_users() {
    global $conn;
    
    $query = "SELECT u.*, c.nama as client_name 
              FROM users u 
              LEFT JOIN clients c ON u.client_id = c.id 
              ORDER BY u.role, u.nama_lengkap";
    
    $result = $conn->query($query);
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan pengguna by ID
function get_user_by_id($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT u.*, c.nama as client_name 
                           FROM users u 
                           LEFT JOIN clients c ON u.client_id = c.id 
                           WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Fungsi untuk menambahkan/mengedit pengguna
function save_user($data) {
    global $conn;
    
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $nama_lengkap = clean_input($data['nama_lengkap']);
    $username = clean_input($data['username']);
    $role = clean_input($data['role']);
    $client_id = ($role === 'client' && isset($data['client_id'])) ? (int)$data['client_id'] : null;
    
    // Jika password diisi (untuk create atau update password)
    $password = isset($data['password']) && !empty($data['password']) ? 
                password_hash($data['password'], PASSWORD_DEFAULT) : null;
    
    if ($id > 0) {
        // Update existing user
        if ($password) {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ?, role = ?, client_id = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $nama_lengkap, $username, $role, $client_id, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ?, role = ?, client_id = ? WHERE id = ?");
            $stmt->bind_param("sssii", $nama_lengkap, $username, $role, $client_id, $id);
        }
    } else {
        // Insert new user (password wajib)
        if (!$password) return false;
        
        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, password, role, client_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nama_lengkap, $username, $password, $role, $client_id);
    }
    
    return $stmt->execute();
}

// Fungsi untuk menghapus pengguna
function delete_user($id) {
    global $conn;
    
    // Tidak boleh menghapus diri sendiri
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        return false;
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Fungsi untuk mendapatkan semua client
function get_all_clients() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM clients ORDER BY nama");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>