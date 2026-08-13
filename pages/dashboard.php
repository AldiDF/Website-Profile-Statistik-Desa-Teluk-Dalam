<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
require 'dashboard_helpers.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$filter                 = ambil_filter_dari_get();
$rt_filter               = $filter['rt'];
$status_penduduk_filter  = $filter['status_penduduk'];
$tampilan_khusus         = $filter['tampilan'];

$daftar_rt = [];
$queryRT = "SELECT DISTINCT CAST(rt AS UNSIGNED) AS rt_num FROM keluarga ORDER BY rt_num ASC";
$resultRT = mysqli_query($conn, $queryRT);
if ($resultRT) {
    while ($rowRT = mysqli_fetch_assoc($resultRT)) {
        $daftar_rt[] = (int) $rowRT['rt_num'];
    }
    mysqli_free_result($resultRT);
}

// Filter pencarian TIDAK dipakai di load awal (pencarian ditangani AJAX oleh dashboard_load.php)
$filter_awal = $filter;
$filter_awal['search'] = '';
$where = build_where_penduduk($conn, $filter_awal);

// ==========================
// STATISTIK dihitung langsung di database (COUNT/SUM), BUKAN dari menarik
// semua baris ke PHP. Ini tetap cepat walau datanya ribuan baris.
// ==========================
$stat            = ambil_statistik($conn, $where);
$total_penduduk  = $stat['total_penduduk'];
$total_kk        = $stat['total_kk'];
$total_laki      = $stat['total_laki'];
$total_perempuan = $stat['total_perempuan'];

// ==========================
// HANYA ambil 100 baris pertama dari database (LIMIT/OFFSET).
// Ini inti perbaikan performa: dulu SEMUA baris ditarik lalu disembunyikan
// pakai JS, sekarang database sendiri yang membatasi jumlah baris yang dikirim.
// ==========================
const HALAMAN_AWAL = 100;
$data_penduduk = ambil_data_penduduk($conn, $where, HALAMAN_AWAL, 0);
$jumlah_dimuat_awal = count($data_penduduk);

