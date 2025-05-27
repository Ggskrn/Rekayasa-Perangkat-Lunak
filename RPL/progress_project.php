<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'projects.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? 0,
        'title' => $_POST['title'] ?? '',
        'detail' => $_POST['detail'] ?? '',
        'status' => $_POST['status'] ?? '',
        'progress' => $_POST['progress'] ?? 0,
        'client_id' => $_POST['client_id'] ?? null
    ];

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = basename($_FILES['attachment']['name']);
        $filepath = $uploadDir . uniqid() . '_' . $filename;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
            // Save attachment to database
            $note = $_POST['note'] ?? '';
            $project_id = $data['id'] ?: $conn->insert_id;
            
            add_attachment($project_id, $filename, $filepath, $note);
        }
    }

    if (save_project($data)) {
        $_SESSION['success_message'] = "Project berhasil disimpan!";
        header("Location: index.php?view=progress");
        exit();
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan project. Silakan coba lagi.";
        header("Location: index.php?view=progress");
        exit();
    }
}

// Redirect if accessed directly
header("Location: index.php");
exit();
?>