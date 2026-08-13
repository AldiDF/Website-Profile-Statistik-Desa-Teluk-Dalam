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

function tentukan_kelengkapan(array $d, bool $kkTidakLengkap = false): string
{
    if ($kkTidakLengkap) {
        return 'TIDAK LENGKAP';
    }

    $wajib = [
        'nik',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'pekerjaan',
        'pendidikan_terakhir',
        'kewarganegaraan',
        'hubungan_keluarga',
    ];
    foreach ($wajib as $f) {
        if (empty($d[$f])) {
            return 'TIDAK LENGKAP';
        }
    }
    if (strlen((string) $d['nik']) !== 16) {
        return 'TIDAK LENGKAP';
    }

    return 'LENGKAP';
}

if (isset($_GET['id_keluarga']) && is_numeric($_GET['id_keluarga'])) {
    $mode = "edit";
    $title_page = "Edit Data Keluarga";
    $id_keluarga = (int) $_GET['id_keluarga'];
}
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
                $stmtCek = mysqli_prepare($conn, "SELECT id_keluarga FROM keluarga WHERE nomor_kk = ? AND id_keluarga != ?");
                mysqli_stmt_bind_param($stmtCek, "si", $keluarga['nomor_kk'], $id_keluarga);
                mysqli_stmt_execute($stmtCek);
                $hasilCek = mysqli_stmt_get_result($stmtCek);
                if (mysqli_fetch_assoc($hasilCek)) {
                    throw new Exception("Nomor KK tersebut sudah dipakai oleh keluarga lain.");
                }
                mysqli_stmt_close($stmtCek);
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
            foreach ($niks as $i => $nik) {
                $currentId = trim($id_penduduks[$i] ?? '');

                if ($currentId !== '') {
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
                    // Hitung DULU sebelum dibandingkan, dan IKUT dimasukkan ke $data_baru
                    // supaya perbandingan $ada_perubahan juga mendeteksi kalau CUMA status_lengkap
                    // yang berubah (misal data lama salah tersimpan sebagai TIDAK LENGKAP padahal sebenarnya lengkap).
                    $data_baru['status_lengkap'] = tentukan_kelengkapan($data_baru);

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
                        continue;
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
                        (int) $currentId,
                        'LENGKAP'
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
                        trim($hubungans[$i]),
                        "LENGKAP"
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
            header("Location: dashboard?status=sukses");
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

function opsi_select($nama_opsi, $daftar, $terpilih)
{
    $html = '';
    foreach ($daftar as $opt) {
        $selected = ($terpilih === $opt) ? 'selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($opt) . "\" $selected>" . htmlspecialchars($opt) . "</option>";
    }
    return $html;
}

function bersihkan_input(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = strtoupper($value);
    return $value;
}

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
                        'DIPLOMA I/II/III',
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
                <input type="text" name="hubungan_keluarga[]" maxlength="20" value="<?= htmlspecialchars($a['hubungan_keluarga'] ?? '') ?>" required>
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
    <link rel="icon" href="assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styless/data_detail.css">
</head>

<body>

    <div class="topbar">
        <img src="assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
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
                        <span class="badge-mode <?= $mode ?>"><?= $mode === 'edit' ? 'Edit Data Kependudukan' : 'Tambah Data Kependudukan' ?></span>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <p class="section-title">Data Keluarga</p>
                    <div class="form-grid" style="grid-template-columns: 2fr 1fr;"> <!-- Grid diubah jadi 2 kolom: KK lebih lebar, RT lebih sempit -->
                        <div class="form-group"> <!-- Hapus class form-group-full di sini -->
                            <label>Nomor Kartu Keluarga <span class="hint">(16 digit)</span></label>
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
                    <div id="anggotaContainer">
                        <?php foreach ($anggota_list as $i => $a): ?>
                            <?= render_anggota_block($a, $i + 1) ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-tambah-anggota" id="btnTambahAnggota">+ Tambah Anggota Keluarga</button>
                </div>
                <div class="form-footer">
                    <?php if ($mode === 'edit'): ?>
                        <a href="delete?id_keluarga=<?= htmlspecialchars((string) $id_keluarga) ?>"
                            class="btn-hapus-kk"
                            onclick="return confirm('Yakin ingin menghapus seluruh KK ini beserta SEMUA anggotanya? Tindakan ini tidak bisa dibatalkan.')">
                            Hapus KK
                        </a>
                    <?php endif; ?>
                    <a href="dashboard" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-save">
                        <?= $mode === 'edit' ? 'Update Data' : 'Konfirmasi Penambahan Data' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
    <script src="scriptss/leniss.js"></script>
    <script src="scriptss/data_detail.js"></script>

</body>

</html>