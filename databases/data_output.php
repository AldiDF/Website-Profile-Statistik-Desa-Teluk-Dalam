<?php
function cek_id_keluarga($conn,$nomor_kk, $rt, $alamat_domisili) {
    $stmtCek = mysqli_prepare($conn, "SELECT id_keluarga FROM keluarga WHERE nomor_kk = ?");
        mysqli_stmt_bind_param($stmtCek, "s", $nomor_kk);
        mysqli_stmt_execute($stmtCek);
        $hasilCek = mysqli_stmt_get_result($stmtCek);

        if ($rowKeluarga = mysqli_fetch_assoc($hasilCek)) {
            // No. KK SUDAH ADA -> pakai id_keluarga yang sudah ada
            $id_keluarga = $rowKeluarga['id_keluarga'];
        } else {
            // No. KK BELUM ADA -> insert dulu ke tabel keluarga
            $id_keluarga = tambah_data_keluarga($conn, $nomor_kk, $rt, $alamat_domisili);
        }
        mysqli_stmt_close($stmtCek);

    return $id_keluarga;
}

function ambil_data_penduduk($conn, $id, $form_data_penduduk) {
    $stmt = mysqli_prepare($conn, "
        SELECT p.*, k.nomor_kk, k.rt, k.alamat_domisili
        FROM penduduk p
        JOIN keluarga k ON p.id_keluarga_fk = k.id_keluarga
        WHERE p.id_penduduk = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $form_data_penduduk = $row;
    } else {
        die("Data penduduk dengan ID tersebut tidak ditemukan.");
    }
    mysqli_stmt_close($stmt);
    
    return $form_data_penduduk;
}

?>