<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
include '../databases/data_output.php';
include '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$mode = "tambah";
$title_page = "Tambah Data Keluarga";
$error = "";
$id_keluarga = null;

$keluarga = [
    "nomor_kk"        => "",
    "rt"              => "",
    "alamat_domisili" => "",
];

$anggota_kosong = [
    "id_penduduk"         => "",
    "nik"                 => "",
    "nama_lengkap"        => "",
    "tempat_lahir"        => "",
    "tanggal_lahir"       => "",
    "jenis_kelamin"       => "",
    "agama"               => "",
    "pekerjaan"           => "",
    "pendidikan_terakhir" => "",
    "kewarganegaraan"     => "",
    "status_penduduk"     => "PERMANEN",
    "hubungan_keluarga"   => "",
];

$anggota_list = [];
$original_ids = "";

// ==========================
// TENTUKAN MODE (Tambah / Edit)
// ==========================
if (isset($_GET['id_keluarga']) && is_numeric($_GET['id_keluarga'])) {
    $mode = "edit";
    $title_page = "Edit Data Keluarga";
    $id_keluarga = (int) $_GET['id_keluarga'];
}

// ==========================
// PROSES SUBMIT (Tambah / Update sekaligus semua anggota)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode        = $_POST['form_mode'] === 'edit' ? 'edit' : 'tambah';
    $title_page  = $mode === 'edit' ? "Edit Data Keluarga" : "Tambah Data Keluarga";
    $id_keluarga = isset($_POST['id_keluarga']) && is_numeric($_POST['id_keluarga']) ? (int) $_POST['id_keluarga'] : null;
    $original_ids = $_POST['original_ids'] ?? '';

    $keluarga['nomor_kk']        = bersihkan_input($_POST['nomor_kk'] ?? '');
    $keluarga['rt']              = bersihkan_input($_POST['rt'] ?? '');
    $keluarga['alamat_domisili'] = bersihkan_input($_POST['alamat_domisili'] ?? '');

    $niks               = bersihkan_input_array($_POST['nik'] ?? []);
    $namas              = bersihkan_input_array($_POST['nama_lengkap'] ?? []);
    $tempat_lahirs      = bersihkan_input_array($_POST['tempat_lahir'] ?? []);
    $tanggal_lahirs     = bersihkan_input_array($_POST['tanggal_lahir'] ?? []);
    $jenis_kelamins     = bersihkan_input_array($_POST['jenis_kelamin'] ?? []);
    $agamas             = bersihkan_input_array($_POST['agama'] ?? []);
    $pekerjaans         = bersihkan_input_array($_POST['pekerjaan'] ?? []);
    $pendidikans        = bersihkan_input_array($_POST['pendidikan_terakhir'] ?? []);
    $kewarganegaraans   = bersihkan_input_array($_POST['kewarganegaraan'] ?? []);
    $status_penduduks   = bersihkan_input_array($_POST['status_penduduk'] ?? []);
    $hubungans          = bersihkan_input_array($_POST['hubungan_keluarga'] ?? []);
    $id_penduduks       = bersihkan_input_array($_POST['id_penduduk'] ?? []);

    // Susun ulang $anggota_list supaya kalau ada error, form tetap terisi
    foreach ($niks as $i => $v) {
        $anggota_list[] = [
            "id_penduduk"         => $id_penduduks[$i] ?? '',
            "nik"                 => $niks[$i] ?? '',
            "nama_lengkap"        => $namas[$i] ?? '',
            "tempat_lahir"        => $tempat_lahirs[$i] ?? '',
            "tanggal_lahir"       => $tanggal_lahirs[$i] ?? '',
            "jenis_kelamin"       => $jenis_kelamins[$i] ?? '',
            "agama"               => $agamas[$i] ?? '',
            "pekerjaan"           => $pekerjaans[$i] ?? '',
            "pendidikan_terakhir" => $pendidikans[$i] ?? '',
            "kewarganegaraan"     => $kewarganegaraans[$i] ?? '',
            "status_penduduk"     => $status_penduduks[$i] ?? '',
            "hubungan_keluarga"   => $hubungans[$i] ?? '',
        ];
    }

    // ==========================
    // VALIDASI
    // ==========================
    if (strlen($keluarga['nomor_kk']) !== 16 || !ctype_digit($keluarga['nomor_kk'])) {
        $error = "Nomor KK harus terdiri dari 16 digit angka.";
    } elseif (empty($niks)) {
        $error = "Minimal harus ada 1 anggota keluarga.";
    } else {
        foreach ($niks as $i => $nik) {
            $nomorAnggota = $i + 1;
            $nikTrim = trim($nik);
            if (strlen($nikTrim) !== 16 || !ctype_digit($nikTrim)) {
                $error = "NIK pada Anggota Keluarga $nomorAnggota harus terdiri dari 16 digit angka.";
                break;
            }
            if (trim($namas[$i] ?? '') === '') {
                $error = "Nama Lengkap pada Anggota Keluarga $nomorAnggota wajib diisi.";
                break;
            }
        }
    }

    if (empty($error)) {
        mysqli_begin_transaction($conn);
        $gagal = false;

        try {
            if ($mode === 'edit') {
                if ($id_keluarga === null) {
                    throw new Exception("ID Keluarga tidak valid.");
                }
                // Cek nomor KK tidak dipakai keluarga lain
                $stmtCek = mysqli_prepare($conn, "SELECT id_keluarga FROM keluarga WHERE nomor_kk = ? AND id_keluarga != ?");
                mysqli_stmt_bind_param($stmtCek, "si", $keluarga['nomor_kk'], $id_keluarga);
                mysqli_stmt_execute($stmtCek);
                $hasilCek = mysqli_stmt_get_result($stmtCek);
                if (mysqli_fetch_assoc($hasilCek)) {
                    throw new Exception("Nomor KK tersebut sudah dipakai oleh keluarga lain.");
                }
                mysqli_stmt_close($stmtCek);

                // Cek dulu apakah data keluarga benar-benar berubah, supaya tidak query sia-sia
                $data_keluarga_lama = ambil_data_keluarga($conn, $id_keluarga);
                $keluarga_berubah = (
                    $data_keluarga_lama === null ||
                    (string) $data_keluarga_lama['nomor_kk']        !== (string) $keluarga['nomor_kk'] ||
                    (string) $data_keluarga_lama['rt']              !== (string) $keluarga['rt'] ||
                    (string) $data_keluarga_lama['alamat_domisili'] !== (string) $keluarga['alamat_domisili']
                );

                if ($keluarga_berubah) {
                    $stmtKeluarga = edit_data_keluarga($conn, $keluarga['nomor_kk'], $keluarga['rt'], $keluarga['alamat_domisili'], $id_keluarga);
                    if (!mysqli_stmt_execute($stmtKeluarga)) {
                        throw new Exception("Gagal menyimpan data keluarga.");
                    }
                    mysqli_stmt_close($stmtKeluarga);
                }

                // Hapus anggota yang sudah tidak ada di form (dihapus lewat tombol Hapus)
                $original_id_arr  = array_filter(array_map('trim', explode(',', $original_ids)));
                $submitted_id_arr = array_filter(array_map('trim', $id_penduduks));
                $to_delete        = array_diff($original_id_arr, $submitted_id_arr);
                foreach ($to_delete as $del_id) {
                    $stmtHapus = hapus_data_penduduk($conn, (int) $del_id);
                    if (!mysqli_stmt_execute($stmtHapus)) {
                        throw new Exception("Gagal menghapus salah satu anggota keluarga.");
                    }
                    mysqli_stmt_close($stmtHapus);
                }
            } else {
                $id_keluarga = cek_id_keluarga($conn, $keluarga['nomor_kk'], $keluarga['rt'], $keluarga['alamat_domisili']);
                if (!is_numeric($id_keluarga)) {
                    throw new Exception("Gagal menyimpan data keluarga baru.");
                }
            }

            // Simpan / update tiap anggota
            foreach ($niks as $i => $nik) {
                $currentId = trim($id_penduduks[$i] ?? '');

                if ($currentId !== '') {
                    // Bandingkan dulu dengan data lama di database; kalau tidak ada
                    // perubahan sama sekali, lewati query UPDATE untuk anggota ini.
                    $data_lama = ambil_data_penduduk_by_id($conn, (int) $currentId);
                    $data_baru = [
                        'nik'                 => trim($niks[$i]),
                        'nama_lengkap'        => trim($namas[$i]),
                        'tempat_lahir'        => trim($tempat_lahirs[$i]),
                        'tanggal_lahir'       => trim($tanggal_lahirs[$i]),
                        'jenis_kelamin'       => trim($jenis_kelamins[$i]),
                        'agama'               => trim($agamas[$i]),
                        'pekerjaan'           => trim($pekerjaans[$i]),
                        'pendidikan_terakhir' => trim($pendidikans[$i]),
                        'kewarganegaraan'     => trim($kewarganegaraans[$i]),
                        'status_penduduk'     => trim($status_penduduks[$i]),
                        'hubungan_keluarga'   => trim($hubungans[$i]),
                    ];

                    $ada_perubahan = true;
                    if ($data_lama !== null) {
                        $ada_perubahan = false;
                        foreach ($data_baru as $kolom => $nilai_baru) {
                            if ((string) $data_lama[$kolom] !== (string) $nilai_baru) {
                                $ada_perubahan = true;
                                break;
                            }
                        }
                    }

                    if (!$ada_perubahan) {
                        continue; // tidak ada perubahan -> skip, lanjut ke anggota berikutnya
                    }

                    $stmtAnggota = edit_data_penduduk(
                        $conn,
                        $data_baru['nik'],
                        $data_baru['nama_lengkap'],
                        $data_baru['tempat_lahir'],
                        $data_baru['tanggal_lahir'],
                        $data_baru['jenis_kelamin'],
                        $data_baru['agama'],
                        $data_baru['pekerjaan'],
                        $data_baru['pendidikan_terakhir'],
                        $data_baru['kewarganegaraan'],
                        $data_baru['status_penduduk'],
                        $data_baru['hubungan_keluarga'],
                        (int) $currentId
                    );
                } else {
                    $stmtAnggota = tambah_data_penduduk(
                        $conn,
                        trim($niks[$i]),
                        trim($namas[$i]),
                        trim($tempat_lahirs[$i]),
                        trim($tanggal_lahirs[$i]),
                        trim($jenis_kelamins[$i]),
                        trim($agamas[$i]),
                        trim($pekerjaans[$i]),
                        trim($pendidikans[$i]),
                        trim($kewarganegaraans[$i]),
                        trim($status_penduduks[$i]),
                        (string) $id_keluarga,
                        trim($hubungans[$i])
                    );
                }

                if (!mysqli_stmt_execute($stmtAnggota)) {
                    if (mysqli_errno($conn) === 1062) {
                        throw new Exception("NIK " . htmlspecialchars(trim($niks[$i])) . " sudah terdaftar. Gunakan NIK lain.");
                    }
                    throw new Exception("Terjadi kesalahan saat menyimpan data anggota ke-" . ($i + 1) . ".");
                }
                mysqli_stmt_close($stmtAnggota);
            }

            mysqli_commit($conn);
            header("Location: dashboard.php?status=sukses");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
} else {
    // ==========================
    // GET: siapkan data awal form
    // ==========================
    if ($mode === 'edit') {
        $data_keluarga = ambil_data_keluarga($conn, $id_keluarga);
        if ($data_keluarga === null) {
            die("Data keluarga tidak ditemukan.");
        }
        $keluarga['nomor_kk']        = $data_keluarga['nomor_kk'];
        $keluarga['rt']              = $data_keluarga['rt'];
        $keluarga['alamat_domisili'] = $data_keluarga['alamat_domisili'];

        $anggota_db = ambil_anggota_keluarga($conn, $id_keluarga);

        $prioritas_hubungan = [
            'KEPALA KELUARGA' => 0,
            'SUAMI'           => 1,
            'ISTRI'           => 1,
            'ANAK'            => 2,
        ];
        usort($anggota_db, function ($a, $b) use ($prioritas_hubungan) {
            $pa = $prioritas_hubungan[strtoupper($a['hubungan_keluarga'] ?? '')] ?? 3;
            $pb = $prioritas_hubungan[strtoupper($b['hubungan_keluarga'] ?? '')] ?? 3;
            return $pa <=> $pb;
        });

        foreach ($anggota_db as $a) {
            $anggota_list[] = [
                "id_penduduk"         => $a['id_penduduk'],
                "nik"                 => $a['nik'],
                "nama_lengkap"        => $a['nama_lengkap'],
                "tempat_lahir"        => $a['tempat_lahir'],
                "tanggal_lahir"       => $a['tanggal_lahir'],
                "jenis_kelamin"       => $a['jenis_kelamin'],
                "agama"               => $a['agama'],
                "pekerjaan"           => $a['pekerjaan'],
                "pendidikan_terakhir" => $a['pendidikan_terakhir'],
                "kewarganegaraan"     => $a['kewarganegaraan'],
                "status_penduduk"     => $a['status_penduduk'],
                "hubungan_keluarga"   => $a['hubungan_keluarga'],
            ];
        }
        $original_ids = implode(',', array_column($anggota_list, 'id_penduduk'));
    } else {
        $anggota_list[] = $anggota_kosong;
    }
}

// ==========================
// HELPER: render satu blok form anggota
// ==========================
function opsi_select($nama_opsi, $daftar, $terpilih)
{
    $html = '';
    foreach ($daftar as $opt) {
        $selected = ($terpilih === $opt) ? 'selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($opt) . "\" $selected>" . htmlspecialchars($opt) . "</option>";
    }
    return $html;
}

/**
 * Membersihkan 1 nilai string: trim, hapus spasi ganda, uppercase.
 */
function bersihkan_input(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value); // banyak spasi -> 1 spasi
    $value = strtoupper($value);
    return $value;
}

