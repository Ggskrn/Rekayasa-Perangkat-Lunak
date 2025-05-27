<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}

// Jika sudah login, redirect ke index
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - PT Sinar Baja Bumi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
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

      /* Login Page Styles */
      .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-image: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
        background-size: cover;
        background-position: center;
        background-blend-mode: overlay;
        background-color: rgba(62, 39, 35, 0.85);
      }

      .login-box {
        background-color: var(--card-color);
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 450px;
        text-align: center;
        animation: fadeInUp 0.5s ease;
      }

      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .login-logo {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        color: var(--dark-brown);
        margin-bottom: 30px;
        letter-spacing: 1px;
      }

      .login-form .form-group {
        margin-bottom: 20px;
        text-align: left;
      }

      .login-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark-brown);
      }

      .login-form input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 15px;
        font-family: 'Montserrat', sans-serif;
        transition: all 0.3s ease;
      }

      .login-form input:focus {
        outline: none;
        border-color: var(--gold-accent);
        box-shadow: 0 0 0 3px rgba(201, 179, 139, 0.2);
      }

      .login-btn {
        width: 100%;
        padding: 12px;
        background-color: var(--dark-brown);
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
      }

      .login-btn:hover {
        background-color: var(--primary-color);
      }

      .error-message {
        color: var(--danger-color);
        margin-top: 10px;
        font-size: 14px;
      }
    </style>
  </head>
  <body>
    <div class="login-container">
      <div class="login-box">
        <div class="login-logo">PT Sinar Baja Bumi</div>
        <form class="login-form" method="POST">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
          </div>
          <button type="submit" class="login-btn">Masuk</button>
          <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </body>
</html>