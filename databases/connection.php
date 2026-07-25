<?php
// connection.php

// ==========================
// KREDENSIAL DATABASE
// ==========================
// Untuk development, hardcode masih bisa diterima.
// Untuk production, sebaiknya pindahkan ke file .env terpisah (lihat catatan di bawah).

$server   = "localhost";
$user     = "root";
$password = "";
$db_nama  = "db_telukdalam";

// ==========================
// KONEKSI DENGAN EXCEPTION HANDLING
// ==========================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($server, $user, $password, $db_nama);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Gagal terhubung ke database. Silakan hubungi administrator.");
}
