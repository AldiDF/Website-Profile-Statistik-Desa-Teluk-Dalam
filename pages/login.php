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

            header("Location: dashboard");
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
    <link rel="icon" href="assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styless/beranda.css">

</head>

<body class="login-body">
    <div class="login-box">
        <div class="brand">
            <img src="assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam" style="width: 70px; height: 70px; object-fit: contain;">
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

        <a href="beranda" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</body>

</html>