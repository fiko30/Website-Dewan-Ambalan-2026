<?php
session_start();
require_once '../config/config.php';

// TAMBAHAN: Dapatkan connection dari pool
$conn = getDbConnection();

// Saat halaman login dibuka via GET, reset sesi lama agar user tidak otomatis diarahkan.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['user_id'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, nama_lengkap, password, role FROM users WHERE LOWER(nama_lengkap) = LOWER(?)");
    $stmt->bind_param("s", $nama_lengkap);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $storedPassword = (string) ($row['password'] ?? '');

        // Kompatibel untuk data lama (plain text) dan data baru (hashed).
        $isValidPassword = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

        if ($isValidPassword) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];

            $role = strtolower($row['role']);
            header("Location: " . ($role == 'admin' ? '../admin/dashboard.php' : 'dashboard.php'));
            exit;
        } else {
            $error = "❌ Password Salah!";
        }
    } else {
        $error = "❌ Nama Lengkap Tidak Ditemukan!";
    }
}

// TAMBAHAN: Release connection
if (isset($conn)) {
    releaseDbConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Sistem Penilaian Kelulusan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #6B21A8 0%, #7C3AED 50%, #9333EA 100%); min-height: 100vh; min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 24px; width: 100%; max-width: 420px; }
        .login-header { background: linear-gradient(135deg, #7C3AED 0%, #9333EA 100%); color: white; padding: 45px 30px 35px; text-align: center; border-radius: 24px 24px 0 0; }
        .login-logo { width: 78px; height: 78px; object-fit: contain; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3)); margin-bottom: 12px; }
        .login-header h2 { font-size: 24px; margin-bottom: 8px; line-height: 1.3; }
        .login-header p { font-size: 15px; opacity: 0.9; }
        .login-body { padding: 35px 30px 30px; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #FCA5A5; padding: 14px 16px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; margin-bottom: 8px; color: rgba(255, 255, 255, 0.9); font-weight: 500; font-size: 14px; }
        .form-group input { width: 100%; padding: 14px 16px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 12px; color: white; font-size: 15px; }
        .form-group input::placeholder { color: rgba(255, 255, 255, 0.92); }
        .form-group input::-ms-input-placeholder { color: rgba(255, 255, 255, 0.92); }
        .form-group input:focus { outline: none; border-color: #A78BFA; }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 56px; }
        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 1px solid rgba(255, 255, 255, 0.32);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 9px;
            width: 40px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .toggle-password:hover { background: rgba(255, 255, 255, 0.2); }
        .toggle-password svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .toggle-password .is-hidden { display: none; }
        .btn-login { width: 100%; padding: 16px; background: linear-gradient(135deg, #FB7185 0%, #F43F5E 100%); color: white; border: none; border-radius: 14px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn-back { display: block; width: 100%; margin-top: 12px; padding: 14px; text-align: center; text-decoration: none; background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.28); color: white; border-radius: 12px; font-size: 15px; font-weight: 500; }
        .btn-back:hover { background: rgba(255, 255, 255, 0.2); }
        .login-footer { text-align: center; padding: 20px 30px 25px; color: rgba(255, 255, 255, 0.7); font-size: 13px; }
        .login-footer strong { color: #A78BFA; }

        /* ============================================
           KHUSUS MOBILE / HANDPHONE (max-width: 768px)
           ============================================ */
        @media screen and (max-width: 768px) {
            body {
                padding: 15px 10px;
                align-items: center;
            }
            
            .login-container {
                max-width: 100%;
                border-radius: 16px;
            }
            
            .login-header {
                padding: 25px 18px 20px;
                border-radius: 16px 16px 0 0;
            }
            
            .login-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 10px;
            }
            
            .login-header h2 {
                font-size: 17px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 5px;
                font-weight: 700;
            }
            
            .login-header p {
                font-size: 13px;
            }
            
            .login-body {
                padding: 20px 18px 16px;
            }
            
            .alert-error {
                padding: 11px 13px;
                margin-bottom: 14px;
                font-size: 13px;
            }
            
            .form-group {
                margin-bottom: 14px;
            }
            
            .form-group label {
                margin-bottom: 6px;
                font-size: 13px;
            }
            
            .form-group input {
                padding: 12px 14px;
                font-size: 14px;
                border-radius: 10px;
            }
            
            .toggle-password {
                width: 38px;
                height: 34px;
                right: 7px;
            }
            
            .toggle-password svg {
                width: 18px;
                height: 18px;
            }
            
            .password-wrapper input {
                padding-right: 52px;
            }
            
            .btn-login {
                padding: 13px;
                font-size: 15px;
                border-radius: 12px;
                margin-top: 6px;
            }
            
            .btn-back {
                padding: 12px;
                font-size: 14px;
                margin-top: 10px;
                border-radius: 10px;
            }
            
            .login-footer {
                padding: 12px 18px 16px;
                font-size: 12px;
            }
        }

        /* Untuk HP sangat kecil */
        @media screen and (max-width: 360px) {
            .login-header h2 {
                font-size: 16px;
            }
            .login-header p {
                font-size: 12px;
            }
            .form-group input {
                font-size: 13px;
                padding: 11px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/logo-da.png" alt="Logo Dewan Ambalan" class="login-logo">
            <h2>Sistem Penilaian Kelulusan</h2>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama_lengkap">👤 Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan Nama Lengkap Anda" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">🔒 Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Masukkan Password" required>
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password" title="Tampilkan password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M1 12s4-7 11-7s11 7 11 7s-4 7-11 7s-11-7-11-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off is-hidden" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a21.86 21.86 0 0 1 5.06-5.94"></path>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a22.26 22.26 0 0 1-3.08 4.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Masuk</button>
            </form>
            <a href="../index.php" class="btn-back">Kembali</a>
        </div>
        <div class="login-footer">
            <p><strong>Dewan Ambalan 2025</strong></p>
        </div>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const eyeIcon = toggleBtn.querySelector('.icon-eye');
        const eyeOffIcon = toggleBtn.querySelector('.icon-eye-off');

        toggleBtn.addEventListener('click', function () {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('is-hidden', showPassword);
            eyeOffIcon.classList.toggle('is-hidden', !showPassword);
            toggleBtn.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
            toggleBtn.setAttribute('title', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
        });
    </script>
</body>
</html>