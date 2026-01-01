<?php
// ====== KONEKSI DATABASE ======
$host = "localhost";
$user = "root";      // ganti kalau beda
$pass = "";          // ganti kalau ada password
$db   = "dewasufa";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

session_start();

$register_error = "";
$register_ok    = "";

// ====== PROSES REGISTER ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm_password'] ?? '');

    if ($full_name === '' || $email === '' || $username === '' || $password === '') {
        $register_error = "Semua field wajib diisi.";
    } elseif ($password !== $confirm) {
        $register_error = "Password dan konfirmasi tidak sama.";
    } elseif (strlen($password) < 6) {
        $register_error = "Password minimal 6 karakter.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $register_error = "Email atau username sudah digunakan.";
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role) VALUES (?,?,?,?, 'user')");
            $stmt->bind_param("ssss", $full_name, $email, $username, $hash);
            if ($stmt->execute()) {
                $register_ok = "Akun berhasil dibuat! Silakan login.";
            } else {
                $register_error = "Gagal membuat akun.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEWASUFA - Register</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.5)),url('assets/img/home.jpg');
            background-size:cover; background-position:center; padding:20px;
        }
        .register-container {
            width:100%; max-width:450px; background:rgba(255,255,255,0.15);
            backdrop-filter:blur(20px); border-radius:20px; padding:50px 40px;
            box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.2);
        }
        .logo { text-align:center; margin-bottom:30px; }
        .logo-text { color:white; font-size:24px; font-weight:bold; }
        .form-header { text-align:center; margin-bottom:30px; }
        .form-header h2 { font-size:28px; color:white; margin-bottom:10px; font-weight:400; }
        .form-header p { color:rgba(255,255,255,0.8); font-size:13px; line-height:1.5; }
        .form-group { margin-bottom:20px; }
        .form-group label {
            display:block; margin-bottom:8px; color:rgba(255,255,255,0.9);
            font-size:13px; font-weight:500;
        }
        .input-wrapper { position:relative; }
        .form-group input {
            width:100%; padding:14px 16px;
            border:1px solid rgba(255,255,255,0.3); border-radius:10px;
            font-size:14px; background:rgba(255,255,255,0.1); color:white;
            transition:all 0.3s;
        }
        .form-group input::placeholder { color:rgba(255,255,255,0.5); }
        .form-group input:focus {
            outline:none; border-color:rgba(255,255,255,0.5);
            background:rgba(255,255,255,0.15);
        }
        .toggle-password {
            position:absolute; right:15px; top:50%; transform:translateY(-50%);
            cursor:pointer; color:rgba(255,255,255,0.6); font-size:14px;
        }
        .submit-btn {
            width:100%; padding:14px; background:white; color:#1a73e8;
            border:none; border-radius:10px; font-size:15px; font-weight:600;
            cursor:pointer; transition:all 0.3s; margin-bottom:20px;
        }
        .submit-btn:hover {
            background:rgba(255,255,255,0.9); transform:translateY(-2px);
            box-shadow:0 5px 15px rgba(0,0,0,0.2);
        }
        .divider {
            margin:20px 0; position:relative; height:1px;
            background:rgba(255,255,255,0.3);
        }
        .login-link {
            text-align:center; margin-top:25px;
            color:rgba(255,255,255,0.9); font-size:13px;
        }
        .login-link a {
            color:white; text-decoration:none; font-weight:600;
        }
        .login-link a:hover { text-decoration:underline; }
        .alert {
            padding:10px 12px; border-radius:8px; margin-bottom:15px;
            font-size:13px;
        }
        .alert-danger { background:rgba(255,0,0,0.2); color:#ffd1d1; }
        .alert-success { background:rgba(0,255,0,0.2); color:#d4ffd4; }
        @media (max-width:640px) {
            .register-container { padding:40px 30px; }
            .form-header h2 { font-size:24px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-text">DEWASUFA</div>
        </div>

        <div class="form-header">
            <h2>Create Account</h2>
            <p>Sign up to start your booking journey with us</p>
        </div>

        <?php if ($register_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
        <?php endif; ?>
        <?php if ($register_ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($register_ok) ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label for="fullName">Full Name</label>
                <input type="text" id="fullName" name="full_name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="registerEmail">Email</label>
                <input type="email" id="registerEmail" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="registerUsername">Username</label>
                <input type="text" id="registerUsername" name="username" placeholder="Choose a username" required>
            </div>

            <div class="form-group">
                <label for="registerPassword">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="registerPassword" name="password" placeholder="Create a password" required>
                    <span class="toggle-password" onclick="togglePassword('registerPassword')">👁</span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="input-wrapper">
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" required>
                    <span class="toggle-password" onclick="togglePassword('confirmPassword')">👁</span>
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <div class="divider"></div>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>