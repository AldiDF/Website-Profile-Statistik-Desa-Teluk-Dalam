<?php
session_start();
require '../databases/connection.php'; // file koneksi database ($conn)
include '../databases/model.php';
include '../databases/data_output.php';
include '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}


$mode = "tambah";
$title_page = "Tambah Data Penduduk";
$error = "";

// Data default (kosong) untuk mode tambah
$form_data_penduduk = [
    "id_penduduk" => "",
    "nik" => "",
    "nama_lengkap" => "",
    "tempat_lahir" => "",
    "tanggal_lahir" => "",
    "jenis_kelamin" => "",
    "agama" => "",
    "" => "",
    "pekestatus_perkawinanrjaan" => "",
    "pendidikan_terakhir" => "",
    "kewarganegaraan" => "",
    "status_penduduk" => "Aktif",
    "nomor_kk" => "",
    "rt" => "",
    "hubungan_keluarga" => "",
    "alamat_domisili" => "",
];

// ==========================
// CEK MODE: TAMBAH ATAU EDIT
// ==========================
if (isset($_GET['id_penduduk']) && is_numeric($_GET['id_penduduk'])) {
    $mode = "edit";
    $title_page = "Edit Data Penduduk";
    $id = (int) $_GET['id_penduduk'];

    // JOIN ke tabel keluarga supaya nomor_kk & rt ikut terambil
    $form_data_penduduk = ambil_data_penduduk($conn, $id, $form_data_penduduk);
}

