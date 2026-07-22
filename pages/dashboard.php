<?php
session_start();
require '../databases/connection.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}
$data_penduduk = [];
$query = "
    SELECT
        p.id_penduduk,
        p.nik,
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
        k.rt,
        k.alamat_domisili
    FROM penduduk p
    JOIN keluarga k ON p.id_keluarga_fk = k.id_keluarga
    ORDER BY p.id_penduduk DESC
";

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Desa Teluk Dalam</title>
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

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 1400px; }
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
            <a href="kelola_berita.php">Kelola Berita</a>
        </div>
        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
        </div>
    </nav>

    <div class="container">

        <!-- STATISTIK -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Penduduk</div>
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

        <!-- TABEL DATA -->
        <div class="table-card">
            <div class="table-header">
                <h2>Data Kependudukan</h2>
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
                            <th>Nomor Kartu Keluarga</th>
                            <th>Nomor Induk Kependudukan</th>
                            <th>Nama Lengkap</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Agama</th>
                            <th>Status Perkawinan</th>
                            <th>Pekerjaan</th>
                            <th>Pendidikan Terakhir</th>
                            <th>Kewarganegaraan</th>
                            <th>RT</th>
                            <th>Alamat Domisili</th>
                            <th>Status Penduduk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_penduduk as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($p['nomor_kk']) ?></td>
                            <td><?= htmlspecialchars($p['nik']) ?></td>
                            <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($p['tempat_lahir']) ?></td>
                            <td><?= htmlspecialchars(date('d-m-Y', strtotime($p['tanggal_lahir']))) ?></td>
                            <td><?= htmlspecialchars($p['jenis_kelamin']) ?></td>
                            <td><?= htmlspecialchars($p['agama']) ?></td>
                            <td><?= htmlspecialchars($p['status_perkawinan']) ?></td>
                            <td><?= htmlspecialchars($p['pekerjaan']) ?></td>
                            <td><?= htmlspecialchars($p['pendidikan_terakhir']) ?></td>
                            <td><?= htmlspecialchars($p['kewarganegaraan']) ?></td>
                            <td><?= htmlspecialchars($p['rt']) ?></td>
                            <td><?= htmlspecialchars($p['alamat_domisili']) ?></td>
                            <td>
                                <?php
                                    $statusClass = strtolower($p['status_penduduk']);
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($p['status_penduduk']) ?></span>
                            </td>
                            <td>
                                <a href="data_detail.php?id_penduduk=<?= urlencode($p['id_penduduk']) ?>" class="btn">Edit</a>
                                <a href="delete_data.php?id_penduduk=<?= urlencode($p['id_penduduk']) ?>" class="btn" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="no-result" id="noResult">Tidak ada data yang cocok.</div>
            </div>
        </div>

    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('dataTable');
        const rows = table.querySelectorAll('tbody tr');
        const noResult = document.getElementById('noResult');

        searchInput.addEventListener('keyup', function () {
            const keyword = this.value.toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(keyword);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            noResult.style.display = visibleCount === 0 ? 'block' : 'none';
        });
    </script>

</body>
</html>