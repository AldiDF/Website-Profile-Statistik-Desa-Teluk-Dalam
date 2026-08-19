<?php
require '../databases/auth_check.php';
require '../databases/connection.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$upload_dir      = '../databases/bagan_desa/';
$ekstensi_valid  = ['jpg', 'jpeg', 'png', 'webp'];
$maks_ukuran_mb  = 5;

// ==========================
// Ambil data profil_desa saat ini (id selalu 1)
// ==========================
function ambil_profil_desa($conn)
{
    $q = mysqli_query($conn, "SELECT visi, misi, bagan_gambar FROM profil_desa WHERE id = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }
    return ['visi' => '', 'misi' => '', 'bagan_gambar' => null];
}

$error = '';

// ==========================
// PROSES SIMPAN (POST)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visi = trim($_POST['visi'] ?? '');
    // Setiap baris di textarea = 1 poin misi, baris kosong dibuang
    $misi_baris = array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $_POST['misi'] ?? ''))), fn($l) => $l !== '');
    $misi = implode("\n", $misi_baris);

    $profil_sekarang = ambil_profil_desa($conn);
    $bagan_gambar_final = $profil_sekarang['bagan_gambar'];

    // Hapus gambar bagan (kalau dicentang, dan tidak sedang upload gambar baru)
    $hapus_bagan = isset($_POST['hapus_bagan']) && $_POST['hapus_bagan'] === '1';

    // ==========================
    // UPLOAD GAMBAR BAGAN BARU (kalau ada)
    // ==========================
    if (isset($_FILES['bagan_gambar']) && $_FILES['bagan_gambar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bagan_gambar'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ukuran_mb = $file['size'] / 1024 / 1024;

        if (!in_array($ekstensi, $ekstensi_valid, true)) {
            $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        } elseif ($ukuran_mb > $maks_ukuran_mb) {
            $error = 'Ukuran gambar terlalu besar. Maksimal ' . $maks_ukuran_mb . ' MB.';
        } elseif (!getimagesize($file['tmp_name'])) {
            $error = 'File yang diunggah bukan gambar yang valid.';
        } else {
            $nama_file_baru = 'bagan-desa-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ekstensi;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file_baru)) {
                // Hapus file lama supaya tidak menumpuk sampah di folder assets
                if (!empty($profil_sekarang['bagan_gambar']) && file_exists($upload_dir . $profil_sekarang['bagan_gambar'])) {
                    @unlink($upload_dir . $profil_sekarang['bagan_gambar']);
                }
                $bagan_gambar_final = $nama_file_baru;
            } else {
                $error = 'Gagal menyimpan file gambar ke server.';
            }
        }
    } elseif ($hapus_bagan) {
        if (!empty($profil_sekarang['bagan_gambar']) && file_exists($upload_dir . $profil_sekarang['bagan_gambar'])) {
            @unlink($upload_dir . $profil_sekarang['bagan_gambar']);
        }
        $bagan_gambar_final = null;
    }

    if ($error === '') {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO profil_desa (id, visi, misi, bagan_gambar)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE visi = VALUES(visi), misi = VALUES(misi), bagan_gambar = VALUES(bagan_gambar)
        ");
        mysqli_stmt_bind_param($stmt, "sss", $visi, $misi, $bagan_gambar_final);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: profile?status=sukses");
            exit;
        } else {
            $error = 'Gagal menyimpan ke database: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

$profil = ambil_profil_desa($conn);
$visi_tampil = $profil['visi'] ?? '';
$misi_tampil = $profil['misi'] ?? '';
$bagan_tampil = $profil['bagan_gambar'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Desa - Dashboard Admin</title>
    <link rel="icon" href="assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styless/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>

<body>

    <nav class="navbar">
        <div class="brand">
            <img src="assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
            <h1>Dashboard Admin - Kependudukan Teluk Dalam</h1>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Buka menu" type="button">&#9776;</button>

        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
        </div>

        <div class="nav-menu" id="navMenu">
            <a href="dashboard">Data Penduduk</a>
            <a href="profile" class="active">Profil Desa</a>
            <a href="databases/logout.php">Keluar</a>
        </div>
    </nav>

    <div class="container">

        <div class="page-title">
            <h2>Kelola Profil Desa</h2>
            <p>Perubahan di sini akan langsung tampil pada halaman Beranda (Visi Misi &amp; Bagan Struktur Organisasi).</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
            <div class="alert-status">Profil desa berhasil diperbarui.</div>
        <?php endif; ?>

        <form action="profile" method="POST" enctype="multipart/form-data" id="formProfil">
            <div class="form-grid">

                <div class="form-card full">
                    <h3><i class="fa-solid fa-bullseye"></i> Visi Desa</h3>
                    <p class="hint">Satu kalimat visi utama desa.</p>
                    <label class="field-label" for="visiInput">Visi</label>
                    <textarea name="visi" id="visiInput"><?= htmlspecialchars($visi_tampil) ?></textarea>
                </div>

                <div class="form-card full">
                    <h3><i class="fa-solid fa-list-check"></i> Misi Desa</h3>
                    <p class="hint">Satu poin misi per baris — setiap baris akan otomatis diberi nomor urut di halaman Beranda.</p>
                    <label class="field-label" for="misiInput">Misi (satu baris = satu poin)</label>
                    <textarea name="misi" id="misiInput"><?= htmlspecialchars($misi_tampil) ?></textarea>
                </div>

                <div class="form-card full">
                    <h3><i class="fa-solid fa-sitemap"></i> Bagan Struktur Organisasi</h3>
                    <p class="hint">Unggah gambar bagan struktur organisasi desa. Format JPG/PNG/WEBP, maksimal <?= $maks_ukuran_mb ?> MB.</p>

                    <div class="dropzone" id="dropzone">
                        <input type="file" name="bagan_gambar" id="baganInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                        <p><strong>Klik untuk pilih gambar</strong> atau tarik &amp; lepas (drag &amp; drop) di sini</p>
                        <p class="dz-sub">JPG, PNG, atau WEBP — maks <?= $maks_ukuran_mb ?> MB</p>
                    </div>

                    <div class="dz-preview-wrap<?= $bagan_tampil !== '' && $bagan_tampil !== null ? ' show' : '' ?>" id="dzPreviewWrap">
                        <img src="<?= $bagan_tampil ? 'databases/bagan_desa/' . htmlspecialchars($bagan_tampil) . '?v=' . time() : '' ?>" alt="Pratinjau bagan struktur organisasi" id="dzPreviewImg">
                        <p class="dz-preview-caption" id="dzPreviewCaption">
                            <?= $bagan_tampil ? 'Gambar bagan saat ini' : '' ?>
                        </p>

                        <?php if ($bagan_tampil): ?>
                            <label class="dz-remove-check">
                                <input type="checkbox" name="hapus_bagan" id="hapusBaganCheck" value="1">
                                Hapus gambar bagan ini (tanpa menggantinya dengan yang baru)
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
            </div>
        </form>

    </div>

    <script src="scriptss/dashboard.js"></script>

</body>

</html>