// ==========================
// PROSES SIMPAN (TAMBAH/UPDATE)
// ==========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik                 = trim($_POST['nik']);
    $nama_lengkap        = trim($_POST['nama_lengkap']);
    $tempat_lahir        = trim($_POST['tempat_lahir']);
    $tanggal_lahir       = trim($_POST['tanggal_lahir']);
    $jenis_kelamin       = trim($_POST['jenis_kelamin']);
    $agama               = trim($_POST['agama']);
    $status_perkawinan   = trim($_POST['status_perkawinan']);
    $pekerjaan           = trim($_POST['pekerjaan']);
    $pendidikan_terakhir = trim($_POST['pendidikan_terakhir']);
    $kewarganegaraan     = trim($_POST['kewarganegaraan']);
    $alamat_domisili     = trim($_POST['alamat_domisili']);
    $status_penduduk     = trim($_POST['status_penduduk']);
    $hubungan_keluarga   = trim($_POST['hubungan_keluarga']);
    $post_mode           = trim($_POST['form_mode']);
    $nomor_kk            = trim($_POST['nomor_kk']);
    $rt                  = trim($_POST['rt']);

    if (strlen($nik) !== 16 || !ctype_digit($nik)) {
        $error = "NIK harus terdiri dari 16 digit angka.";
    } elseif (strlen($nomor_kk) !== 16 || !ctype_digit($nomor_kk)) {
        $error = "Nomor KK harus terdiri dari 16 digit angka.";
    } else {

        // ==========================================
        // LANGKAH 1: CEK / SIAPKAN id_keluarga
        // ==========================================
        $id_keluarga = cek_id_keluarga($conn, $nomor_kk, $rt, $alamat_domisili);

        // ==========================================
        // LANGKAH 2: INSERT / UPDATE ke tabel penduduk
        // ==========================================
        if (empty($error) && $id_keluarga !== null) {

            if ($post_mode === "edit") {
                $post_id = (int) $_POST['id'];
                $stmt = edit_data_penduduk(
                    $conn,
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
            } else {
                $stmt = tambah_data_penduduk(
                    $conn,
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
            }

            if (mysqli_stmt_execute($stmt)) {
                header("Location: dashboard.php?status=sukses");
                exit;
            } else {
                if (mysqli_errno($conn) === 1062) {
                    $error = "NIK sudah terdaftar. Gunakan NIK lain.";
                } else {
                    $error = "Terjadi kesalahan saat menyimpan data penduduk.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Jika error, tampilkan ulang data yang diinput user (bukan dari DB)
    if (!empty($error)) {
        $form_data_penduduk = $_POST;
    }
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
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
            max-width: 780px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            padding: 1.8rem 2rem 2.2rem;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.7rem;
            margin-top: 1.8rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border-soft);
        }

        .btn-cancel {
            padding: 0.65rem 1.3rem;
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
            transition: background 0.2s;
        }

        .btn-cancel:hover {
            background: #f6f5f1;
        }

        .btn-save {
            padding: 0.65rem 1.3rem;
            border-radius: 8px;
            border: none;
            background: var(--hijau-tua);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-save:hover {
            background: var(--hijau-gelap);
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-container {
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
        <div class="form-header">
            <h1><?= $title_page ?></h1>
            <span class="badge-mode <?= $mode ?>"><?= $mode === 'edit' ? 'Mode Edit' : 'Mode Tambah' ?></span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="form_mode" value="<?= $mode ?>">
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($form_data_penduduk['id_penduduk']) ?>">
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Nomor Kartu Keluarga <span class="hint">(16 digit, harus unik)</span></label>
                    <?php if ($mode == 'edit'): ?>
                        <input type="text" name="nomor_kk" maxlength="16" pattern="\d{16}"
                            value="<?= htmlspecialchars($form_data_penduduk['nomor_kk']) ?>" required readonly>

                    <?php else: ?>
                        <input type="text" name="nomor_kk" maxlength="16" pattern="\d{16}"
                            value="<?= htmlspecialchars($form_data_penduduk['nomor_kk']) ?>" required>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Hubungan Dalam Keluarga </label>
                    <input type="text" name="hubungan_keluarga"
                        value="<?= htmlspecialchars($form_data_penduduk['hubungan_keluarga']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Nomor Induk kependudukan (NIK)</label>
                    <input type="text" name="nik" maxlength="16" pattern="\d{16}"
                        value="<?= htmlspecialchars($form_data_penduduk['nik']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" maxlength="100"
                        value="<?= htmlspecialchars($form_data_penduduk['nama_lengkap']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" maxlength="50"
                        value="<?= htmlspecialchars($form_data_penduduk['tempat_lahir']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir"
                        value="<?= htmlspecialchars($form_data_penduduk['tanggal_lahir']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Laki-laki', 'Perempuan'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['jenis_kelamin'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agama</label>
                    <select name="agama" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['agama'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status Perkawinan</label>
                    <select name="status_perkawinan" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['status_perkawinan'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pekerjaan</label>
                    <input type="text" name="pekerjaan" maxlength="100"
                        value="<?= htmlspecialchars($form_data_penduduk['pekerjaan']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Tidak Sekolah', 'SD', 'SMP', 'SMA/SMK', 'Diploma', 'S1', 'S2', 'S3'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['pendidikan_terakhir'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kewarganegaraan</label>
                    <select name="kewarganegaraan" required>
                        <?php foreach (['WNI', 'WNA'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['kewarganegaraan'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Alamat Domisili</label>
                    <?php if ($mode == 'edit'): ?>
                        <input type="text" name="alamat_domisili" maxlength="255"
                            value="<?= htmlspecialchars($form_data_penduduk['alamat_domisili']) ?>" required readonly>
                    <?php else: ?>
                        <input type="text" name="alamat_domisili" maxlength="255"
                            value="<?= htmlspecialchars($form_data_penduduk['alamat_domisili']) ?>" required>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>RT</label>
                    <?php if ($mode == 'edit'): ?>
                        <input type="text" name="rt" maxlength="3"
                            value="<?= htmlspecialchars($form_data_penduduk['rt']) ?>" required readonly>
                    <?php else: ?>
                        <input type="text" name="rt" maxlength="3"
                            value="<?= htmlspecialchars($form_data_penduduk['rt']) ?>" required>
                    <?php endif; ?>
                </div>

                <div class="form-group form-group-full">
                    <label>Status Penduduk</label>
                    <select name="status_penduduk" required>
                        <?php foreach (['Aktif', 'Pindah', 'Meninggal'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $form_data_penduduk['status_penduduk'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-footer">
                <a href="dashboard.php" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-save">
                    <?= $mode === 'edit' ? 'Update Data' : 'Simpan Data' ?>
                </button>
            </div>
        </form>
    </div>
    </div>

</body>

</html>