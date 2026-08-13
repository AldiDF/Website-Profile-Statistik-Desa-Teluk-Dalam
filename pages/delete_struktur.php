<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
require '../databases/data_output.php';
require '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;

if ($id === null) {
    header("Location: struktur_desa.php");
    exit;
}

$row = ambil_struktur_desa_by_id($conn, $id);

if ($row !== null) {
    // Hapus file foto dari server dulu (kalau ada), supaya tidak jadi file yatim
    if (!empty($row['foto']) && file_exists('../databases/photo/' . $row['foto'])) {
        unlink('../databases/photo/' . $row['foto']);
    }

    $stmt = hapus_struktur_desa($conn, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: struktur_desa.php?status=hapus");
    exit;
}

header("Location: struktur_desa.php");
exit;