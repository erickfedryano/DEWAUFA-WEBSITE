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

$login_error    = "";
$register_error = "";
$register_ok    = "";
$forgot_error   = "";
$forgot_ok      = "";

// ====== PROSES LOGIN ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $login_error = "Email dan password wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, username, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['user_role'] = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit;
            } else {
                $login_error = "Password salah.";
            }
        } else {
            $login_error = "Email tidak terdaftar.";
        }
        $stmt->close();
    }
}

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
                $register_ok = "Akun berhasil dibuat, silakan login.";
            } else {
                $register_error = "Gagal membuat akun.";
            }
        }
        $stmt->close();
    }
}

// ====== PROSES RESET PASSWORD (SIMPLE: BY EMAIL LANGSUNG GANTI) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot') {
    $email       = trim($_POST['email'] ?? '');
    $new_pass    = trim($_POST['new_password'] ?? '');
    $confirm_new = trim($_POST['confirm_new_password'] ?? '');

    if ($email === '' || $new_pass === '') {
        $forgot_error = "Email dan password baru wajib diisi.";
    } elseif ($new_pass !== $confirm_new) {
        $forgot_error = "Password baru dan konfirmasi tidak sama.";
    } elseif (strlen($new_pass) < 6) {
        $forgot_error = "Password baru minimal 6 karakter.";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $forgot_ok = "Password berhasil direset, silakan login.";
        } else {
            $forgot_error = "Email tidak ditemukan.";
        }
        $stmt->close();
    }
}

// Tentukan form mana yang harus ditampilkan
$show_register = ($register_error || $register_ok) ? true : false;
$show_forgot = ($forgot_error || $forgot_ok) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEWASUFA - Login</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.5)),url('assets/img/home.jpg');
            background-size:cover; background-position:center; padding:20px;
        }
        .login-container {
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
        .remember-forgot {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:25px;
        }
        .remember-me {
            display:flex; align-items:center; gap:8px;
            color:rgba(255,255,255,0.9); font-size:13px;
        }
        .remember-me input[type="checkbox"] { width:16px; height:16px; cursor:pointer; }
        .forgot-link {
            color:rgba(255,255,255,0.9); font-size:13px; text-decoration:none;
        }
        .forgot-link:hover { text-decoration:underline; }
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
        .signup-link {
            text-align:center; margin-top:25px;
            color:rgba(255,255,255,0.9); font-size:13px;
        }
        .signup-link a {
            color:white; text-decoration:none; font-weight:600;
        }
        .signup-link a:hover { text-decoration:underline; }
        
        /* PERBAIKAN: Ganti class hidden dengan display block/none */
        .login-form, .register-form, .forgot-password-form { display:none; }
        .login-form.active { display:block; }
        .register-form.active { display:block; }
        .forgot-password-form.active { display:block; }
        
        .alert {
            padding:10px 12px; border-radius:8px; margin-bottom:15px;
            font-size:13px;
        }
        .alert-danger { background:rgba(255,0,0,0.2); color:#ffd1d1; }
        .alert-success { background:rgba(0,255,0,0.2); color:#d4ffd4; }
        @media (max-width:640px) {
            .login-container { padding:40px 30px; }
            .form-header h2 { font-size:24px; }
        }
    </style>
</head>
<body>
    <div class="login-container" id="loginContainer">
        <!-- Login Form -->
        <div class="login-form <?php echo (!$show_register && !$show_forgot) ? 'active' : ''; ?>">
            <div class="logo">
                <div class="logo-text">DEWASUFA</div>
            </div>

            <div class="form-header">
                <h2>Welcome!</h2>
                <p>Sign in to access your hotel bookings and personal journey</p>
            </div>

            <?php if ($login_error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                        <span class="toggle-password" onclick="togglePassword('loginPassword')">👁</span>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" id="rememberMe">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link" onclick="showForgotPassword(event)">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">Log in</button>
            </form>

            <div class="divider"></div>

            <div class="signup-link">
                Don't have an account? <a href="#" onclick="showRegister(event)">Sign Up</a>
            </div>
        </div>

        <!-- Register Form -->
        <div class="register-form <?php echo $show_register ? 'active' : ''; ?>">
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

            <div class="signup-link">
                Already have an account? <a href="#" onclick="showLogin(event)">Log in</a>
            </div>
        </div>

        <!-- Forgot Password Form -->
        <div class="forgot-password-form <?php echo $show_forgot ? 'active' : ''; ?>">
            <div class="logo">
                <div class="logo-text">DEWASUFA</div>
            </div>

            <div class="form-header">
                <h2>Reset Password</h2>
                <p>Enter your email and new password to reset your account</p>
            </div>

            <?php if ($forgot_error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($forgot_error) ?></div>
            <?php endif; ?>
            <?php if ($forgot_ok): ?>
                <div class="alert alert-success"><?= htmlspecialchars($forgot_ok) ?></div>
            <?php endif; ?>

            <form id="forgotPasswordForm" method="POST" action="">
                <input type="hidden" name="action" value="forgot">
                <div class="form-group">
                    <label for="forgotEmail">Email</label>
                    <input type="email" id="forgotEmail" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="newPassword" name="new_password" placeholder="Enter new password" required>
                        <span class="toggle-password" onclick="togglePassword('newPassword')">👁</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmNewPassword" name="confirm_new_password" placeholder="Confirm new password" required>
                        <span class="toggle-password" onclick="togglePassword('confirmNewPassword')">👁</span>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Reset Password</button>
            </form>

            <div class="signup-link">
                Remember your password? <a href="#" onclick="showLogin(event)">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        
        function showRegister(event) {
            event.preventDefault();
            document.querySelector('.login-form').classList.remove('active');
            document.querySelector('.register-form').classList.add('active');
            document.querySelector('.forgot-password-form').classList.remove('active');
        }
        
        function showLogin(event) {
            event.preventDefault();
            document.querySelector('.login-form').classList.add('active');
            document.querySelector('.register-form').classList.remove('active');
            document.querySelector('.forgot-password-form').classList.remove('active');
        }
        
        function showForgotPassword(event) {
            event.preventDefault();
            document.querySelector('.login-form').classList.remove('active');
            document.querySelector('.register-form').classList.remove('active');
            document.querySelector('.forgot-password-form').classList.add('active');
        }
    </script>
</body>
</html>