/**
 * Membersihkan array of string (misal $_POST['nama_lengkap'] yang berupa array).
 */
function bersihkan_input_array(array $values): array
{
    return array_map('bersihkan_input', $values);
}

function render_anggota_block($a, $nomor)
{
    ob_start();
?>
    <div class="anggota-block">
        <div class="anggota-block-header">
            <h3>Anggota Keluarga <span class="block-num"><?= $nomor ?></span></h3>
            <button type="button" class="btn-hapus-block" onclick="hapusBlokAnggota(this)">&times; Hapus</button>
        </div>
        <input type="hidden" name="id_penduduk[]" value="<?= htmlspecialchars($a['id_penduduk'] ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap[]" maxlength="100" value="<?= htmlspecialchars($a['nama_lengkap'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Nomor Induk Kependudukan</label>
                <input type="text" name="nik[]" maxlength="16" pattern="\d{16}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?= htmlspecialchars($a['nik'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin[]" required>
                    <option value="">-- Pilih --</option>
                    <?= opsi_select('jenis_kelamin', ['LAKI-LAKI', 'PEREMPUAN'], $a['jenis_kelamin'] ?? '') ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir[]" maxlength="50" value="<?= htmlspecialchars($a['tempat_lahir'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir[]" value="<?= htmlspecialchars($a['tanggal_lahir'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Agama</label>
                <select name="agama[]" required>
                    <option value="">-- Pilih --</option>
                    <?= opsi_select('agama', [
                        'ISLAM',
                        'KRISTEN',
                        'KATOLIK',
                        'HINDU',
                        'BUDDHA',
                        'KONGHUCU',
                    ], $a['agama'] ?? '') ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pendidikan Terakhir</label>
                <select name="pendidikan_terakhir[]" required>
                    <option value="">-- Pilih --</option>
                    <?= opsi_select('pendidikan_terakhir', [
                        'TIDAK SEKOLAH',
                        'SD/SEDERAJAT',
                        'SLTP/SEDERAJAT',
                        'SLTA/SEDERAJAT',
                        'DIPLOMA I',
                        'DIPLOMA II',
                        'DIPLOMA III',
                        'DIPLOMA IV/STRATA I',
                        'STRATA II',
                        'STRATA III'
                    ], $a['pendidikan_terakhir'] ?? '') ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jenis Pekerjaan</label>
                <input type="text" name="pekerjaan[]" maxlength="100" value="<?= htmlspecialchars($a['pekerjaan'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Status Hubungan Dalam Keluarga</label>
                <select name="hubungan_keluarga[]" required>
                    <option value="">-- Pilih --</option>
                    <?= opsi_select('hubungan_keluarga', ['KEPALA KELUARGA', 'SUAMI', 'ISTRI', 'ANAK', 'CUCU', 'ORANG TUA', 'MERTUA', 'MENANTU', 'SAUDARA', 'FAMILI LAIN'], $a['hubungan_keluarga'] ?? '') ?>
                </select>
            </div>

            <div class="form-group">
                <label>Kewarganegaraan</label>
                <select name="kewarganegaraan[]" required>
                    <?= opsi_select('kewarganegaraan', ['WNI', 'WNA'], $a['kewarganegaraan'] ?? '') ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status Penduduk</label>
                <select name="status_penduduk[]" required>
                    <?= opsi_select('status_penduduk', ['PERMANEN', 'NON PERMANEN', 'MENINGGAL'], $a['status_penduduk'] ?? 'Aktif') ?>
                </select>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title_page ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #0f4c3a;
            --hijau-gelap: #0c3c2e;
            --emas: #f4b400;
            --abu-teks: #898781;
            --border-soft: #e1e0d9;
            --bg: #f6f5f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: var(--bg);
            color: #2b2b28;
        }

        .topbar {
            background: var(--hijau-tua);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .topbar img {
            width: 35px;
            height: 40px;
            border-radius: 50%;
            display: block;
        }

        .topbar span {
            font-size: 1rem;
            font-weight: 600;
        }

        .page-wrap {
            padding: 2rem 1rem;
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            padding: 1.8rem 2rem 2.2rem;
            margin-bottom: 1.3rem;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-soft);
        }

        .form-header h1 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--hijau-tua);
        }

        .badge-mode {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
        }

        .badge-mode.tambah {
            background: rgba(15, 76, 58, 0.1);
            color: var(--hijau-tua);
        }

        .badge-mode.edit {
            background: rgba(244, 180, 0, 0.18);
            color: #a16207;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1.2rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--hijau-tua);
            margin: 0 0 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.1rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            color: var(--hijau-tua);
            margin-bottom: 0.35rem;
            font-weight: 600;
        }

        .form-group .hint {
            font-weight: 400;
            color: var(--abu-teks);
            font-size: 0.75rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 1.5px solid var(--border-soft);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #2b2b28;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--emas);
            box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.18);
        }

        .anggota-block {
            border: 1.5px dashed var(--border-soft);
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .anggota-block:last-child {
            margin-bottom: 0;
        }

        .anggota-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .anggota-block-header h3 {
            font-size: 0.95rem;
            color: var(--hijau-tua);
            font-weight: 600;
        }

        .btn-hapus-block {
            background: none;
            border: 1.5px solid #fca5a5;
            color: #b91c1c;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-hapus-block:hover {
            background: #fee2e2;
        }

        .btn-tambah-anggota {
            width: 100%;
            display: block;
            background: var(--hijau-gelap);
            color: #fff;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .btn-tambah-anggota:hover {
            background: #0a2f24;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.7rem;
            margin-top: 1.2rem;
        }

        .form-footer .btn-hapus-kk {
            margin-right: auto;
        }

        .btn-hapus-kk {
            padding: 0.75rem 1.3rem;
            border-radius: 8px;
            border: 1.5px solid #fca5a5;
            background: #fff;
            color: #b91c1c;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-hapus-kk:hover {
            background: #fee2e2;
        }

        .btn-cancel {
            padding: 0.75rem 1.3rem;
            border-radius: 8px;
            border: 1.5px solid var(--border-soft);
            background: #fff;
            color: var(--abu-teks);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-cancel:hover {
            background: #f6f5f1;
        }

        .btn-save {
            flex: 1;
            text-align: center;
            justify-content: center;
            padding: 0.75rem 1.3rem;
            border-radius: 8px;
            border: none;
            background: var(--hijau-tua);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-save:hover {
            background: var(--hijau-gelap);
        }

        @media (max-width: 720px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1.3rem;
            }

            .topbar {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
        <span>Desa Teluk Dalam - Admin</span>
    </div>

    <div class="page-wrap">
        <div class="form-container">

            <form action="" method="POST" id="familyForm">
                <input type="hidden" name="form_mode" value="<?= $mode ?>">
                <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="id_keluarga" value="<?= htmlspecialchars((string) $id_keluarga) ?>">
                    <input type="hidden" name="original_ids" value="<?= htmlspecialchars($original_ids) ?>">
                <?php endif; ?>

                <div class="card">
                    <div class="form-header">
                        <h1><?= $title_page ?></h1>
                        <span class="badge-mode <?= $mode ?>"><?= $mode === 'edit' ? 'Mode Edit' : 'Mode Tambah' ?></span>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <p class="section-title">Data Keluarga</p>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label>Nomor Kartu Keluarga <span class="hint">(16 digit, harus unik)</span></label>
                            <input type="text" name="nomor_kk" maxlength="16" pattern="\d{16}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?= htmlspecialchars($keluarga['nomor_kk']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>RT</label>
                            <input type="text" name="rt" maxlength="3" pattern="\d{3}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?= htmlspecialchars($keluarga['rt']) ?>" required>
                        </div>
                        <div class="form-group form-group-full">
                            <label>Alamat Domisili</label>
                            <input type="text" name="alamat_domisili" maxlength="255" value="<?= htmlspecialchars($keluarga['alamat_domisili']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <p class="section-title">Anggota Keluarga</p>

                    <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem;">
                        <input type="file" id="importExcelInput" accept=".xlsx,.xls" style="display:none;">
                        <button type="button" class="btn-tambah-anggota" id="btnImportExcel">📥 Import dari Excel</button>
                        <span id="importStatus" style="font-size:0.82rem; color:#898781;"></span>
                    </div>

                    <div id="anggotaContainer">
                        <?php foreach ($anggota_list as $i => $a): ?>
                            <?= render_anggota_block($a, $i + 1) ?>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn-tambah-anggota" id="btnTambahAnggota">+ Tambah Anggota Keluarga</button>
                </div>

                <div class="form-footer">
                    <?php if ($mode === 'edit'): ?>
                        <a href="delete.php?id_keluarga=<?= htmlspecialchars((string) $id_keluarga) ?>"
                            class="btn-hapus-kk"
                            onclick="return confirm('Yakin ingin menghapus seluruh KK ini beserta SEMUA anggotanya? Tindakan ini tidak bisa dibatalkan.')">
                            Hapus KK
                        </a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-save">
                        <?= $mode === 'edit' ? 'Update Data' : 'Konfirmasi Penambahan Data' ?>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // ==========================
        // IMPORT DARI EXCEL (format standar Data Keluarga)
        // ==========================
        const MAP_PENDIDIKAN = {
            'TIDAK SEKOLAH': 'TIDAK SEKOLAH',
            'SD/SEDERAJAT': 'SD/SEDERAJAT',
            'SLTP/SEDERAJAT': 'SLTP/SEDERAJAT',
            'SLTA/SEDERAJAT': 'SLTA/SEDERAJAT',
            'DIPLOMA I': 'DIPLOMA I',
            'DIPLOMA II': 'DIPLOMA II',
            'DIPLOMA III': 'DIPLOMA III',
            'DIPLOMA IV/STRATA I': 'DIPLOMA IV/STRATA I',
            'D-IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'S1/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'S2/SEDERAJAT': 'STRATA II',
            'S3/SEDERAJAT': 'STRATA III',
        };

        function normalisasiPendidikan(v) {
            if (!v) return '';
            const key = v.toString().trim().toUpperCase();
            return MAP_PENDIDIKAN[key] || key;
        }

        function excelDateToISO(v) {
            if (!v) return '';
            if (v instanceof Date && !isNaN(v)) {
                const y = v.getFullYear();
                const m = String(v.getMonth() + 1).padStart(2, '0');
                const d = String(v.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
            const parts = v.toString().trim().split(/[\/\-]/);
            if (parts.length === 3) {
                let [a, b, c] = parts;
                if (c.length === 4) return `${c}-${b.padStart(2, '0')}-${a.padStart(2, '0')}`;
                if (a.length === 4) return `${a}-${b.padStart(2, '0')}-${c.padStart(2, '0')}`;
            }
            return '';
        }

        function setSelectValue(block, name, value) {
            const el = block.querySelector(`[name="${name}"]`);
            if (!el || !value) return;
            const opsi = Array.from(el.options).find(o => o.value.toUpperCase() === value.toUpperCase());
            if (opsi) el.value = opsi.value;
        }

        function anggotaBlockKosongDiForm() {
            const container = document.getElementById('anggotaContainer');
            const blocks = container.querySelectorAll('.anggota-block');
            if (blocks.length === 0) return true;
            if (blocks.length === 1) {
                const nama = blocks[0].querySelector('[name="nama_lengkap[]"]');
                return !nama || nama.value.trim() === '';
            }
            return false;
        }

        document.getElementById('btnImportExcel').addEventListener('click', () => {
            document.getElementById('importExcelInput').click();
        });

        document.getElementById('importExcelInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            e.target.value = ''; // supaya file yang sama bisa dipilih lagi kalau perlu
            if (!file) return;

            if (!anggotaBlockKosongDiForm()) {
                const lanjut = confirm('Form sudah berisi data anggota. Import akan MENGGANTI seluruh anggota yang sudah ada di form ini. Lanjutkan?');
                if (!lanjut) return;
            }

            const reader = new FileReader();
            reader.onload = function (evt) {
                try {
                    const data = new Uint8Array(evt.target.result);
                    const workbook = XLSX.read(data, { type: 'array', cellDates: true });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                    prosesImportExcel(rows);
                } catch (err) {
                    alert('Gagal membaca file Excel: ' + err.message);
                }
            };
            reader.readAsArrayBuffer(file);
        });

        function prosesImportExcel(rows) {
            let nomorKK = '', rt = '', alamat = '';
            let headerRowIdx = -1;
            let nomorKKRowIdx = -1;
            // Kumpulkan semua header "PERIODE ... ( PENDUDUK PERMANEN/NON PERMANEN)" beserta posisi barisnya.
            // File bisa berisi lebih dari satu blok header, jadi statusnya nanti diambil dari header
            // TERDEKAT SEBELUM baris "No. KK" yang terbaca, bukan header terakhir di seluruh file.
            const statusHeaderList = []; // { rowIndex, status }

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cellA = (row[0] || '').toString();

                if (cellA.trim().toUpperCase().startsWith('NO. KK')) {
                    const mKK = cellA.match(/(\d{16})/);
                    if (mKK) { nomorKK = mKK[1]; nomorKKRowIdx = i; }
                }
                if (cellA.trim().toUpperCase().startsWith('ALAMAT')) {
                    const mAlamat = cellA.match(/ALAMAT\s*:\s*(.*?),\s*NAMA DUSUN/i);
                    if (mAlamat) alamat = mAlamat[1].trim();
                    const mRT = cellA.match(/RT\/RW\s*:\s*(\d+)/i);
                    if (mRT) rt = mRT[1];
                }
                // Baris "No. KK : ..." di file contoh menyatukan info KK & alamat di kolom A/C
                const cellC = (row[2] || '').toString();
                if (cellC.toUpperCase().includes('ALAMAT')) {
                    const mAlamat = cellC.match(/ALAMAT\s*:\s*(.*?),\s*NAMA DUSUN/i);
                    if (mAlamat) alamat = mAlamat[1].trim();
                    const mRT = cellC.match(/RT\/RW\s*:\s*(\d+)/i);
                    if (mRT) rt = mRT[1];
                }
                if (cellA.trim().toUpperCase() === 'NO' && (row[1] || '').toString().toUpperCase().includes('NAMA')) {
                    headerRowIdx = i;
                }
                if (cellA.trim().toUpperCase().startsWith('PERIODE')) {
                    const cellRT = (row[12] || '').toString();
                    let status = 'PERMANEN';
                    if (/NON PERMANEN/i.test(cellRT)) status = 'NON PERMANEN';
                    else if (/PERMANEN/i.test(cellRT)) status = 'PERMANEN';
                    statusHeaderList.push({ rowIndex: i, status: status });
                }
            }

            // Ambil status dari header PERIODE terdekat SEBELUM baris "No. KK" yang terbaca
            let statusPendudukHeader = 'PERMANEN';
            const acuanBaris = nomorKKRowIdx > -1 ? nomorKKRowIdx : rows.length;
            for (const h of statusHeaderList) {
                if (h.rowIndex <= acuanBaris) statusPendudukHeader = h.status;
                else break;
            }

            if (!nomorKK && headerRowIdx === -1) {
                alert('Format Excel tidak dikenali. Pastikan menggunakan format Data Keluarga standar.');
                return;
            }

            if (nomorKK) document.querySelector('input[name="nomor_kk"]').value = nomorKK;
            if (rt) document.querySelector('input[name="rt"]').value = rt.padStart(3, '0');
            if (alamat) document.querySelector('input[name="alamat_domisili"]').value = alamat;

            const dataRows = [];
            for (let i = (headerRowIdx > -1 ? headerRowIdx + 1 : 0); i < rows.length; i++) {
                const row = rows[i];
                const cellA = (row[0] || '').toString().trim();
                if (cellA.toUpperCase().startsWith('NO. KK')) continue;
                const nama = (row[1] || '').toString().trim();
                const nik = (row[2] || '').toString().trim();
                if (!nama && !nik) continue;
                dataRows.push(row);
            }

            if (dataRows.length === 0) {
                alert('Tidak ada data anggota yang terbaca dari file.');
                return;
            }

            const container = document.getElementById('anggotaContainer');
            container.innerHTML = '';

            dataRows.forEach((row) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = blokAnggotaKosongHTML().trim();
                const block = wrapper.firstChild;

                const nama = (row[1] || '').toString().trim();
                const nik = (row[2] || '').toString().trim();
                const tempatLahir = (row[3] || '').toString().trim();
                const tglLahir = excelDateToISO(row[4]);
                const jk = (row[5] || '').toString().trim().toUpperCase();
                const hubungan = (row[6] || '').toString().trim().toUpperCase();
                const agama = (row[7] || '').toString().trim().toUpperCase();
                const pendidikan = normalisasiPendidikan(row[8]);
                const pekerjaan = (row[9] || '').toString().trim();

                block.querySelector('[name="nama_lengkap[]"]').value = nama;
                block.querySelector('[name="nik[]"]').value = nik;
                block.querySelector('[name="tempat_lahir[]"]').value = tempatLahir;
                block.querySelector('[name="tanggal_lahir[]"]').value = tglLahir;
                setSelectValue(block, 'jenis_kelamin[]', jk);
                setSelectValue(block, 'hubungan_keluarga[]', hubungan);
                setSelectValue(block, 'agama[]', agama);
                setSelectValue(block, 'pendidikan_terakhir[]', pendidikan);
                block.querySelector('[name="pekerjaan[]"]').value = pekerjaan;
                setSelectValue(block, 'status_penduduk[]', statusPendudukHeader);
                setSelectValue(block, 'kewarganegaraan[]', 'WNI');

                container.appendChild(block);
            });

            renumberBlocks();
            document.getElementById('importStatus').textContent =
                `${dataRows.length} anggota berhasil diimpor. Cek ulang data sebelum menyimpan.`;
        }

        function renumberBlocks() {
            document.querySelectorAll('#anggotaContainer .anggota-block .block-num').forEach((el, idx) => {
                el.textContent = idx + 1;
            });
        }

        function hapusBlokAnggota(btn) {
            const container = document.getElementById('anggotaContainer');
            if (container.children.length <= 1) {
                alert('Minimal harus ada 1 anggota keluarga.');
                return;
            }
            if (confirm('Hapus anggota ini dari form?')) {
                btn.closest('.anggota-block').remove();
                renumberBlocks();
            }
        }

        function blokAnggotaKosongHTML() {
            return `
            <div class="anggota-block">
                <div class="anggota-block-header">
                    <h3>Anggota Keluarga <span class="block-num"></span></h3>
                    <button type="button" class="btn-hapus-block" onclick="hapusBlokAnggota(this)">&times; Hapus</button>
                </div>
                <input type="hidden" name="id_penduduk[]" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap[]" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor Induk Kependudukan</label>
                        <input type="text" name="nik[]" maxlength="16" pattern="\\d{16}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin[]" required>
                            <option value="">-- Pilih --</option>
                            <option value="LAKI-LAKI">LAKI-LAKI</option>
                            <option value="PEREMPUAN">PEREMPUAN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir[]" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir[]" required>
                    </div>
                    <div class="form-group">
                        <label>Agama</label>
                        <select name="agama[]" required>
                            <option value="">-- Pilih --</option>
                            <option value="ISLAM">ISLAM</option>
                            <option value="KRISTEN">KRISTEN</option>
                            <option value="KATOLIK">KATOLIK</option>
                            <option value="HINDU">HINDU</option>
                            <option value="BUDDHA">BUDDHA</option>
                            <option value="KONGHUCU">KONGHUCU</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pendidikan Terakhir</label>
                        <select name="pendidikan_terakhir[]" required>
                            <option value="">-- Pilih --</option>
                            <option value="TIDAK SEKOLAH">TIDAK SEKOLAH</option>
                            <option value="SD/SEDERAJAT">SD/SEDERAJAT</option>
                            <option value="SLTP/SEDERAJAT">SLTP/SEDERAJAT</option>
                            <option value="SLTA/SEDERAJAT">SLTA/SEDERAJAT</option>
                            <option value="DIPLOMA I">DIPLOMA I</option>
                            <option value="DIPLOMA II">DIPLOMA II</option>
                            <option value="DIPLOMA III">DIPLOMA III</option>
                            <option value="DIPLOMA IV/STRATA I">DIPLOMA IV/STRATA I</option>
                            <option value="STRATA II">STRATA II</option>
                            <option value="STRATA III">STRATA III</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Pekerjaan</label>
                        <input type="text" name="pekerjaan[]" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label>Status Hubungan Dalam Keluarga</label>
                        <select name="hubungan_keluarga[]" required>
                            <option value="KEPALA KELUARGA">KEPALA KELUARGA</option>
                            <option value="SUAMI">SUAMI</option>
                            <option value="ISTRI">ISTRI</option>
                            <option value="ANAK">ANAK</option>
                            <option value="MENANTU">MENANTU</option>
                            <option value="CUCU">CUCU</option>
                            <option value="ORANG TUA">ORANG TUA</option>
                            <option value="MERTUA">MERTUA</option>
                            <option value="FAMILI LAIN">FAMILI LAIN</option>
                            <option value="PEMBANTU/SOPIR/ASISTEN RUMAH TANGGA/PENGASUH">PEMBANTU/SOPIR/ASISTEN RUMAH TANGGA/PENGASUH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kewarganegaraan</label>
                        <select name="kewarganegaraan[]" required>
                            <option value="WNI">WNI</option>
                            <option value="WNA">WNA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Penduduk</label>
                        <select name="status_penduduk[]" required>
                            <option value="PERMANEN">PERMANEN</option>
                            <option value="NON PERMANEN">NON PERMANEN</option>
                            <option value="MENINGGAL">MENINGGAL</option>
                        </select>
                    </div>
                </div>
            </div>`;
        }

        document.getElementById('btnTambahAnggota').addEventListener('click', () => {
            const container = document.getElementById('anggotaContainer');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = blokAnggotaKosongHTML().trim();
            container.appendChild(wrapper.firstChild);
            renumberBlocks();
            container.lastElementChild.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        });

        renumberBlocks();
    </script>

</body>

</html>