$grouped = group_by_kk($data_penduduk);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kependudukan Desa Teluk Dalam</title>
    <link rel="icon" href="assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styless/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <a href="dashboard" class="active">Data Penduduk</a>
            <a href="struktur_desa">Perangkat Desa</a>
            <a href="databases/logout.php">Keluar</a>
        </div>
    </nav>

    <div class="container">

        <?php if (isset($_GET['status'])): ?>
            <?php
            $pesan_status = [
                'sukses'   => 'Data berhasil disimpan.',
                'hapus_kk' => 'KK beserta seluruh anggotanya berhasil dihapus.',
            ];
            ?>
            <?php if (isset($pesan_status[$_GET['status']])): ?>
                <div class="alert-status"><?= $pesan_status[$_GET['status']] ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- STATISTIK -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Penduduk<?= $rt_filter !== "" ? " (RT $rt_filter)" : "" ?><?= $status_penduduk_filter !== "" ? " - " . ucwords(strtolower($status_penduduk_filter)) : "" ?></div>
                <div class="value"><?= $total_penduduk ?></div>
            </div>
            <div class="stat-card kk">
                <div class="label">Total Keluarga</div>
                <div class="value"><?= $total_kk ?></div>
            </div>
            <div class="stat-card laki">
                <div class="label">Laki-laki</div>
                <div class="value"><?= $total_laki ?></div>
            </div>
            <div class="stat-card perempuan">
                <div class="label">Perempuan</div>
                <div class="value"><?= $total_perempuan ?></div>
            </div>
        </div>

        <!-- FILTER RT -->
        <div class="rt-filter">
            <a href="dashboard<?= $status_penduduk_filter !== "" ? "?status_penduduk=" . urlencode($status_penduduk_filter) : "" ?>" class="<?= $rt_filter === "" ? "active" : "" ?>">Semua RT</a>
            <?php foreach ($daftar_rt as $rtValue): ?>
                <a href="dashboard?rt=<?= $rtValue ?><?= $status_penduduk_filter !== "" ? "&status_penduduk=" . urlencode($status_penduduk_filter) : "" ?>"
                    class="<?= ($rt_filter !== "" && (int) $rt_filter === $rtValue) ? "active" : "" ?>">
                    RT <?= $rtValue ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- FILTER STATUS PENDUDUK (TETAP / TIDAK TETAP) -->
        <div class="rt-filter">
            <a href="dashboard<?= $rt_filter !== "" ? "?rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "" ? "active" : "" ?>">Semua Status</a>
            <a href="dashboard?status_penduduk=PERMANEN<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "PERMANEN" ? "active" : "" ?>">Penduduk Tetap</a>
            <a href="dashboard?status_penduduk=NON+PERMANEN<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "NON PERMANEN" ? "active" : "" ?>">Penduduk Tidak Tetap</a>
            <a href="dashboard?status_penduduk=MENINGGAL<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>"
                class="<?= $status_penduduk_filter === "MENINGGAL" ? "active" : "" ?>">
                Meninggal
            </a>
            <a href="dashboard?status_penduduk=TIDAK+LENGKAP<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>"
                class="<?= $status_penduduk_filter === "TIDAK LENGKAP" ? "active" : "" ?>">
                Data Tidak Lengkap
            </a>
        </div>
        <div class="table-card">
            <div class="table-header">
                <h2>Data Kependudukan<?= $rt_filter !== "" ? " - RT $rt_filter" : "" ?><?php
                                                                                        if ($status_penduduk_filter !== "") {
                                                                                            $label_status = [
                                                                                                'PERMANEN'     => 'Penduduk Tetap',
                                                                                                'NON PERMANEN' => 'Penduduk Tidak Tetap',
                                                                                                'MENINGGAL'    => 'Meninggal',
                                                                                                'TIDAK LENGKAP' => 'Data Tidak Lengkap',
                                                                                            ];
                                                                                            echo " - " . ($label_status[$status_penduduk_filter] ?? $status_penduduk_filter);
                                                                                        }
                                                                                        ?></h2>
                <div class="table-actions">
                    <input type="text" id="searchInput" class="search-box" placeholder="Cari NIK, nama, alamat, dll...">
                    <a href="import_massal" class="btn btn-import">📥 Import Massal</a>
                    <a href="data_detail" class="btn-tambah">+ Tambah Data</a>
                </div>
            </div>

            <div class="table-wrapper" data-lenis-prevent>
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Nama Lengkap</th>
                            <th>Tempat, Tanggal Lahir</th>
                            <th>Umur</th>
                            <th>Jenis Kelamin</th>
                            <th>Hubungan Dalam Keluarga</th>
                            <th>Agama</th>
                            <th>Pendidikan Terakhir</th>
                            <th>Pekerjaan</th>
                            <th>Kewarganegaraan</th>
                            <th>Status Penduduk</th>
                        </tr>
                    </thead>

                    <?= render_grup_html($grouped) ?>
                </table>
                <div class="no-result" id="noResult">Tidak ada data yang cocok.</div>
            </div>
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="load-more-wrap" id="loadMoreWrap">
                <button type="button" class="btn-load-more" id="loadMoreBtn">Tampilkan 100 Berikutnya</button>
            </div>
        </div>

    </div>
    
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
    <script src="scriptss/leniss.js"></script>
    <script src="scriptss/dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initDashboard({
                filterRt: <?= json_encode($rt_filter) ?>,
                filterStatus: <?= json_encode($status_penduduk_filter) ?>,
                filterTampilan: <?= json_encode($tampilan_khusus) ?>,
                jumlahDimuatAwal: <?= (int) $jumlah_dimuat_awal ?>,
                totalPenduduk: <?= (int) $total_penduduk ?>
            });
        });
    </script>

</body>

</html>