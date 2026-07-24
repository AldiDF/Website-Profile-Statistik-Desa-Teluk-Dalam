<?php
session_start();
require '../databases/connection.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}
$rt_filter = "";
if (isset($_GET['rt']) && $_GET['rt'] !== "") {
    // Ambil hanya digitnya saja, supaya "1", "01", "001" dianggap sama
    $rt_digits = preg_replace('/\D/', '', $_GET['rt']);
    if ($rt_digits !== "") {
        $rt_filter = $rt_digits;
    }
}

// Daftar RT yang ditampilkan sebagai tombol filter (tetap RT 1 - 4)
$daftar_rt = [1, 2, 3, 4];

$data_penduduk = [];

$query = "
    SELECT
        p.id_penduduk,
        p.nik,
        k.id_keluarga,
        k.nomor_kk,
        p.nama_lengkap,
        p.tempat_lahir,
        p.tanggal_lahir,
        p.jenis_kelamin,
        p.agama,
        p.status_perkawinan,
        p.pekerjaan,
        p.pendidikan_terakhir,
        p.kewarganegaraan,
        p.status_penduduk,
        p.hubungan_keluarga,
        k.rt,
        k.alamat_domisili
    FROM penduduk p
    JOIN keluarga k ON p.id_keluarga_fk = k.id_keluarga
";

if ($rt_filter !== "") {
    $query .= " WHERE CAST(k.rt AS UNSIGNED) = " . (int) $rt_filter . " ";
}

$query .= " ORDER BY k.rt ASC, k.nomor_kk ASC, p.id_penduduk ASC ";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_penduduk[] = $row;
    }
    mysqli_free_result($result);
} else {
    die("Gagal mengambil data: " . mysqli_error($conn));
}

$total_penduduk  = count($data_penduduk);
$total_kk        = count(array_unique(array_column($data_penduduk, 'nomor_kk')));
$total_laki      = count(array_filter($data_penduduk, fn($p) => $p['jenis_kelamin'] === 'Laki-laki'));
$total_perempuan = count(array_filter($data_penduduk, fn($p) => $p['jenis_kelamin'] === 'Perempuan'));

// ==========================
// KELOMPOKKAN DATA PER KK (meniru struktur excel)
// ==========================
$grouped = [];
foreach ($data_penduduk as $p) {
    $kk = $p['nomor_kk'];
    if (!isset($grouped[$kk])) {
        $grouped[$kk] = [
            'id_keluarga'     => $p['id_keluarga'],
            'nomor_kk'        => $kk,
            'alamat_domisili' => $p['alamat_domisili'],
            'rt'              => $p['rt'],
            'anggota'         => [],
        ];
    }
    $grouped[$kk]['anggota'][] = $p;
}

// Urutkan anggota dalam tiap keluarga: Kepala Keluarga dulu, baru yang lain
$prioritas_hubungan = [
    'KEPALA KELUARGA' => 0,
    'SUAMI'           => 1,
    'ISTRI'           => 1,
    'ANAK'            => 2,
];
foreach ($grouped as &$kel) {
    usort($kel['anggota'], function ($a, $b) use ($prioritas_hubungan) {
        $pa = $prioritas_hubungan[strtoupper($a['hubungan_keluarga'] ?? '')] ?? 3;
        $pb = $prioritas_hubungan[strtoupper($b['hubungan_keluarga'] ?? '')] ?? 3;
        return $pa <=> $pb;
    });
}
unset($kel);

