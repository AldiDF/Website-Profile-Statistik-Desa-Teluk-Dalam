<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
require '../databases/data_output.php';
require '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$mode = "tambah";
$title_page = "Tambah Perangkat Desa";
$error = "";
$id = null;

$data = [
    "id"           => "",
    "nama_lengkap" => "",
    "jabatan"      => "",
    "foto"         => "",
];

function bersihkan_input(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = strtoupper($value);
    return $value;
}

// ==========================
// TENTUKAN MODE (Tambah / Edit)
// ==========================
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $mode = "edit";
    $title_page = "Edit Perangkat Desa";
    $id = (int) $_GET['id'];

    $row = ambil_struktur_desa_by_id($conn, $id);
    if ($row === null) {
        die("Data perangkat desa tidak ditemukan.");
    }
    $data = $row;
}

// ==========================
// PROSES SIMPAN (Tambah / Update)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_mode = $_POST['form_mode'] === 'edit' ? 'edit' : 'tambah';
    $mode = $post_mode;
    $title_page = $mode === 'edit' ? "Edit Perangkat Desa" : "Tambah Perangkat Desa";

    $nama_lengkap = bersihkan_input($_POST['nama_lengkap'] ?? '');

    // Ambil dari select. Jika isinya "Lainnya", ambil dari input text.
    $jabatan_select = $_POST['jabatan_select'] ?? '';
    if ($jabatan_select === 'Lainnya') {
        $jabatan = bersihkan_input($_POST['jabatan_teks'] ?? '');
    } else {
        $jabatan = bersihkan_input($jabatan_select);
    }
    $foto_lama    = trim($_POST['foto_lama'] ?? '');
    $id           = isset($_POST['id']) && is_numeric($_POST['id']) ? (int) $_POST['id'] : null;

    $data = [
        "id"           => $id,
        "nama_lengkap" => $nama_lengkap,
        "jabatan"      => $jabatan,
        "foto"         => $foto_lama,
    ];

    if ($nama_lengkap === '') {
        $error = "Nama Lengkap wajib diisi.";
    } elseif (strlen($nama_lengkap) > 50) {
        $error = "Nama Lengkap maksimal 50 karakter.";
    } elseif ($jabatan === '') {
        $error = "Jabatan wajib diisi.";
    } elseif (strlen($jabatan) > 50) {
        $error = "Jabatan maksimal 50 karakter.";
    } else {
        // ==========================
        // VALIDASI JABATAN UNIK (HANYA BOLEH 1 PER JABATAN INTI)
        // ==========================
        $jabatan_unik = [
            'KEPALA DESA',
            'SEKRETARIS DESA',
            'KAUR KEUANGAN',
            'KAUR TATA USAHA DAN UMUM',
            'KAUR PERENCANAAN',
            'KASI PEMERINTAHAN',
            'KASI KESEJAHTERAAN',
            'KASI PELAYANAN'
        ];

        // Cek apakah jabatan yang diinput termasuk dalam daftar jabatan yang tidak boleh ganda
        if (in_array(strtoupper($jabatan), $jabatan_unik)) {
            $jabatan_escape = mysqli_real_escape_string($conn, strtolower($jabatan));
            $q_cek = "SELECT id FROM struktur_desa WHERE LOWER(jabatan) = '$jabatan_escape'";

            if ($mode === 'edit' && $id) {
                // Abaikan ID yang sedang diedit
                $q_cek .= " AND id != $id";
            }

            $res_cek = mysqli_query($conn, $q_cek);
            if ($res_cek && mysqli_num_rows($res_cek) > 0) {
                // Format nama jabatan untuk ditampilkan di pesan error (contoh: KEPALA DESA -> Kepala Desa)
                $jabatan_format = ucwords(strtolower($jabatan));
                $error = "Jabatan {$jabatan_format} sudah terisi. Tidak dapat menambahkan lebih dari satu {$jabatan_format}.";
            }
        }

        if (empty($error)) {
            $nama_file_foto = $foto_lama;

            // ==========================
            // PROSES UPLOAD FOTO 
            // ==========================
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
                $ukuran_maks        = 2 * 1024 * 1024; // 2 MB

                $ekstensi_asli = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $ukuran_file   = $_FILES['foto']['size'];
                $tmp_path      = $_FILES['foto']['tmp_name'];

                if (!in_array($ekstensi_asli, $ekstensi_diizinkan, true)) {
                    $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
                } elseif ($ukuran_file > $ukuran_maks) {
                    $error = "Ukuran foto maksimal 2 MB.";
                } else {
                    $info_gambar = @getimagesize($tmp_path);
                    if ($info_gambar === false) {
                        $error = "File yang diupload bukan gambar yang valid.";
                    } else {
                        $folder_tujuan = '../databases/photo/';
                        if (!is_dir($folder_tujuan)) {
                            mkdir($folder_tujuan, 0755, true);
                        }

                        $nama_file_baru = uniqid('struktur_', true) . '.' . $ekstensi_asli;
                        if (move_uploaded_file($tmp_path, $folder_tujuan . $nama_file_baru)) {
                            if ($mode === 'edit' && $foto_lama !== '' && file_exists($folder_tujuan . $foto_lama)) {
                                unlink($folder_tujuan . $foto_lama);
                            }
                            $nama_file_foto = $nama_file_baru;
                        } else {
                            $error = "Gagal menyimpan file foto ke server.";
                        }
                    }
                }
            }

            // ==========================
            // SIMPAN KE DATABASE 
            // ==========================
            if (empty($error)) {
                $data['foto'] = $nama_file_foto;

                if ($mode === 'edit') {
                    if ($id === null) {
                        $error = "ID data tidak valid.";
                    } else {
                        $stmt = edit_struktur_desa($conn, $nama_lengkap, $jabatan, $nama_file_foto, $id);
                        if (!mysqli_stmt_execute($stmt)) {
                            $error = "Gagal memperbarui data struktur desa.";
                        } else {
                            mysqli_stmt_close($stmt);
                            header("Location: struktur_desa.php?status=sukses");
                            exit;
                        }
                    }
                } else {
                    $stmt = tambah_struktur_desa($conn, $nama_lengkap, $jabatan, $nama_file_foto);
                    if (!mysqli_stmt_execute($stmt)) {
                        $error = "Gagal menyimpan data struktur desa baru.";
                    } else {
                        mysqli_stmt_close($stmt);
                        header("Location: struktur_desa.php?status=sukses");
                        exit;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title_page ?> - Desa Teluk Dalam</title>
    <link rel="icon" href="../assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../styless/dashboard.css">
    <link rel="stylesheet" href="../styless/data_detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f1f5f9;
        }

        .form-container {
            max-width: 560px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.8rem 2rem 2.2rem;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h1 {
            font-size: 1.3rem;
            color: #1e293b;
        }

        .badge-mode {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.7rem;
            border-radius: 12px;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.7rem 1rem;
            border-radius: 6px;
            font-size: 0.88rem;
            margin-bottom: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* --- Drag & Drop Style --- */
        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .drop-zone.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .foto-preview {
            width: 150px;
            height: 180px;
            border-radius: 100px / 115px;
            object-fit: cover;
            background: #fff;
            border: 3px solid #e2e8f0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        /* Tampilan khusus untuk icon unggah (sebelum ada foto) */
        .foto-preview.is-placeholder {
            border-radius: 10px;
            border: none;
            box-shadow: none;
            object-fit: contain;
            background: transparent;
            width: 64px;
            height: 64px;
            margin-bottom: -10px;
        }

        .drop-zone-text {
            font-size: 0.85rem;
            color: #64748b;
        }

        .hint {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.7rem;
            margin-top: 1.8rem;
            padding-top: 1.2rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn-cancel {
            padding: 0.6rem 1.3rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #f8fafc;
        }
    </style>
</head>

<body>

    <div class="topbar">
        <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
        <span>Desa Teluk Dalam - Admin</span>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1><?= $title_page ?></h1>
            <span class="badge-mode <?= $mode ?>"><?= $mode === 'edit' ? 'Edit Perangkat Desa' : 'Tambah Perangkat Desa' ?></span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_mode" value="<?= $mode ?>">
            <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($data['foto'] ?? '') ?>">
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $data['id']) ?>">
            <?php endif; ?>

            <!-- Urutan diubah: Foto -> Nama -> Jabatan -->
            <div class="form-group">
                <label>Foto Perangkat Desa <span class="hint">(JPG/PNG/WEBP, maksimal 2MB)</span></label>
                <?php
                // Icon SVG Base64 untuk "Unggah Gambar"
                $icon_unggah = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'%3E%3C/circle%3E%3Cpolyline points='21 15 16 10 5 21'%3E%3C/polyline%3E%3C/svg%3E";

                $ada_foto = (!empty($data['foto']) && file_exists('../assets/struktur/' . $data['foto']));
                $foto_preview_src = $ada_foto ? '../assets/struktur/' . htmlspecialchars($data['foto']) : $icon_unggah;
                ?>
                <div class="drop-zone" id="dropZoneFoto">
                    <img src="<?= $foto_preview_src ?>" alt="Preview Foto" class="foto-preview <?= $ada_foto ? '' : 'is-placeholder' ?>" id="fotoPreview">
                    <div class="drop-zone-text" id="dropZoneText">
                        Klik di sini atau <strong>Tarik & Lepas (Drag & Drop)</strong> file foto
                    </div>
                    <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" maxlength="50"
                    value="<?= htmlspecialchars($data['nama_lengkap'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Jabatan</label>
                <select id="jabatanSelect" name="jabatan_select" onchange="toggleJabatanLainnya(this.value)" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <?php
                    $jabatan_umum = [
                        'KEPALA DESA',
                        'SEKRETARIS DESA',
                        'KAUR KEUANGAN',
                        'KAUR TATA USAHA DAN UMUM',
                        'KAUR PERENCANAAN',
                        'KASI PEMERINTAHAN',
                        'KASI KESEJAHTERAAN',
                        'KASI PELAYANAN',
                    ];

                    $nilai_jabatan = htmlspecialchars($data['jabatan'] ?? '');
                    $is_lainnya = !empty($nilai_jabatan) && !in_array($nilai_jabatan, array_map('strtoupper', $jabatan_umum));

                    foreach ($jabatan_umum as $j) {
                        $selected = (strtoupper($j) === strtoupper($nilai_jabatan)) ? 'selected' : '';
                        echo "<option value=\"$j\" $selected>$j</option>";
                    }
                    ?>
                    <option value="Lainnya" <?= $is_lainnya ? 'selected' : '' ?>>Jabatan Lainnya...</option>
                </select>

                <!-- Input Teks muncul jika "Lainnya" dipilih -->
                <input type="text" id="jabatanInputLainnya" name="jabatan_teks" maxlength="50" placeholder="Ketik jabatan lainnya..."
                    value="<?= $is_lainnya ? $nilai_jabatan : '' ?>"
                    style="margin-top: 0.5rem; <?= $is_lainnya ? 'display:block;' : 'display:none;' ?>">
            </div>

            <div class="form-footer">
                <a href="struktur_desa.php" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-save">
                    <?= $mode === 'edit' ? 'Update Data' : 'Simpan Data' ?>
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/noumanqamar450/alertbox@main/version/1.0.2/alertbox.min.js"></script>

    <script>
        const dropZone = document.getElementById('dropZoneFoto');
        const fotoInput = document.getElementById('fotoInput');
        const fotoPreview = document.getElementById('fotoPreview');
        const dropZoneText = document.getElementById('dropZoneText');

        // Klik area untuk membuka dialog file
        dropZone.addEventListener('click', () => fotoInput.click());

        // Mencegah default behavior saat drag & drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
            dropZoneText.innerHTML = "<strong>Lepaskan file di sini</strong>";
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
            dropZoneText.innerHTML = "Klik di sini atau <strong>Tarik & Lepas (Drag & Drop)</strong> file foto";
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            dropZoneText.innerHTML = "Klik di sini atau <strong>Tarik & Lepas (Drag & Drop)</strong> file foto";

            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                fotoInput.files = e.dataTransfer.files;
                tampilkanPreview(e.dataTransfer.files[0]);
            }
        });

        // Event change jika menggunakan dialog klik
        fotoInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                tampilkanPreview(e.target.files[0]);
            }
        });

        function tampilkanPreview(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(evt) {
                fotoPreview.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        }

        function tampilkanPreview(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(evt) {
                fotoPreview.src = evt.target.result;
                // Hapus class placeholder agar bentuknya berubah menjadi elips vertikal
                fotoPreview.classList.remove('is-placeholder');
            };
            reader.readAsDataURL(file);
        }

        function toggleJabatanLainnya(val) {
            const inputLainnya = document.getElementById('jabatanInputLainnya');
            if (val === 'Lainnya') {
                inputLainnya.style.display = 'block';
                inputLainnya.setAttribute('required', 'required');
            } else {
                inputLainnya.style.display = 'none';
                inputLainnya.removeAttribute('required');
                inputLainnya.value = ''; // Kosongkan saat disembunyikan
            }
        }
    </script>

</body>

</html>