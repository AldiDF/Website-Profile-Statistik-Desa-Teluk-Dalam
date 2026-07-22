<?php
session_start();

// TODO: ganti dengan query database sesungguhnya (SELECT dari tabel berita)
// Untuk sementara data di bawah ini masih dummy, hanya untuk tampilan.
$data_berita = [
    [
        'id_berita'    => 1,
        'judul'        => 'Musyawarah Desa Penyusunan RKP Tahun 2027',
        'kategori'     => 'Pemerintahan',
        'penulis'      => 'Admin Desa',
        'tanggal'      => '2026-07-15',
        'dilihat'      => 128,
        'status'       => 'Terbit',
        'thumbnail'    => 'https://placehold.co/80x60/0f4c3a/ffffff?text=Berita',
    ],
    [
        'id_berita'    => 2,
        'judul'        => 'Penyaluran Bantuan Langsung Tunai Tahap II',
        'kategori'     => 'Sosial',
        'penulis'      => 'Admin Desa',
        'tanggal'      => '2026-07-10',
        'dilihat'      => 342,
        'status'       => 'Terbit',
        'thumbnail'    => 'https://placehold.co/80x60/f4b400/2b2b28?text=Berita',
    ],
    [
        'id_berita'    => 3,
        'judul'        => 'Jadwal Posyandu Balita Bulan Agustus',
        'kategori'     => 'Kesehatan',
        'penulis'      => 'Bidan Desa',
        'tanggal'      => '2026-07-05',
        'dilihat'      => 76,
        'status'       => 'Draf',
        'thumbnail'    => 'https://placehold.co/80x60/2a78d6/ffffff?text=Berita',
    ],
    [
        'id_berita'    => 4,
        'judul'        => 'Gotong Royong Pembersihan Saluran Irigasi',
        'kategori'     => 'Kegiatan',
        'penulis'      => 'Admin Desa',
        'tanggal'      => '2026-06-28',
        'dilihat'      => 201,
        'status'       => 'Terbit',
        'thumbnail'    => 'https://placehold.co/80x60/898781/ffffff?text=Berita',
    ],
    [
        'id_berita'    => 5,
        'judul'        => 'Pengumuman Libur Pelayanan Kantor Desa',
        'kategori'     => 'Pengumuman',
        'penulis'      => 'Admin Desa',
        'tanggal'      => '2026-06-20',
        'dilihat'      => 89,
        'status'       => 'Arsip',
        'thumbnail'    => 'https://placehold.co/80x60/b91c1c/ffffff?text=Berita',
    ],
];

$total_berita = count($data_berita);
$total_terbit = count(array_filter($data_berita, fn($b) => $b['status'] === 'Terbit'));
$total_draf   = count(array_filter($data_berita, fn($b) => $b['status'] === 'Draf'));
$total_arsip  = count(array_filter($data_berita, fn($b) => $b['status'] === 'Arsip'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Desa Teluk Dalam</title>
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
        .stat-card.terbit { border-left-color: #15803d; }
        .stat-card.draf { border-left-color: var(--emas); }
        .stat-card.arsip { border-left-color: #b91c1c; }

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

        .header-actions {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            padding: 0.55rem 0.9rem;
            border: 1.5px solid var(--border-soft);
            border-radius: 8px;
            width: 260px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .search-box:focus {
            outline: none;
            border-color: var(--emas);
            box-shadow: 0 0 0 3px rgba(244, 180, 0, 0.18);
        }

        .filter-select {
            padding: 0.55rem 0.9rem;
            border: 1.5px solid var(--border-soft);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
            color: #2b2b28;
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--emas);
        }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 1000px; }
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
            vertical-align: middle;
        }
        tbody td.nowrap { white-space: nowrap; }
        tbody tr:hover { background: #faf9f5; }

        .thumb {
            width: 64px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
        }

        .judul-cell { max-width: 320px; }
        .judul-cell .judul { font-weight: 600; color: #2b2b28; }

        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge.terbit { background: #dcfce7; color: #15803d; }
        .badge.draf { background: #fef9c3; color: #a16207; }
        .badge.arsip { background: #fee2e2; color: #b91c1c; }

        .kategori-tag {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #eef2ff;
            color: #3730a3;
            white-space: nowrap;
        }

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
            white-space: nowrap;
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
        a.btn[href^="hapus_berita"] { color: #b91c1c; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
            <h1>Dashboard Admin - Kependudukan Teluk Dalam</h1>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php">Data Penduduk</a>
            <a href="kelola_berita.php" class="active">Kelola Berita</a>
        </div>
        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
        </div>
    </nav>

    <div class="container">

        <!-- STATISTIK -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Berita</div>
                <div class="value"><?= $total_berita ?></div>
            </div>
            <div class="stat-card terbit">
                <div class="label">Terbit</div>
                <div class="value"><?= $total_terbit ?></div>
            </div>
            <div class="stat-card draf">
                <div class="label">Draf</div>
                <div class="value"><?= $total_draf ?></div>
            </div>
            <div class="stat-card arsip">
                <div class="label">Arsip</div>
                <div class="value"><?= $total_arsip ?></div>
            </div>
        </div>

        <!-- TABEL DATA -->
        <div class="table-card">
            <div class="table-header">
                <h2>Data Berita</h2>
                <div class="header-actions">
                    <input type="text" id="searchInput" class="search-box" placeholder="Cari judul, kategori, penulis...">
                    <select id="statusFilter" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="terbit">Terbit</option>
                        <option value="draf">Draf</option>
                        <option value="arsip">Arsip</option>
                    </select>
                    <a href="berita_detail.php" class="btn-tambah">+ Tambah Berita</a>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Judul Berita</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Tanggal Terbit</th>
                            <th>Dilihat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_berita as $i => $b): ?>
                        <tr data-status="<?= strtolower($b['status']) ?>">
                            <td class="nowrap"><?= $i + 1 ?></td>
                            <td><img src="<?= htmlspecialchars($b['thumbnail']) ?>" alt="Thumbnail" class="thumb"></td>
                            <td class="judul-cell">
                                <div class="judul"><?= htmlspecialchars($b['judul']) ?></div>
                            </td>
                            <td class="nowrap"><span class="kategori-tag"><?= htmlspecialchars($b['kategori']) ?></span></td>
                            <td class="nowrap"><?= htmlspecialchars($b['penulis']) ?></td>
                            <td class="nowrap"><?= htmlspecialchars(date('d-m-Y', strtotime($b['tanggal']))) ?></td>
                            <td class="nowrap"><?= htmlspecialchars($b['dilihat']) ?>x</td>
                            <td class="nowrap">
                                <?php $statusClass = strtolower($b['status']); ?>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($b['status']) ?></span>
                            </td>
                            <td class="nowrap">
                                <a href="berita_detail.php?id_berita=<?= urlencode($b['id_berita']) ?>" class="btn">Edit</a>
                                <a href="hapus_berita.php?id_berita=<?= urlencode($b['id_berita']) ?>" class="btn" onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="no-result" id="noResult">Tidak ada berita yang cocok.</div>
            </div>
        </div>

    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const table = document.getElementById('dataTable');
        const rows = table.querySelectorAll('tbody tr');
        const noResult = document.getElementById('noResult');

        function applyFilter() {
            const keyword = searchInput.value.toLowerCase();
            const status = statusFilter.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matchKeyword = text.includes(keyword);
                const matchStatus = !status || row.dataset.status === status;
                const match = matchKeyword && matchStatus;
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            noResult.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        searchInput.addEventListener('keyup', applyFilter);
        statusFilter.addEventListener('change', applyFilter);
    </script>

</body>
</html>