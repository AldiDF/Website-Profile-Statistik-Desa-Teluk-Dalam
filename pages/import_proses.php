<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
include '../databases/data_output.php';
include '../databases/data_input.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['keluarga']) || !is_array($input['keluarga'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid atau kosong.']);
    exit;
}

function bersihkan(string $v): string
{
    $v = trim($v);
    $v = preg_replace('/\s+/', ' ', $v);
    return strtoupper($v);
}

$ringkasan = [
    'total_kk'           => count($input['keluarga']),
    'keluarga_baru'      => 0,
    'keluarga_ada'       => 0,
    'anggota_baru'       => 0,
    'anggota_diperbarui' => 0,
    'anggota_dilewati'   => 0,
    'gagal'              => [],
];

foreach ($input['keluarga'] as $idxKel => $kel) {
    $nomorKK = trim((string) ($kel['nomor_kk'] ?? ''));
    $rt      = trim((string) ($kel['rt'] ?? ''));
    $alamat  = bersihkan((string) ($kel['alamat_domisili'] ?? ''));
    $anggotaList = $kel['anggota'] ?? [];
    $labelKK = $nomorKK !== '' ? $nomorKK : ('baris data ke-' . ($idxKel + 1));
    if (strlen($nomorKK) !== 16 || !ctype_digit($nomorKK)) {
        $ringkasan['gagal'][] = "KK $labelKK: Nomor KK tidak valid (harus 16 digit angka).";
        continue;
    }
    mysqli_begin_transaction($conn);
    try {
        $keluargaLama = ambil_data_keluarga_by_nomor_kk($conn, $nomorKK);

        if ($keluargaLama) {
            $id_keluarga = (int) $keluargaLama['id_keluarga'];
            $ringkasan['keluarga_ada']++;
        } else {
            $id_keluarga = tambah_data_keluarga($conn, $nomorKK, $rt, $alamat);
            if (!is_numeric($id_keluarga)) {
                throw new Exception("Gagal menyimpan KK baru.");
            }
            $id_keluarga = (int) $id_keluarga;
            $ringkasan['keluarga_baru']++;
        }

        foreach ($anggotaList as $idxA => $a) {
            $nomorAnggota = $idxA + 1;
            $nik = trim((string) ($a['nik'] ?? ''));
            $nama = bersihkan((string) ($a['nama_lengkap'] ?? ''));

            if (strlen($nik) !== 16 || !ctype_digit($nik)) {
                $ringkasan['gagal'][] = "KK $labelKK, anggota ke-$nomorAnggota: NIK tidak valid.";
                continue;
            }
            if ($nama === '') {
                $ringkasan['gagal'][] = "KK $labelKK, anggota ke-$nomorAnggota: Nama kosong.";
                continue;
            }

            $dataBaru = [
                'nik'                 => $nik,
                'nama_lengkap'        => $nama,
                'tempat_lahir'        => bersihkan((string) ($a['tempat_lahir'] ?? '')),
                'tanggal_lahir'       => trim((string) ($a['tanggal_lahir'] ?? '')),
                'jenis_kelamin'       => bersihkan((string) ($a['jenis_kelamin'] ?? '')),
                'agama'               => bersihkan((string) ($a['agama'] ?? '')),
                'pekerjaan'           => bersihkan((string) ($a['pekerjaan'] ?? '')),
                'pendidikan_terakhir' => bersihkan((string) ($a['pendidikan_terakhir'] ?? '')),
                'kewarganegaraan'     => bersihkan((string) ($a['kewarganegaraan'] ?? 'WNI')),
                'status_penduduk'     => bersihkan((string) ($a['status_penduduk'] ?? 'PERMANEN')),
                'hubungan_keluarga'   => bersihkan((string) ($a['hubungan_keluarga'] ?? '')),
            ];

            $dataLama = ambil_data_penduduk_by_nik($conn, $nik);

            if ($dataLama === null) {
                $stmt = tambah_data_penduduk(
                    $conn,
                    $dataBaru['nik'],
                    $dataBaru['nama_lengkap'],
                    $dataBaru['tempat_lahir'],
                    $dataBaru['tanggal_lahir'],
                    $dataBaru['jenis_kelamin'],
                    $dataBaru['agama'],
                    $dataBaru['pekerjaan'],
                    $dataBaru['pendidikan_terakhir'],
                    $dataBaru['kewarganegaraan'],
                    $dataBaru['status_penduduk'],
                    (string) $id_keluarga,
                    $dataBaru['hubungan_keluarga']
                );
                if (!mysqli_stmt_execute($stmt)) {
                    if (mysqli_errno($conn) === 1062) {
                        $ringkasan['gagal'][] = "NIK $nik: sudah terdaftar (bentrok saat proses).";
                    } else {
                        throw new Exception("Gagal menyimpan anggota NIK $nik.");
                    }
                } else {
                    $ringkasan['anggota_baru']++;
                }
                mysqli_stmt_close($stmt);
            } else {
                $adaPerubahan = false;
                foreach ($dataBaru as $kolom => $nilai) {
                    if ((string) $dataLama[$kolom] !== (string) $nilai) {
                        $adaPerubahan = true;
                        break;
                    }
                }

                if (!$adaPerubahan) {
                    $ringkasan['anggota_dilewati']++;
                    continue; 
                }

                $stmt = edit_data_penduduk(
                    $conn,
                    $dataBaru['nik'],
                    $dataBaru['nama_lengkap'],
                    $dataBaru['tempat_lahir'],
                    $dataBaru['tanggal_lahir'],
                    $dataBaru['jenis_kelamin'],
                    $dataBaru['agama'],
                    $dataBaru['pekerjaan'],
                    $dataBaru['pendidikan_terakhir'],
                    $dataBaru['kewarganegaraan'],
                    $dataBaru['status_penduduk'],
                    $dataBaru['hubungan_keluarga'],
                    (int) $dataLama['id_penduduk']
                );
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal memperbarui anggota NIK $nik.");
                }
                $ringkasan['anggota_diperbarui']++;
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $ringkasan['gagal'][] = "KK $labelKK: " . $e->getMessage();
    }
}

echo json_encode(['success' => true, 'ringkasan' => $ringkasan]);