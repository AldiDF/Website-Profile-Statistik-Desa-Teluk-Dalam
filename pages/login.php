<?php
require '../databases/session_config.php';
session_start();
require "../databases/connection.php";
$conn = $conn ?? null;

$error = "";
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$locked_out = false;
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 300) {
    $sisa_detik = 300 - (time() - $_SESSION['last_attempt_time']);
    $error = "Terlalu banyak percobaan login. Coba lagi dalam " . ceil($sisa_detik / 60) . " menit.";
    $locked_out = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked_out) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } elseif (!($conn instanceof mysqli)) {
        $error = "Koneksi database gagal.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admin WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);

            $_SESSION['admin_id']       = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['login_time']     = time();
            $_SESSION['ip_address']     = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent']     = $_SERVER['HTTP_USER_AGENT'];

            unset($_SESSION['login_attempts']); 

            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $error = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Desa Teluk Dalam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #0f4c3a;
            --emas: #f4b400;
            --abu-teks: #898781;
            --border-soft: #e1e0d9;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(160deg, var(--hijau-tua) 0%, #123f30 55%, #0b2e22 100%);
            position: relative;
            overflow: hidden;
            padding: 1.5rem;
        }

        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(244, 180, 0, 0.08);
        }
        body::before {
            width: 420px;
            height: 420px;
            top: -140px;
            right: -120px;
        }
        body::after {
            width: 300px;
            height: 300px;
            bottom: -100px;
            left: -80px;
            background: rgba(255, 255, 255, 0.05);
        }

        .login-box {
            position: relative;
            z-index: 1;
            background: #ffffff;
            padding: 2.5rem 2.25rem;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 380px;
        }

        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.75rem;
        }

        .brand svg { display: block; }

        .brand h2 {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--hijau-tua);
            text-align: center;
        }

        .brand span {
            font-size: 0.8rem;
            color: var(--abu-teks);
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--hijau-tua);
        }

        .form-group input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1.5px solid var(--border-soft);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--emas);
            box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.18);
        }

        .form-group input:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .btn-login {
            width: 100%;
            padding: 0.8rem;
            margin-top: 0.5rem;
            background: var(--hijau-tua);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-login:hover {
            background: #0c3c2e;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .error-msg {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.1rem;
            text-align: center;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--abu-teks);
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--hijau-tua);
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="brand">
             <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam" style="width: 70px; height: 70px; object-fit: contain;">
            <h2>Desa Teluk Dalam</h2>
            <span>Portal Admin</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus <?= $locked_out ? 'disabled' : '' ?>>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required <?= $locked_out ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="btn-login" <?= $locked_out ? 'disabled' : '' ?>>Masuk</button>
        </form>

        <a href="beranda.php" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</body>
</html>