// ==========================
// HELPER: HITUNG UMUR
// ==========================
function hitung_umur($tanggal_lahir)
{
    if (empty($tanggal_lahir) || $tanggal_lahir === '0000-00-00') {
        return '-';
    }
    try {
        $lahir    = new DateTime($tanggal_lahir);
        $sekarang = new DateTime();
        $diff     = $lahir->diff($sekarang);
        return "{$diff->y} Thn {$diff->m} Bln {$diff->d} Hari";
    } catch (Exception $e) {
        return '-';
    }
}
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

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', 'Segoe UI', Arial, sans-serif; }
        body { background: var(--bg); color: #2b2b28; }

        .navbar {
            background: var(--hijau-tua);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .navbar .brand img {
            width: 35px;
            height: 40px;
            border-radius: 50%;
            display: block;
        }
        .navbar h1 { font-size: 1.15rem; font-weight: 600; }
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
            background: rgba(255,255,255,0.1);
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
        }

        .container { padding: 1.5rem 2rem; }

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
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            border-left: 5px solid var(--hijau-tua);
        }
        .stat-card.kk { border-left-color: var(--emas); }
        .stat-card.laki { border-left-color: #2a78d6; }
        .stat-card.perempuan { border-left-color: #e87ba4; }

        .stat-card .label { font-size: 0.85rem; color: var(--abu-teks); margin-bottom: 0.3rem; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--hijau-tua); }

        .table-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .table-header h2 { font-size: 1.1rem; color: var(--hijau-tua); font-weight: 600; }

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

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 1500px; }
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
        tbody tr:hover { background: #faf9f5; }

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
        .btn-edit-kk:hover { filter: brightness(0.95); }

        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.aktif { background: #dcfce7; color: #15803d; }
        .badge.pindah { background: #fef9c3; color: #a16207; }
        .badge.meninggal { background: #fee2e2; color: #b91c1c; }

        .no-result { text-align: center; padding: 2rem; color: #94a3b8; display: none; }

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
        .btn-tambah:hover { background: var(--hijau-gelap); }

        .btn {
            color: var(--hijau-tua);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            margin-right: 0.4rem;
        }
        .btn:hover { text-decoration: underline; }
        a.btn[href^="delete_data"] { color: #b91c1c; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
            <h1>Dashboard Admin - Kependudukan Teluk Dalam</h1>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="active">Data Penduduk</a>
            <a href="beranda.php">Keluar</a>
        </div>
        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
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
                <div class="label">Total Penduduk<?= $rt_filter !== "" ? " (RT $rt_filter)" : "" ?></div>
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
            <a href="dashboard.php" class="<?= $rt_filter === "" ? "active" : "" ?>">Semua RT</a>
            <?php foreach ($daftar_rt as $rtValue): ?>
                <a href="dashboard.php?rt=<?= $rtValue ?>"
                    class="<?= ($rt_filter !== "" && (int) $rt_filter === $rtValue) ? "active" : "" ?>">
                    RT <?= $rtValue ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- TABEL DATA -->
        <div class="table-card">
            <div class="table-header">
                <h2>Data Kependudukan<?= $rt_filter !== "" ? " - RT $rt_filter" : "" ?></h2>
                <div style="display:flex; gap:0.6rem; align-items:center;">
                    <input type="text" id="searchInput" class="search-box" placeholder="Cari NIK, nama, alamat, dll...">
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
                            <th>Status Perkawinan</th>
                            <th>Pendidikan Terakhir</th>
                            <th>Pekerjaan</th>
                            <th>Kewarganegaraan</th>
                            <th>Status Penduduk</th>
                        </tr>
                    </thead>

                    <?php if (empty($grouped)): ?>
                        <tbody>
                            <tr><td colspan="13" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada data.</td></tr>
                        </tbody>
                    <?php else: ?>
                        <?php foreach ($grouped as $kel): ?>
                            <tbody class="kk-group">
                                <tr class="kk-header-row">
                                    <th colspan="13">
                                        <span class="kk-tag">No. KK: <?= htmlspecialchars($kel['nomor_kk'] ?? '-') ?></span>
                                        <span class="rt-tag">RT <?= htmlspecialchars($kel['rt'] ?? '-') ?></span>
                                        Alamat: <?= htmlspecialchars($kel['alamat_domisili'] ?? '-') ?>
                                        <a class="btn-edit-kk"
                                            href="data_detail.php?id_keluarga=<?= urlencode($kel['id_keluarga'] ?? '') ?>">
                                            Edit
                                        </a>
                                    </th>
                                </tr>
                                <?php foreach ($kel['anggota'] as $i => $p): ?>
                                    <tr class="data-row">
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($p['nik'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['nama_lengkap'] ?? '') ?></td>
                                        <td>
                                            <?= htmlspecialchars($p['tempat_lahir'] ?? '') ?>,
                                            <?= !empty($p['tanggal_lahir']) ? htmlspecialchars(date('d-m-Y', strtotime($p['tanggal_lahir']))) : '-' ?>
                                        </td>
                                        <td><?= htmlspecialchars(hitung_umur($p['tanggal_lahir'] ?? null)) ?></td>
                                        <td><?= htmlspecialchars($p['jenis_kelamin'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['hubungan_keluarga'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['agama'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['status_perkawinan'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['pendidikan_terakhir'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['pekerjaan'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['kewarganegaraan'] ?? '') ?></td>
                                        <td>
                                            <?php $statusClass = strtolower($p['status_penduduk'] ?? ''); ?>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($p['status_penduduk'] ?? '') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
                <div class="no-result" id="noResult">Tidak ada data yang cocok.</div>
            </div>
        </div>

    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const groups = document.querySelectorAll('#dataTable tbody.kk-group');
        const noResult = document.getElementById('noResult');

        searchInput.addEventListener('keyup', function () {
            const keyword = this.value.toLowerCase();
            let visibleCount = 0;

            groups.forEach(group => {
                const dataRows = group.querySelectorAll('tr.data-row');
                let groupHasMatch = false;

                dataRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = text.includes(keyword);
                    row.style.display = match ? '' : 'none';
                    if (match) {
                        groupHasMatch = true;
                        visibleCount++;
                    }
                });

                // Sembunyikan header KK juga kalau tidak ada anggota yang cocok
                group.style.display = groupHasMatch ? '' : 'none';
            });

            noResult.style.display = visibleCount === 0 ? 'block' : 'none';
        });
    </script>

</body>
</html>