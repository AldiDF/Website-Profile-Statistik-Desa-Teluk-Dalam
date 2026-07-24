<?php
session_start();
require '../databases/connection.php';
include '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

if (!isset($_GET['id_keluarga']) || !is_numeric($_GET['id_keluarga'])) {
    header("Location: dashboard.php");
    exit;
}

$id_keluarga = (int) $_GET['id_keluarga'];

mysqli_begin_transaction($conn);

try {
    $stmtAnggota = mysqli_prepare($conn, "DELETE FROM penduduk WHERE id_keluarga_fk = ?");
    mysqli_stmt_bind_param($stmtAnggota, "i", $id_keluarga);
    if (!mysqli_stmt_execute($stmtAnggota)) {
        throw new Exception("Gagal menghapus anggota keluarga.");
    }
    mysqli_stmt_close($stmtAnggota);
    $stmtKeluarga = hapus_data_keluarga($conn, $id_keluarga);
    if (!mysqli_stmt_execute($stmtKeluarga)) {
        throw new Exception("Gagal menghapus data keluarga.");
    }
    mysqli_stmt_close($stmtKeluarga);

    mysqli_commit($conn);
    header("Location: dashboard.php?status=hapus_kk");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}