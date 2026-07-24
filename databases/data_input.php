<?php
function tambah_data_penduduk(
    $conn,
    string $nik,
    string $nama_lengkap,
    string $tempat_lahir,
    string $tanggal_lahir,
    string $jenis_kelamin,
    string $agama,
    string $status_perkawinan,
    string $pekerjaan,
    string $pendidikan_terakhir,
    string $kewarganegaraan,
    string $status_penduduk,
    string $id_keluarga,
    string $hubungan_keluarga
) {

    $stmt = mysqli_prepare($conn, "INSERT INTO penduduk (
                        nik, nama_lengkap, tempat_lahir, tanggal_lahir,
                        jenis_kelamin, agama, status_perkawinan, pekerjaan,
                        pendidikan_terakhir, kewarganegaraan, status_penduduk, id_keluarga_fk, hubungan_keluarga
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssis",
        $nik,
        $nama_lengkap,
        $tempat_lahir,
        $tanggal_lahir,
        $jenis_kelamin,
        $agama,
        $status_perkawinan,
        $pekerjaan,
        $pendidikan_terakhir,
        $kewarganegaraan,
        $status_penduduk,
        $id_keluarga,
        $hubungan_keluarga
    );

    return $stmt;
}

function edit_data_penduduk(
    $conn,
    string $nik,
    string $nama_lengkap,
    string $tempat_lahir,
    string $tanggal_lahir,
    string $jenis_kelamin,
    string $agama,
    string $status_perkawinan,
    string $pekerjaan,
    string $pendidikan_terakhir,
    string $kewarganegaraan,
    string $status_penduduk,
    string $hubungan_keluarga,
    int $post_id
) {
    $stmt = mysqli_prepare($conn, "UPDATE penduduk SET
                        nik = ?,
                        nama_lengkap = ?,
                        tempat_lahir = ?,
                        tanggal_lahir = ?,
                        jenis_kelamin = ?,
                        agama = ?,
                        status_perkawinan = ?,
                        pekerjaan = ?,
                        pendidikan_terakhir = ?,
                        kewarganegaraan = ?,
                        status_penduduk = ?,
                        hubungan_keluarga = ?
                    WHERE id_penduduk = ?");

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssi",
        $nik,
        $nama_lengkap,
        $tempat_lahir,
        $tanggal_lahir,
        $jenis_kelamin,
        $agama,
        $status_perkawinan,
        $pekerjaan,
        $pendidikan_terakhir,
        $kewarganegaraan,
        $status_penduduk,
        $hubungan_keluarga,
        $post_id
    );
    return $stmt;
}

function tambah_data_keluarga($conn, string $nomor_kk, string $rt, string $alamat_domisili)
{
    $stmtInsertKeluarga = mysqli_prepare($conn, "INSERT INTO keluarga (nomor_kk, rt, alamat_domisili) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmtInsertKeluarga, "sss", $nomor_kk, $rt, $alamat_domisili);

    if (mysqli_stmt_execute($stmtInsertKeluarga)) {
        $id_keluarga = mysqli_insert_id($conn); // ambil id_keluarga yang baru dibuat
        return $id_keluarga;
    } else {
        $error = "Gagal menyimpan data keluarga baru.";
        return $error;
    }
    mysqli_stmt_close($stmtInsertKeluarga);
}

function edit_data_keluarga($conn, string $nomor_kk, string $rt, string $alamat_domisili, int $id_keluarga)
{
    $stmt = mysqli_prepare($conn, "UPDATE keluarga SET nomor_kk = ?, rt = ?, alamat_domisili = ? WHERE id_keluarga = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $nomor_kk, $rt, $alamat_domisili, $id_keluarga);
    return $stmt;
}

function hapus_data_penduduk($conn, int $id_penduduk)
{
    $stmt = mysqli_prepare($conn, "DELETE FROM penduduk WHERE id_penduduk = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_penduduk);
    return $stmt;
}

function hapus_data_keluarga($conn, int $id_keluarga)
{
    $stmt = mysqli_prepare($conn, "DELETE FROM keluarga WHERE id_keluarga = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_keluarga);
    return $stmt;
}