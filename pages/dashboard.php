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
    <title>Dashboard Admin - Desa Teluk Dalam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../styless/dashboard.css">
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

        .navbar {
            background: var(--hijau-tua);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            position: relative;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .navbar .brand img {
            width: 35px;
            height: 40px;
            border-radius: 50%;
            display: block;
            flex-shrink: 0;
        }

        .navbar h1 {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .navbar .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.2rem 0.5rem;
            line-height: 1;
        }

        .navbar .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .navbar .nav-menu a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            opacity: 0.85;
            transition: background 0.2s, opacity 0.2s;
        }

        .navbar .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            opacity: 1;
        }

        .navbar .nav-menu a.active {
            background: var(--emas);
            color: var(--hijau-gelap);
            opacity: 1;
        }

        .navbar .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar .halo {
            font-size: 0.9rem;
            font-weight: 300;
            background: rgba(244, 180, 0, 0.15);
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            border: 1px solid rgba(244, 180, 0, 0.4);
            white-space: nowrap;
        }

        .container {
            padding: 1.5rem 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-status {
            background: #dcfce7;
            color: #15803d;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(21, 128, 61, 0.2);
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--hijau-tua);
        }

        .stat-card.kk {
            border-left-color: var(--emas);
        }

        .stat-card.laki {
            border-left-color: #2a78d6;
        }

        .stat-card.perempuan {
            border-left-color: #e87ba4;
        }

        .stat-card .label {
            font-size: 0.85rem;
            color: var(--abu-teks);
            margin-bottom: 0.3rem;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua);
        }

        .table-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .table-header h2 {
            font-size: 1.1rem;
            color: var(--hijau-tua);
            font-weight: 600;
        }

        .search-box {
            padding: 0.55rem 0.9rem;
            border: 1.5px solid var(--border-soft);
            border-radius: 8px;
            width: 280px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--emas);
            box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.18);
        }

        /* ===== FILTER RT ===== */
        .rt-filter {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }

        .rt-filter a {
            text-decoration: none;
            color: var(--hijau-tua);
            background: #fff;
            border: 1.5px solid var(--border-soft);
            padding: 0.5rem 1.1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .rt-filter a:hover {
            border-color: var(--emas);
        }

        .rt-filter a.active {
            background: var(--hijau-tua);
            color: #fff;
            border-color: var(--hijau-tua);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 1500px;
        }

        thead th {
            background: #f3f2ec;
            text-align: left;
            padding: 0.7rem 0.6rem;
            border-bottom: 2px solid var(--border-soft);
            white-space: nowrap;
            color: var(--hijau-tua);
            font-weight: 600;
        }

        tbody td {
            padding: 0.6rem;
            border-bottom: 1px solid #f1f0ea;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #faf9f5;
        }

        /* ===== HEADER GRUP PER KK (mirip struktur excel) ===== */
        .kk-header-row th {
            background: #eaf3ee;
            color: var(--hijau-gelap);
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.6rem 0.7rem;
            border-top: 2px solid var(--hijau-tua);
            border-bottom: 1px solid var(--border-soft);
            white-space: normal;
            text-align: left;
        }

        .kk-header-row .kk-tag {
            display: inline-block;
            background: var(--hijau-tua);
            color: #fff;
            padding: 0.15rem 0.6rem;
            border-radius: 6px;
            margin-right: 0.6rem;
            font-size: 0.78rem;
        }

        .kk-header-row .rt-tag {
            display: inline-block;
            background: var(--emas);
            color: var(--hijau-gelap);
            padding: 0.15rem 0.6rem;
            border-radius: 6px;
            margin-right: 0.6rem;
            font-size: 0.78rem;
        }

        .btn-edit-kk {
            float: right;
            text-decoration: none;
            background: var(--emas);
            color: var(--hijau-gelap);
            padding: 0.2rem 0.7rem;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            transition: filter 0.2s;
        }

        .btn-edit-kk:hover {
            filter: brightness(0.95);
        }

        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.aktif {
            background: #dcfce7;
            color: #15803d;
        }

        .badge.pindah {
            background: #fef9c3;
            color: #a16207;
        }

        .badge.meninggal {
            background: #fee2e2;
            color: #b91c1c;
        }

        .no-result {
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
            display: none;
        }

        .pagination-info {
            text-align: center;
            padding: 0.8rem 0 0.2rem;
            color: var(--abu-teks);
            font-size: 0.85rem;
        }

        .load-more-wrap {
            display: flex;
            justify-content: center;
            padding: 1rem 0 0.2rem;
        }

        .btn-load-more {
            background: #fff;
            color: var(--hijau-tua);
            border: 1.5px solid var(--hijau-tua);
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .btn-load-more:hover {
            background: var(--hijau-tua);
            color: #fff;
        }

        .btn-tambah {
            background: var(--hijau-tua);
            color: #fff;
            border: none;
            padding: 0.55rem 1.1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.2s;
        }

        .btn-tambah:hover {
            background: var(--hijau-gelap);
        }

        .btn {
            color: var(--hijau-tua);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            margin-right: 0.4rem;
        }

        .table-actions {
            display: flex;
            gap: 0.6rem;
            align-items: center;
        }

        .btn-import {
            border: 1px solid var(--hijau-tua);
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            white-space: nowrap;
        }

        .btn:hover {
            text-decoration: underline;
        }

        a.btn[href^="delete_data"] {
            color: #b91c1c;
        }

        /* =====================================================
           RESPONSIVE - MOBILE
           Catatan: khusus TABEL data, kita TIDAK membuatnya jadi
           "stack/card" di mobile. Tabel tetap dalam bentuk tabel
           dan cukup discroll secara horizontal (lihat .table-wrapper
           di atas: overflow-x:auto + table min-width). Yang dibuat
           responsive di sini adalah elemen di LUAR tabel (navbar,
           statistik, filter, search, tombol, dsb).
        ===================================================== */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.8rem 1rem;
            }

            .navbar h1 {
                font-size: 1rem;
            }

            .navbar .nav-toggle {
                display: block;
            }

            .navbar .nav-menu {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 0.3rem;
                order: 3;
            }

            .navbar .nav-menu.open {
                display: flex;
            }

            .navbar .nav-menu a {
                text-align: center;
                padding: 0.7rem;
            }

            .navbar .nav-right {
                order: 2;
            }

            .container {
                padding: 1rem 0.85rem;
            }

            /* Statistik: 2 kolom di tablet/mobile besar */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.7rem;
            }

            .stat-card {
                padding: 0.9rem 1rem;
            }

            .stat-card .label {
                font-size: 0.75rem;
            }

            .stat-card .value {
                font-size: 1.4rem;
            }

            .table-card {
                padding: 1rem 0.85rem;
                border-radius: 10px;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .table-header h2 {
                font-size: 1rem;
            }

            /* Aksi tabel (search + tombol) ditumpuk penuh selebar layar */
            .table-actions {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }

            .search-box {
                width: 100%;
            }

            .btn-import,
            .btn-tambah {
                width: 100%;
                text-align: center;
                justify-content: center;
                margin-right: 0;
            }

            /* Filter RT & Status: tombol lebih ringkas & tetap bisa wrap */
            .rt-filter {
                gap: 0.4rem;
                margin-bottom: 1rem;
            }

            .rt-filter a {
                padding: 0.45rem 0.85rem;
                font-size: 0.78rem;
            }

            /* Hanya area tabel yang discroll horizontal, bukan seluruh halaman */
            .table-wrapper {
                -webkit-overflow-scrolling: touch;
                margin: 0 -0.85rem;
                padding: 0 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.6rem;
            }

            .stat-card .value {
                font-size: 1.25rem;
            }

            .navbar h1 {
                font-size: 0.9rem;
            }

            .navbar .brand img {
                width: 30px;
                height: 34px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="brand">
            <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
            <h1>Dashboard Admin - Kependudukan Teluk Dalam</h1>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Buka menu" type="button">&#9776;</button>

        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
        </div>

        <div class="nav-menu" id="navMenu">
            <a href="dashboard.php" class="active">Data Penduduk</a>
            <a href="../databases/logout.php">Keluar</a>
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
            <a href="dashboard.php<?= $status_penduduk_filter !== "" ? "?status_penduduk=" . urlencode($status_penduduk_filter) : "" ?>" class="<?= $rt_filter === "" ? "active" : "" ?>">Semua RT</a>
            <?php foreach ($daftar_rt as $rtValue): ?>
                <a href="dashboard.php?rt=<?= $rtValue ?><?= $status_penduduk_filter !== "" ? "&status_penduduk=" . urlencode($status_penduduk_filter) : "" ?>"
                    class="<?= ($rt_filter !== "" && (int) $rt_filter === $rtValue) ? "active" : "" ?>">
                    RT <?= $rtValue ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- FILTER STATUS PENDUDUK (TETAP / TIDAK TETAP) -->
        <div class="rt-filter">
            <a href="dashboard.php<?= $rt_filter !== "" ? "?rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "" ? "active" : "" ?>">Semua Status</a>
            <a href="dashboard.php?status_penduduk=PERMANEN<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "PERMANEN" ? "active" : "" ?>">Penduduk Tetap</a>
            <a href="dashboard.php?status_penduduk=NON+PERMANEN<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>" class="<?= $status_penduduk_filter === "NON PERMANEN" ? "active" : "" ?>">Penduduk Tidak Tetap</a>
            <a href="dashboard.php?status_penduduk=MENINGGAL<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>"
                class="<?= $status_penduduk_filter === "MENINGGAL" ? "active" : "" ?>"
                >
                Meninggal
            </a>
            <a href="dashboard.php?status_penduduk=TIDAK+LENGKAP<?= $rt_filter !== "" ? "&rt=" . urlencode($rt_filter) : "" ?>"
                class="<?= $status_penduduk_filter === "TIDAK LENGKAP" ? "active" : "" ?>"
                >
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
                    <a href="import_massal.php" class="btn btn-import">📥 Import Massal</a>
                    <a href="data_detail.php" class="btn-tambah">+ Tambah Data</a>
                </div>
            </div>

            <div class="table-wrapper">
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

    <script>
        const searchInput   = document.getElementById('searchInput');
        const dataTable     = document.getElementById('dataTable');
        const noResult      = document.getElementById('noResult');
        const paginationInfo = document.getElementById('paginationInfo');
        const loadMoreWrap  = document.getElementById('loadMoreWrap');
        const loadMoreBtn   = document.getElementById('loadMoreBtn');

        const PAGE_SIZE = 100;

        // Filter yang sedang aktif di halaman (dari PHP), dikirim juga ke AJAX
        // supaya load-more/pencarian tetap konsisten dengan filter RT/status yang dipilih.
        const FILTER_RT     = <?= json_encode($rt_filter) ?>;
        const FILTER_STATUS = <?= json_encode($status_penduduk_filter) ?>;
        const FILTER_TAMPILAN = <?= json_encode($tampilan_khusus) ?>;

        let offset       = <?= (int) $jumlah_dimuat_awal ?>; // sudah dimuat dari PHP saat pertama buka halaman
        let totalPenduduk = <?= (int) $total_penduduk ?>;
        let searchDebounce = null;
        let searchToken   = 0; // supaya respons AJAX yang telat/kadaluarsa tidak menimpa hasil terbaru

        updatePaginationInfo(<?= (int) $jumlah_dimuat_awal ?>, totalPenduduk, false);
        loadMoreWrap.style.display = (<?= (int) $jumlah_dimuat_awal ?> < totalPenduduk) ? 'flex' : 'none';

        function buildQuery(params) {
            const usp = new URLSearchParams(params);
            if (FILTER_RT) usp.set('rt', FILTER_RT);
            if (FILTER_STATUS) usp.set('status_penduduk', FILTER_STATUS);
            if (FILTER_TAMPILAN) usp.set('tampilan', FILTER_TAMPILAN);
            return usp.toString();
        }

        function updatePaginationInfo(shown, total, isSearch) {
            if (total > 0) {
                paginationInfo.textContent = isSearch
                    ? 'Ditemukan ' + total + ' data cocok (menampilkan ' + shown + ')'
                    : 'Menampilkan ' + shown + ' dari ' + total + ' penduduk';
                paginationInfo.style.display = 'block';
            } else {
                paginationInfo.style.display = 'none';
            }
            noResult.style.display = shown === 0 ? 'block' : 'none';
        }

        // ==========================
        // Ambil satu "halaman" data dari server (dashboard_load.php) lewat AJAX.
        // append=false -> ganti isi tabel (dipakai saat mulai cari / reset pencarian)
        // append=true  -> tambahkan di bawah data yang sudah ada (tombol Load More)
        // ==========================
        async function muatData(offsetVal, keyword, append) {
            const myToken = ++searchToken;
            const qs = buildQuery({ offset: offsetVal, limit: PAGE_SIZE, search: keyword });

            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = 'Memuat...';

            try {
                const res = await fetch('dashboard_load.php?' + qs);
                const data = await res.json();

                if (myToken !== searchToken) return; // ada request lebih baru, abaikan yang ini

                if (!append) {
                    dataTable.querySelectorAll('tbody').forEach(tb => tb.remove());
                }
                dataTable.insertAdjacentHTML('beforeend', data.html);

                offset = data.offset;
                totalPenduduk = data.total;

                const shownNow = dataTable.querySelectorAll('tr.data-row').length;
                updatePaginationInfo(shownNow, totalPenduduk, keyword !== '');
                loadMoreWrap.style.display = data.has_more ? 'flex' : 'none';
            } catch (e) {
                console.error('Gagal memuat data:', e);
            } finally {
                loadMoreBtn.disabled = false;
                loadMoreBtn.textContent = 'Tampilkan 100 Berikutnya';
            }
        }

        // ==========================
        // Live search: dicari LANGSUNG di database (server-side), dengan debounce
        // supaya tidak menembak query di setiap ketukan tombol.
        // ==========================
        searchInput.addEventListener('keyup', function() {
            // Huruf yang diketik otomatis dijadikan huruf besar (uppercase),
            // sambil menjaga posisi kursor tetap di tempat semula.
            const selStart = this.selectionStart;
            const selEnd = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(selStart, selEnd);

            const keyword = this.value.trim();

            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function() {
                muatData(0, keyword, false);
            }, 350);
        });

        loadMoreBtn.addEventListener('click', function() {
            muatData(offset, searchInput.value.trim(), true);
        });

        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
        });
    </script>

</body>

</html>