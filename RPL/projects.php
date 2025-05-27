<?php
require_once 'config.php';
require_once 'auth.php';

// Fungsi untuk mendapatkan semua proyek
function get_all_projects($search = '', $filter = 'all') {
    global $conn;
    $current_user = get_current_user();
    
    $query = "SELECT p.*, u.nama_lengkap as created_by_name, c.nama as client_name 
              FROM projects p 
              LEFT JOIN users u ON p.created_by = u.id 
              LEFT JOIN clients c ON p.client_id = c.id";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    // Filter untuk client
    if ($current_user && $current_user['role'] === 'client' && $current_user['client_id']) {
        $conditions[] = "p.client_id = ?";
        $params[] = $current_user['client_id'];
        $types .= 'i';
    }
    
    // Filter status
    if ($filter === 'ongoing') {
        $conditions[] = "p.progress < 100";
    } elseif ($filter === 'completed') {
        $conditions[] = "p.progress = 100";
    }
    
    // Search
    if (!empty($search)) {
        $conditions[] = "(p.title LIKE ? OR p.detail LIKE ? OR p.status LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    // Gabungkan kondisi
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan proyek by ID
function get_project_by_id($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT p.*, u.nama_lengkap as created_by_name, c.nama as client_name 
                           FROM projects p 
                           LEFT JOIN users u ON p.created_by = u.id 
                           LEFT JOIN clients c ON p.client_id = c.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Fungsi untuk menambahkan/mengedit proyek
function save_project($data) {
    global $conn;
    $current_user = get_current_user();
    
    if (!$current_user) return false;
    
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $title = clean_input($data['title']);
    $detail = clean_input($data['detail']);
    $status = clean_input($data['status']);
    $progress = (int)$data['progress'];
    $client_id = isset($data['client_id']) ? (int)$data['client_id'] : null;
    
    if ($id > 0) {
        // Update existing project
        $stmt = $conn->prepare("UPDATE projects SET title = ?, detail = ?, status = ?, progress = ?, client_id = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $title, $detail, $status, $progress, $client_id, $id);
    } else {
        // Insert new project
        $stmt = $conn->prepare("INSERT INTO projects (title, detail, status, progress, created_by, client_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $title, $detail, $status, $progress, $current_user['id'], $client_id);
    }
    
    return $stmt->execute();
}

// Fungsi untuk menghapus proyek
function delete_project($id) {
    global $conn;
    
    // Hanya admin yang bisa menghapus
    if (!is_admin()) return false;
    
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Fungsi untuk mendapatkan lampiran proyek
function get_project_attachments($project_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT a.*, u.nama_lengkap as uploaded_by_name 
                           FROM attachments a 
                           JOIN users u ON a.uploaded_by = u.id 
                           WHERE a.project_id = ? 
                           ORDER BY a.uploaded_at DESC");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk menambahkan lampiran
function add_attachment($project_id, $filename, $filepath, $note) {
    global $conn;
    $current_user = get_current_user();
    
    if (!$current_user) return false;
    
    $stmt = $conn->prepare("INSERT INTO attachments (project_id, filename, filepath, note, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $project_id, $filename, $filepath, $note, $current_user['id']);
    
    return $stmt->execute();
}

// Fungsi untuk mendapatkan statistik dashboard
function get_dashboard_stats() {
    global $conn;
    $current_user = get_current_user();
    
    $stats = [
        'total' => 0,
        'ongoing' => 0,
        'completed' => 0,
        'avg_progress' => 0
    ];
    
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN progress < 100 THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed,
                AVG(progress) as avg_progress
              FROM projects";
    
    // Filter untuk client
    if ($current_user && $current_user['role'] === 'client' && $current_user['client_id']) {
        $query .= " WHERE client_id = " . $current_user['client_id'];
    }
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total'] = $row['total'];
        $stats['ongoing'] = $row['ongoing'];
        $stats['completed'] = $row['completed'];
        $stats['avg_progress'] = round($row['avg_progress']);
    }
    
    return $stats;
}
?>