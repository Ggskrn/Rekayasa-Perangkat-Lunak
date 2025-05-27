<?php
require_once 'auth.php';
require_once 'projects.php';
require_once 'users.php';

// Jika belum login, redirect ke halaman login
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$current_user = get_current_user();

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header("Location: login.php");
    exit();
}

// Handle view switching
$view = 'dashboard';
if (isset($_GET['view'])) {
    $allowed_views = ['dashboard', 'progress', 'users', 'about', 'logout'];
    if (in_array($_GET['view'], $allowed_views)) {
        $view = $_GET['view'];
    }
}

// Get page title based on view
$page_titles = [
    'dashboard' => 'Dashboard',
    'progress' => 'Project',
    'users' => 'Manajemen User',
    'about' => 'Tentang Kami',
    'logout' => 'Logout'
];

$page_title = $page_titles[$view];

// Get data for views
$projects = [];
$users = [];
$clients = [];
$stats = [];

if ($view === 'progress') {
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'all';
    $projects = get_all_projects($search, $filter);
} elseif ($view === 'users' && is_admin()) {
    $users = get_all_users();
    $clients = get_all_clients();
} elseif ($view === 'dashboard') {
    $stats = get_dashboard_stats();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $page_title; ?> - PT Sinar Baja Bumi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
      :root {
        --primary-color: #8B6B4A;
        --secondary-color: #6F4E37;
        --bg-color: #F5F3EE;
        --card-color: #FFFFFF;
        --text-color: #333333;
        --sidebar-color: #5D4037;
        --success-color: #4E8C48;
        --danger-color: #A44A3F;
        --warning-color: #D4A017;
        --gold-accent: #C9B38B;
        --dark-brown: #3E2723;
      }

      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
        min-height: 100vh;
      }

      /* Elegant Loading Screen */
      .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(62, 39, 35, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease;
      }

      .loading-screen.active {
        opacity: 1;
        pointer-events: all;
      }

      .loader {
        width: 80px;
        height: 80px;
        border: 8px solid var(--gold-accent);
        border-bottom-color: transparent;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
        margin-bottom: 20px;
      }

      .loading-text {
        color: var(--gold-accent);
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        letter-spacing: 2px;
        margin-top: 20px;
      }

      @keyframes rotation {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }

      /* Main App Layout */
      .app-container {
        display: flex;
        min-height: 100vh;
        background-image: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-blend-mode: overlay;
        background-color: rgba(245, 243, 238, 0.9);
      }

      /* Sidebar */
      .sidebar {
        width: 280px;
        background-color: var(--sidebar-color);
        color: white;
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 1000;
        box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
      }

      .sidebar.collapsed {
        transform: translateX(-280px);
      }

      .sidebar-logo {
        text-align: center;
        margin-bottom: 30px;
        color: var(--gold-accent);
        font-size: 28px;
        font-weight: bold;
        font-family: 'Playfair Display', serif;
        letter-spacing: 1px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--gold-accent);
      }

      .sidebar-menu {
        display: flex;
        flex-direction: column;
        gap: 15px;
      }

      .sidebar-menu button {
        background: none;
        border: none;
        color: white;
        text-align: left;
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.4s ease;
        border-radius: 5px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 500;
      }

      .sidebar-menu button:hover {
        background-color: rgba(201, 179, 139, 0.2);
        transform: translateX(10px);
      }

      .sidebar-menu button i {
        width: 24px;
        text-align: center;
        font-size: 18px;
        color: var(--gold-accent);
      }

      /* Main Content */
      .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: auto;
        transition: margin-left 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        background-color: rgba(245, 243, 238, 0.85);
      }

      .sidebar:not(.collapsed) + .main-content {
        margin-left: 280px;
      }

      /* Page Header */
      .page-header {
        background-color: var(--dark-brown);
        color: white;
        padding: 20px 40px;
        font-size: 24px;
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Playfair Display', serif;
        letter-spacing: 1px;
      }

      .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
      }

      .user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: var(--gold-accent);
        color: var(--dark-brown);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        font-family: 'Playfair Display', serif;
        transition: all 0.3s ease;
      }

      .user-avatar:hover {
        transform: scale(1.1);
      }

      .user-details {
        display: flex;
        flex-direction: column;
      }

      .user-name {
        font-size: 16px;
        font-weight: bold;
        font-family: 'Montserrat', sans-serif;
      }

      .user-role {
        font-size: 13px;
        opacity: 0.8;
        font-family: 'Montserrat', sans-serif;
      }

      /* View Content */
      .view-content {
        padding: 40px;
        flex: 1;
        animation: fadeIn 0.8s ease;
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      /* Search Bar */
      .search-bar {
        display: flex;
        margin-bottom: 30px;
        gap: 15px;
      }

      .search-bar input {
        flex: 1;
        padding: 12px 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 15px;
        font-family: 'Montserrat', sans-serif;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.8);
      }

      .search-bar input:focus {
        outline: none;
        border-color: var(--gold-accent);
        box-shadow: 0 0 0 2px rgba(201, 179, 139, 0.3);
      }

      .search-bar button {
        padding: 12px 25px;
        background-color: var(--dark-brown);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .search-bar button:hover {
        background-color: var(--primary-color);
        transform: translateY(-2px);
      }

      /* Project Styles */
      .project {
        background: var(--card-color);
        margin-top: 20px;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.4s ease;
        border-left: 4px solid var(--gold-accent);
      }

      .project:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      }

      .project.ongoing {
        border-left-color: var(--warning-color);
      }

      .project.completed {
        border-left-color: var(--success-color);
      }

      .project-info {
        flex: 1;
      }

      .project-info h3 {
        color: var(--dark-brown);
        font-family: 'Playfair Display', serif;
        margin-bottom: 8px;
        font-size: 20px;
      }

      .project-info p {
        color: var(--text-color);
        margin-bottom: 8px;
        font-size: 14px;
      }

      .project-info .status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
      }

      .project-info .status.ongoing {
        background-color: rgba(212, 160, 23, 0.1);
        color: var(--warning-color);
      }

      .project-info .status.completed {
        background-color: rgba(78, 140, 72, 0.1);
        color: var(--success-color);
      }

      .project-progress {
        text-align: right;
        min-width: 120px;
      }

      .project-progress .percentage {
        font-size: 24px;
        font-weight: bold;
        font-family: 'Playfair Display', serif;
        color: var(--dark-brown);
      }

      .project-progress .progress-bar-container {
        height: 8px;
        background-color: #e0e0e0;
        border-radius: 4px;
        margin: 8px 0;
        overflow: hidden;
      }

      .project-progress .progress-bar {
        height: 100%;
        border-radius: 4px;
        background-color: var(--gold-accent);
        transition: width 0.6s ease;
      }

      .project-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        justify-content: flex-end;
      }

      /* Info Text */
      .info {
        font-size: 16px;
        line-height: 1.8;
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
        padding: 30px;
        background: var(--card-color);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        font-family: 'Montserrat', sans-serif;
      }

      /* View Toggle */
      .view {
        display: none;
        flex-direction: column;
        height: 100%;
        position: relative;
      }

      .view.active {
        display: flex;
      }

      /* Dashboard Cards */
      .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
      }

      .dashboard-card {
        background: var(--card-color);
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.4s ease;
        cursor: pointer;
        border-top: 4px solid var(--gold-accent);
        animation: cardEnter 0.6s ease;
      }

      .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
      }

      @keyframes cardEnter {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .dashboard-card h3 {
        color: var(--dark-brown);
        font-family: 'Playfair Display', serif;
        margin-bottom: 15px;
        font-size: 20px;
        letter-spacing: 0.5px;
      }

      .dashboard-card .value {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 15px;
        font-family: 'Playfair Display', serif;
        color: var(--primary-color);
      }

      .dashboard-card .progress-container {
        height: 10px;
        background-color: #e0e0e0;
        border-radius: 5px;
        margin-bottom: 15px;
        overflow: hidden;
      }

      .dashboard-card .progress-bar {
        height: 100%;
        border-radius: 5px;
        background-color: var(--gold-accent);
        transition: width 1s ease;
      }

      .dashboard-card .info-text {
        font-size: 13px;
        color: #666;
        font-family: 'Montserrat', sans-serif;
      }

      /* Button Styles */
      .btn {
        min-width: 120px;
        height: 45px;
        color: #fff;
        padding: 5px 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        outline: none;
        border-radius: 5px;
        border: none;
        background: var(--dark-brown);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-family: 'Montserrat', sans-serif;
        overflow: hidden;
      }

      .btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(255,255,255,0.1), rgba(255,255,255,0));
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
      }

      .btn:hover::after {
        opacity: 1;
      }

      .btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .btn i {
        margin-right: 8px;
      }

      .btn-danger {
        background: var(--danger-color);
      }

      .btn-success {
        background: var(--success-color);
      }

      /* Form Styles */
      .form-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: var(--card-color);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        z-index: 1001;
        display: none;
        width: 90%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalEnter 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border-top: 5px solid var(--gold-accent);
      }

      @keyframes modalEnter {
        from { opacity: 0; transform: translate(-50%, -60%); }
        to { opacity: 1; transform: translate(-50%, -50%); }
      }

      .form-container.active {
        display: block;
      }

      .form-container h3 {
        color: var(--dark-brown);
        margin-bottom: 25px;
        font-size: 24px;
        font-family: 'Playfair Display', serif;
        letter-spacing: 0.5px;
      }

      .form-group {
        margin-bottom: 20px;
      }

      .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark-brown);
        font-family: 'Montserrat', sans-serif;
      }

      .form-container input,
      .form-container textarea,
      .form-container select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 15px;
        font-family: 'Montserrat', sans-serif;
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.8);
      }

      .form-container input:focus,
      .form-container textarea:focus,
      .form-container select:focus {
        outline: none;
        border-color: var(--gold-accent);
        box-shadow: 0 0 0 3px rgba(201, 179, 139, 0.2);
      }

      .form-container textarea {
        min-height: 100px;
        resize: vertical;
      }

      .form-section {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
      }

      .form-section h4 {
        color: var(--dark-brown);
        margin-bottom: 15px;
        font-size: 18px;
        font-family: 'Playfair Display', serif;
      }

      .file-upload {
        position: relative;
        margin: 20px 0;
      }

      .file-upload input[type="file"] {
        display: none;
      }

      .file-upload label {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background-color: var(--bg-color);
        border: 1px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
      }

      .file-upload label:hover {
        background-color: #e8e5dd;
      }

      .file-upload label i {
        margin-right: 8px;
        color: var(--primary-color);
      }

      #file-chosen {
        margin-left: 10px;
        font-size: 14px;
        color: #666;
      }

      .upload-btn {
        background-color: var(--dark-brown);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 15px;
        margin-top: 10px;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
      }

      .upload-btn:hover {
        background-color: var(--primary-color);
      }

      .save-btn {
        background-color: var(--success-color);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        width: 100%;
        margin-top: 20px;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
        letter-spacing: 0.5px;
      }

      .save-btn:hover {
        background-color: #3d6e38;
      }

      .delete-btn {
        background-color: var(--danger-color);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
      }

      .delete-btn:hover {
        background-color: #8c3d35;
      }

      /* Overlay for form */
      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(62, 39, 35, 0.7);
        z-index: 1000;
        display: none;
        animation: fadeIn 0.3s ease;
      }

      .overlay.active {
        display: block;
      }

      /* Close button for form */
      .close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        transition: all 0.3s ease;
      }

      .close-btn:hover {
        color: var(--danger-color);
        transform: rotate(90deg);
      }

      /* User Management Styles */
      .user-management {
        margin-top: 30px;
      }

      .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: var(--card-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 10px;
        overflow: hidden;
      }

      .user-table th, .user-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
      }

      .user-table th {
        background-color: var(--dark-brown);
        color: white;
        font-family: 'Playfair Display', serif;
      }

      .user-table tr:hover {
        background-color: rgba(201, 179, 139, 0.1);
      }

      .user-table .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
      }

      .user-table .role-admin {
        background-color: rgba(139, 107, 74, 0.1);
        color: var(--primary-color);
      }

      .user-table .role-engineer {
        background-color: rgba(78, 140, 72, 0.1);
        color: var(--success-color);
      }

      .user-table .role-client {
        background-color: rgba(164, 74, 63, 0.1);
        color: var(--danger-color);
      }

      /* Report Styles */
      .report-container {
        background-color: var(--card-color);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-top: 30px;
      }

      .report-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
      }

      .report-title {
        font-family: 'Playfair Display', serif;
        color: var(--dark-brown);
        font-size: 24px;
      }

      .download-btn {
        background-color: var(--dark-brown);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-family: 'Montserrat', sans-serif;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .download-btn:hover {
        background-color: var(--primary-color);
      }

      .report-table {
        width: 100%;
        border-collapse: collapse;
      }

      .report-table th, .report-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
      }

      .report-table th {
        background-color: var(--bg-color);
        font-family: 'Playfair Display', serif;
        color: var(--dark-brown);
      }

      .report-table tr:last-child td {
        border-bottom: none;
      }

      .report-table .file-link {
        color: var(--primary-color);
        text-decoration: none;
        transition: all 0.3s ease;
      }

      .report-table .file-link:hover {
        text-decoration: underline;
      }

      /* Responsive styles */
      @media (max-width: 768px) {
        .sidebar {
          position: fixed;
          left: -280px;
          top: 0;
          height: 100%;
        }

        .sidebar.open {
          left: 0;
        }

        .sidebar:not(.collapsed) + .main-content {
          margin-left: 0;
        }

        .hamburger-menu {
          display: block;
        }

        .dashboard-cards {
          grid-template-columns: 1fr;
        }

        .view-content {
          padding: 20px;
        }

        .page-header {
          padding: 15px 20px;
        }

        .search-bar {
          flex-direction: column;
        }

        .search-bar button {
          width: 100%;
        }

        .form-container {
          width: 95%;
          padding: 20px;
        }

        .user-table, .report-table {
          display: block;
          overflow-x: auto;
        }
      }

      /* Background images for different views */
      .dashboard {
        background-image: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      }

      .progress {
        background-image: url('https://images.unsplash.com/photo-1507679799987-c73779587ccf?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      }

      .about {
        background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      }

      .logout {
        background-image: url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      }
    </style>
  </head>
  <body>
    <div class="app-container">
      <div class="loading-screen">
        <div class="loader"></div>
        <div class="loading-text">Memuat...</div>
      </div>

      <button class="hamburger-menu" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="sidebar">
        <div class="sidebar-logo">PT Sinar Baja Bumi</div>
        <div class="sidebar-menu">
          <button onclick="switchView('dashboard')">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </button>
          <button onclick="switchView('progress')">
            <i class="fas fa-project-diagram"></i> Project
          </button>
          <?php if (is_admin()): ?>
            <button onclick="switchView('users')">
              <i class="fas fa-users-cog"></i> Manajemen User
            </button>
          <?php endif; ?>
          <button onclick="switchView('about')">
            <i class="fas fa-info-circle"></i> Tentang Kami
          </button>
          <button onclick="window.location.href='?action=logout'">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
        </div>
      </div>

      <div class="main-content">
        <div class="page-header">
          <div id="page-title"><?php echo $page_title; ?></div>
          <div class="user-info">
            <div class="user-avatar" id="userAvatar">
              <?php 
                $initials = '';
                if ($current_user) {
                  $names = explode(' ', $current_user['nama_lengkap']);
                  foreach ($names as $name) {
                    $initials .= strtoupper(substr($name, 0, 1));
                  }
                  $initials = substr($initials, 0, 2);
                }
                echo $initials;
              ?>
            </div>
            <div class="user-details">
              <div class="user-name"><?php echo $current_user ? $current_user['nama_lengkap'] : ''; ?></div>
              <div class="user-role">
                <?php 
                  if ($current_user) {
                    echo $current_user['role'] === 'engineer' ? 'Pegawai' : 
                         ($current_user['role'] === 'admin' ? 'Admin' : 'Client');
                  }
                ?>
              </div>
            </div>
          </div>
        </div>

        <div class="view <?php echo $view === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
          <div class="view-content">
            <div class="dashboard-cards">
              <div class="dashboard-card">
                <h3>Total Projects</h3>
                <div class="value" id="totalProjects"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="info-text">All projects in the system</div>
              </div>
              <div class="dashboard-card">
                <h3>Ongoing Projects</h3>
                <div class="value" id="ongoingProjects"><?php echo $stats['ongoing'] ?? 0; ?></div>
                <div class="info-text">Projects currently in progress</div>
              </div>
              <div class="dashboard-card">
                <h3>Completed Projects</h3>
                <div class="value" id="completedProjects"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="info-text">Successfully finished projects</div>
              </div>
              <div class="dashboard-card">
                <h3>Project Progress</h3>
                <div class="value" id="avgProgress"><?php echo $stats['avg_progress'] ?? 0; ?>%</div>
                <div class="progress-container">
                  <div class="progress-bar" id="progressBar" style="width: <?php echo $stats['avg_progress'] ?? 0; ?>%"></div>
                </div>
                <div class="info-text">Average completion rate</div>
              </div>
            </div>
          </div>
        </div>

        <div class="view <?php echo $view === 'progress' ? 'active' : ''; ?>" id="progress">
          <div class="view-content">
            <div class="search-bar">
              <input type="text" id="searchInput" placeholder="Search projects..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
              <button onclick="searchProjects()"><i class="fas fa-search"></i> Search</button>
              <?php if (is_admin() || is_engineer()): ?>
                <button class="btn" id="addProjectBtn" onclick="showAddForm()"><i class="fas fa-plus"></i> Add Project</button>
              <?php endif; ?>
            </div>

            <div id="projectList">
              <?php foreach ($projects as $project): ?>
                <div class="project <?php echo $project['progress'] === 100 ? 'completed' : 'ongoing'; ?>">
                  <div class="project-info">
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                    <?php if ($project['client_name']): ?>
                      <p>Client: <?php echo htmlspecialchars($project['client_name']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($project['detail']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($project['status']); ?></p>
                    <span class="status <?php echo $project['progress'] === 100 ? 'completed' : 'ongoing'; ?>">
                      <?php echo $project['progress'] === 100 ? 'Selesai' : 'Sedang Berjalan'; ?>
                    </span>
                  </div>
                  <div class="project-progress">
                    <div class="percentage"><?php echo $project['progress']; ?>%</div>
                    <div class="progress-bar-container">
                      <div class="progress-bar" style="width: <?php echo $project['progress']; ?>%"></div>
                    </div>
                    <div class="project-actions">
                      <?php if (is_admin() || is_engineer()): ?>
                        <button class="btn" onclick="showEditForm(<?php echo $project['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                        <?php if (is_admin()): ?>
                          <button class="btn btn-danger" onclick="deleteProject(<?php echo $project['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        <?php endif; ?>
                      <?php endif; ?>
                      <button class="btn btn-success" onclick="showReport(<?php echo $project['id']; ?>)">
                        <i class="fas fa-file-alt"></i> <?php echo (is_admin() || is_engineer()) ? 'Laporan' : 'Lihat Laporan'; ?>
                      </button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($projects)): ?>
                <div class="info">Tidak ada project ditemukan</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if (is_admin()): ?>
          <div class="view <?php echo $view === 'users' ? 'active' : ''; ?>" id="users">
            <div class="view-content">
              <h2 style="margin-bottom: 20px;">Manajemen Pengguna</h2>
              <button class="btn" onclick="showAddUserForm()"><i class="fas fa-user-plus"></i> Tambah Pengguna</button>
              
              <div class="user-management">
                <table class="user-table">
                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Username</th>
                      <th>Role</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="userTableBody">
                    <?php foreach ($users as $user): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td>
                          <?php if ($user['role'] === 'admin'): ?>
                            <span class="role-badge role-admin">Admin</span>
                          <?php elseif ($user['role'] === 'engineer'): ?>
                            <span class="role-badge role-engineer">Pegawai</span>
                          <?php elseif ($user['role'] === 'client'): ?>
                            <span class="role-badge role-client">Client</span>
                          <?php endif; ?>
                          <?php if ($user['client_name']): ?>
                            <small><?php echo htmlspecialchars($user['client_name']); ?></small>
                          <?php endif; ?>
                        </td>
                        <td>
                          <button class="btn" onclick="showEditUserForm(<?php echo $user['id']; ?>)"><i class="fas fa-edit"></i></button>
                          <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="view <?php echo $view === 'about' ? 'active' : ''; ?>" id="about">
          <div class="view-content">
            <h2 style="text-align: center; margin-bottom: 20px">
              PT SINAR BAJA BUMI
            </h2>
            <p class="info">
              Perusahaan kami bergerak di bidang konstruksi baja dengan pengalaman lebih dari 20 tahun.
              Kami menyediakan solusi konstruksi baja berkualitas tinggi untuk berbagai proyek baik skala kecil maupun besar.
            </p>
            <div class="dashboard-cards">
              <div class="dashboard-card">
                <h3>Visi</h3>
                <p>Menjadi perusahaan konstruksi baja terdepan di Indonesia dengan kualitas dan inovasi terbaik.</p>
              </div>
              <div class="dashboard-card">
                <h3>Misi</h3>
                <p>Memberikan solusi konstruksi baja yang efisien, aman, dan berkualitas tinggi dengan layanan terpadu.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="view <?php echo $view === 'logout' ? 'active' : ''; ?>" id="logout">
          <div class="view-content">
            <h2 style="text-align: center">Anda telah logout.</h2>
            <p class="info">
              Silakan tutup tab ini atau login kembali untuk melanjutkan.
            </p>
          </div>
        </div>
      </div>

      <!-- Project Form Overlay -->
      <div class="overlay" id="formOverlay"></div>

      <!-- Project Form -->
      <div class="form-container" id="projectForm">
        <button class="close-btn" onclick="hideForm()">&times;</button>
        <h3 id="formTitle">Tambah Detail Project</h3>
        <form id="projectFormData" method="POST" enctype="multipart/form-data">
          <input type="hidden" id="projectId" name="id" value="">
          <div class="form-group">
            <label for="projectTitle">Judul Project</label>
            <input type="text" id="projectTitle" name="title" placeholder="Judul Project" required>
          </div>
          <div class="form-group">
            <label for="projectDetail">Detail Project</label>
            <textarea id="projectDetail" name="detail" placeholder="Detail Project" required></textarea>
          </div>
          <div class="form-group">
            <label for="statusInput">Status</label>
            <select id="statusInput" name="status" onchange="updateProgressFromStatus()" required>
              <option value="">-- Pilih Status --</option>
              <option value="Perencanaan">Perencanaan</option>
              <option value="Persiapan">Persiapan</option>
              <option value="Produksi">Produksi</option>
              <option value="Pengawasan">Pengawasan</option>
              <option value="Penyelesaian">Penyelesaian</option>
            </select>
          </div>
          <input type="hidden" id="projectProgress" name="progress" value="0">

          <div class="form-section">
            <h4>Catatan & Lampiran</h4>
            <div class="form-group">
              <label for="attachmentText">Catatan</label>
              <textarea id="attachmentText" name="note" placeholder="Tambahkan catatan..."></textarea>
            </div>
            <div class="form-group">
              <label for="attachmentFile">Lampiran File</label>
              <div class="file-upload">
                <input type="file" id="attachmentFile" name="attachment" accept="image/*,.pdf,.doc,.docx,.txt">
                <label for="attachmentFile">Pilih File</label>
                <span id="file-chosen">Tidak ada file dipilih</span>
              </div>
            </div>
            <button type="button" class="btn" onclick="addAttachment()">Unggah</button>
          </div>

          <div class="attachment-log" id="attachmentLog"></div>

          <button type="submit" class="save-btn">
            Simpan Project
          </button>
        </form>
      </div>

      <!-- User Form -->
      <div class="form-container" id="userForm">
        <button class="close-btn" onclick="hideUserForm()">&times;</button>
        <h3 id="userFormTitle">Tambah Pengguna</h3>
        <form id="userFormData" method="POST">
          <input type="hidden" id="userId" name="id" value="">
          <div class="form-group">
            <label for="fullName">Nama Lengkap</label>
            <input type="text" id="fullName" name="nama_lengkap" placeholder="Nama lengkap pengguna" required>
          </div>
          <div class="form-group">
            <label for="newUsername">Username</label>
            <input type="text" id="newUsername" name="username" placeholder="Username untuk login" required>
          </div>
          <div class="form-group">
            <label for="newPassword">Password</label>
            <input type="password" id="newPassword" name="password" placeholder="Password untuk login">
          </div>
          <div class="form-group">
            <label for="userRoleSelect">Role</label>
            <select id="userRoleSelect" name="role" required>
              <option value="">-- Pilih Role --</option>
              <option value="admin">Admin</option>
              <option value="engineer">Pegawai</option>
              <option value="client">Client</option>
            </select>
          </div>
          <div class="form-group" id="clientSelectContainer" style="display: none;">
            <label for="clientSelect">Client</label>
            <select id="clientSelect" name="client_id">
              <option value="">-- Pilih Client --</option>
              <?php foreach ($clients as $client): ?>
                <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nama']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="save-btn">
            Simpan Pengguna
          </button>
        </form>
      </div>

      <!-- Report View -->
      <div class="form-container" id="reportView">
        <button class="close-btn" onclick="hideReportView()">&times;</button>
        <div class="report-container">
          <div class="report-header">
            <h3 class="report-title" id="reportProjectTitle">Laporan Proyek</h3>
            <button class="download-btn" onclick="generatePDF()">
              <i class="fas fa-download"></i> Unduh PDF
            </button>
          </div>
          <div id="reportContent">
            <!-- Report content will be generated here -->
          </div>
        </div>
      </div>
    </div>

    <script>
      // Toggle sidebar
      function toggleSidebar() {
        const sidebar = document.querySelector(".sidebar");
        if (window.innerWidth >= 769) {
          sidebar.classList.toggle("collapsed");
        } else {
          sidebar.classList.toggle("open");
        }
      }

      // Switch between views
      function switchView(viewId) {
        window.location.href = `?view=${viewId}`;
      }

      // Show loading screen
      function showLoading() {
        document.querySelector('.loading-screen').classList.add('active');
      }

      // Hide loading screen
      function hideLoading() {
        document.querySelector('.loading-screen').classList.remove('active');
      }

      // Show add project form
      function showAddForm() {
        document.getElementById('formOverlay').classList.add('active');
        document.getElementById('projectForm').classList.add('active');
        document.getElementById('formTitle').textContent = 'Tambah Detail Project';
        document.getElementById('projectId').value = '';
        document.getElementById('projectFormData').reset();
        document.getElementById('attachmentLog').innerHTML = '';
        document.getElementById('file-chosen').textContent = 'Tidak ada file dipilih';
      }

      // Show edit project form
      function showEditForm(projectId) {
        // In a real implementation, you would fetch the project data via AJAX
        // For now, we'll redirect to a URL with the project ID
        window.location.href = `edit_project.php?id=${projectId}`;
      }

      // Delete project
      function deleteProject(projectId) {
        if (confirm("Apakah Anda yakin ingin menghapus project ini?")) {
          // In a real implementation, you would make an AJAX call or form submission
          window.location.href = `delete_project.php?id=${projectId}`;
        }
      }

      // Show project report
      function showReport(projectId) {
        // In a real implementation, you would fetch the project data via AJAX
        // For now, we'll redirect to a URL with the project ID
        window.location.href = `view_report.php?id=${projectId}`;
      }

      // Hide form
      function hideForm() {
        document.getElementById('formOverlay').classList.remove('active');
        document.getElementById('projectForm').classList.remove('active');
      }

      // Add attachment
      function addAttachment() {
        const text = document.getElementById("attachmentText").value;
        const file = document.getElementById("attachmentFile").files[0];
        const log = document.getElementById("attachmentLog");
        const status = document.getElementById("statusInput").value;

        if (!text && !file) {
          alert("Tulis catatan atau pilih file terlebih dahulu.");
          return;
        }

        if (!status) {
          alert("Pilih status proyek terlebih dahulu.");
          return;
        }

        // Find or create section for this status
        let section = document.getElementById(`log-${status}`);
        if (!section) {
          section = document.createElement("div");
          section.id = `log-${status}`;
          section.innerHTML = `<hr><strong>${status}</strong><br>`;
          log.appendChild(section);
        }

        // Create note/attachment
        const entry = document.createElement("div");
        entry.innerHTML = `> <small>${text}</small><br/><small>${new Date().toLocaleString()}</small><br/>`;

        if (file) {
          const url = URL.createObjectURL(file);
          const link = document.createElement("a");
          link.href = url;
          link.download = file.name;
          link.textContent = `${file.name}`;
          link.style.display = "block";
          entry.appendChild(link);
        }

        section.appendChild(entry);

        // Reset input
        document.getElementById("attachmentText").value = "";
        document.getElementById("attachmentFile").value = "";
        document.getElementById("file-chosen").textContent = "Tidak ada file dipilih";
      }

      // Search projects
      function searchProjects() {
        const searchTerm = document.getElementById('searchInput').value;
        window.location.href = `?view=progress&search=${encodeURIComponent(searchTerm)}`;
      }

      // Update progress based on status
      function updateProgressFromStatus() {
        const select = document.getElementById("statusInput");
        const selectedStatus = select.value;
        const progressInput = document.getElementById("projectProgress");

        switch (selectedStatus) {
          case "Perencanaan":
            progressInput.value = 10;
            break;
          case "Persiapan":
            progressInput.value = 30;
            break;
          case "Produksi":
            progressInput.value = 60;
            break;
          case "Pengawasan":
            progressInput.value = 75;
            break;
          case "Penyelesaian":
            progressInput.value = 100;
            break;
          default:
            progressInput.value = 0;
        }

        // Disable attachment if status is Penyelesaian
        const isFinal = selectedStatus === "Penyelesaian";
        document.getElementById("attachmentText").disabled = isFinal;
        document.getElementById("attachmentFile").disabled = isFinal;
      }

      // User Management Functions
      function showAddUserForm() {
        document.getElementById('formOverlay').classList.add('active');
        document.getElementById('userForm').classList.add('active');
        document.getElementById('userFormTitle').textContent = 'Tambah Pengguna';
        document.getElementById('userId').value = '';
        document.getElementById('userFormData').reset();
        document.getElementById('clientSelectContainer').style.display = 'none';
      }

      function showEditUserForm(userId) {
        // In a real implementation, you would fetch the user data via AJAX
        // For now, we'll redirect to a URL with the user ID
        window.location.href = `edit_user.php?id=${userId}`;
      }

      function hideUserForm() {
        document.getElementById('formOverlay').classList.remove('active');
        document.getElementById('userForm').classList.remove('active');
      }

      function deleteUser(userId) {
        if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
          // In a real implementation, you would make an AJAX call or form submission
          window.location.href = `delete_user.php?id=${userId}`;
        }
      }

      // Show/hide client select based on role selection
      document.getElementById('userRoleSelect').addEventListener('change', function() {
        const clientSelectContainer = document.getElementById('clientSelectContainer');
        if (this.value === 'client') {
          clientSelectContainer.style.display = 'block';
        } else {
          clientSelectContainer.style.display = 'none';
        }
      });

      // Set up file input display
      const fileInput = document.getElementById('attachmentFile');
      const fileChosen = document.getElementById('file-chosen');
      
      fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
          fileChosen.textContent = this.files[0].name;
        } else {
          fileChosen.textContent = 'Tidak ada file dipilih';
        }
      });

      // Add click handlers to dashboard cards to filter projects accordingly
      document.addEventListener('DOMContentLoaded', () => {
        // Total projects card - show all projects
        document.querySelector('.dashboard-card:nth-child(1)').addEventListener('click', () => {
          switchView('progress');
        });
        
        // Ongoing projects card - filter ongoing projects
        document.querySelector('.dashboard-card:nth-child(2)').addEventListener('click', () => {
          window.location.href = '?view=progress&filter=ongoing';
        });
        
        // Completed projects card - filter completed projects
        document.querySelector('.dashboard-card:nth-child(3)').addEventListener('click', () => {
          window.location.href = '?view=progress&filter=completed';
        });
      });

      // Generate PDF report
      function generatePDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Get project title
        const title = document.getElementById('reportProjectTitle').textContent;
        
        // Add title to PDF
        doc.setFontSize(18);
        doc.text(title, 105, 20, { align: 'center' });
        
        // Add current date
        doc.setFontSize(10);
        doc.text(`Dibuat pada: ${new Date().toLocaleDateString()}`, 105, 30, { align: 'center' });
        
        // Add line separator
        doc.setDrawColor(139, 107, 74);
        doc.setLineWidth(0.5);
        doc.line(20, 35, 190, 35);
        
        // Get all text content from report
        const contentElements = document.getElementById('reportContent').children;
        let yPosition = 45;
        
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        
        for (let i = 0; i < contentElements.length; i++) {
          const element = contentElements[i];
          
          if (element.tagName === 'HR') {
            // Add line break
            yPosition += 10;
            doc.setDrawColor(200);
            doc.line(20, yPosition, 190, yPosition);
            yPosition += 10;
          } else if (element.tagName === 'H4') {
            // Add section header
            doc.setFontSize(14);
            doc.setTextColor(62, 39, 35);
            doc.text(element.textContent, 20, yPosition);
            yPosition += 10;
          } else if (element.tagName === 'P') {
            // Add paragraph
            const lines = doc.splitTextToSize(element.textContent, 170);
            doc.setFontSize(11);
            doc.setTextColor(0, 0, 0);
            doc.text(lines, 20, yPosition);
            yPosition += lines.length * 7;
          } else if (element.tagName === 'TABLE') {
            // Add table
            const rows = [];
            const headers = [];
            
            // Get headers
            const headerCells = element.querySelectorAll('thead th');
            headerCells.forEach(cell => {
              headers.push(cell.textContent);
            });
            
            // Get rows data
            const dataRows = element.querySelectorAll('tbody tr');
            dataRows.forEach(row => {
              const rowData = [];
              const cells = row.querySelectorAll('td');
              cells.forEach(cell => {
                rowData.push(cell.textContent);
              });
              rows.push(rowData);
            });
            
            // Add table to PDF
            doc.autoTable({
              head: [headers],
              body: rows,
              startY: yPosition,
              theme: 'grid',
              headStyles: {
                fillColor: [62, 39, 35],
                textColor: 255
              },
              alternateRowStyles: {
                fillColor: [245, 243, 238]
              },
              margin: { top: 10 }
            });
            
            yPosition = doc.lastAutoTable.finalY + 10;
          }
          
          // Add new page if needed
          if (yPosition > 260) {
            doc.addPage();
            yPosition = 20;
          }
        }
        
        // Save the PDF
        doc.save(`Laporan_${title.replace(/ /g, '_')}.pdf`);
      }

      // Hide report view
      function hideReportView() {
        document.getElementById('formOverlay').classList.remove('active');
        document.getElementById('reportView').classList.remove('active');
      }
    </script>
  </body>
</html>