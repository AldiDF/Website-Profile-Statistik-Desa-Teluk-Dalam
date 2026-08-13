<?php
require '../databases/auth_check.php';
require '../databases/connection.php';
require '../databases/data_output.php';
require '../databases/data_input.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$daftar_struktur = ambil_semua_struktur_desa($conn);

// ==========================
// Pisahkan Kepala Desa dan Perangkat Lainnya
// ==========================
$kepala_desa = null;
$perangkat_lain = [];

foreach ($daftar_struktur as $s) {
    if ($s['jabatan'] === 'KEPALA DESA') {
        $kepala_desa = $s;
    } else {
        $perangkat_lain[] = $s;
    }
}

// Ikon profil default (SVG) jika foto belum ada
$icon_default = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perangkat Desa Teluk Dalam</title>
    <link rel="icon" href="../assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../styless/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .struktur-org-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        /* --- KEPALA DESA WRAPPER --- */
        .kades-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .kades-card {
            border: 2px solid #f4b400;
            /* Border emas khusus Kades */
            transform: scale(1.05);
            /* Sedikit lebih besar */
            box-shadow: 0 6px 16px rgba(244, 180, 0, 0.15) !important;
        }

        /* --- PERANGKAT LAIN GRID (Max 5 per baris) --- */
        /* --- PERANGKAT LAIN GRID (Max 5 per baris) --- */
        .perangkat-lain-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            /* Mengubah rata tengah menjadi rata kiri */
            gap: 1.2rem;
            width: 100%;
        }

        .struktur-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.4rem 1rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
            display: flex;
            flex-direction: column;
            align-items: center;

            /* Agar maksimal 5 card per baris:
                100% dibagi 5 = 20%. 
               Dikurangi gap (1.2rem * 4 celah / 5 card) sekitar 0.96rem */
            flex: 0 0 calc(20% - 0.96rem);

            /* Mengatur batas minimal dan maksimal agar rapi */
            min-width: 180px;
            max-width: calc(20% - 0.96rem);
        }

        .struktur-foto {
            width: 100px;
            height: 130px;
            /* Bentuk vertikal elips */
            border-radius: 50px / 65px;
            /* Disesuaikan untuk elips */
            object-fit: cover;
            background: #f8fafc;
            border: 3px solid #f4b400;
            margin-bottom: 0.9rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .struktur-nama {
            font-weight: 600;
            color: #0f4c3a;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
            line-height: 1.3;
        }

        .struktur-jabatan {
            font-size: 0.82rem;
            color: #898781;
            margin-bottom: 0.9rem;
        }

        .struktur-aksi {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            /* Mendorong tombol selalu ke bawah jika nama panjang */
        }

        .struktur-aksi a {
            font-size: 0.78rem;
            text-decoration: none;
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
        }

        .btn-edit-struktur {
            background: #eaf3ee;
            color: #0f4c3a;
        }

        .btn-hapus-struktur {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty-struktur {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }

        /* --- MEDIA QUERIES (Responsive layout) --- */
        @media (max-width: 1100px) {

            /* max 4 baris untuk laptop kecil */
            .struktur-card {
                flex-basis: calc(25% - 1rem);
            }
        }

        @media (max-width: 860px) {

            /* max 3 baris untuk tablet */
            .struktur-card {
                flex-basis: calc(33.333% - 1rem);
            }
        }

        @media (max-width: 600px) {

            /* max 2 baris untuk hp besar */
            .struktur-card {
                flex-basis: calc(50% - 1rem);
            }
        }

        @media (max-width: 400px) {

            /* 1 baris untuk hp kecil */
            .struktur-card {
                flex-basis: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="brand">
            <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
            <h1>Dashboard Admin - Perangkat Desa Teluk Dalam</h1>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Buka menu" type="button">&#9776;</button>

        <div class="nav-right">
            <span class="halo">Halo, Admin</span>
        </div>

        <div class="nav-menu" id="navMenu">
            <a href="dashboard.php">Data Penduduk</a>
            <a href="struktur_desa.php" class="active">Perangkat Desa</a>
            <a href="../databases/logout.php">Keluar</a>
        </div>
    </nav>

    <div class="container">

        <?php if (isset($_GET['status'])): ?>
            <?php
            $pesan_status = [
                'sukses' => 'Data perangkat desa berhasil disimpan.',
                'hapus'  => 'Data perangkat desa berhasil dihapus.',
            ];
            ?>
            <?php if (isset($pesan_status[$_GET['status']])): ?>
                <div class="alert-status"><?= $pesan_status[$_GET['status']] ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <h2>Bagan Perangkat Desa</h2>
                <div class="table-actions">
                    <a href="struktur_detail.php" class="btn-tambah">+ Tambah Perangkat Desa</a>
                </div>
            </div>

            <?php if (empty($daftar_struktur)): ?>
                <div class="empty-struktur">Belum ada data perangkat desa.</div>
            <?php else: ?>
                <div class="struktur-org-container">

                    <!-- BARIS PERTAMA: KEPALA DESA -->
                    <?php if ($kepala_desa): ?>
                        <div class="kades-wrapper">
                            <div class="struktur-card kades-card">
                                <?php
                                $foto_src = (!empty($kepala_desa['foto']) && file_exists('../databases/photo/' . $kepala_desa['foto']))
                                    ? '../databases/photo/' . htmlspecialchars($kepala_desa['foto'])
                                    : $icon_default;
                                ?>
                                <img src="<?= $foto_src ?>" alt="Foto <?= htmlspecialchars($kepala_desa['nama_lengkap']) ?>" class="struktur-foto">
                                <div class="struktur-nama"><?= htmlspecialchars($kepala_desa['nama_lengkap']) ?></div>
                                <div class="struktur-jabatan"><?= htmlspecialchars($kepala_desa['jabatan']) ?></div>
                                <div class="struktur-aksi">
                                    <a href="struktur_detail.php?id=<?= (int) $kepala_desa['id'] ?>" class="btn-edit-struktur">Edit</a>
                                    <a href="delete_struktur.php?id=<?= (int) $kepala_desa['id'] ?>" class="btn-hapus-struktur"
                                        onclick="return confirm('Yakin hapus data ini? Foto yang tersimpan juga akan ikut dihapus.');">Hapus</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- BARIS KEDUA & SETERUSNYA: PERANGKAT LAIN -->
                    <?php if (!empty($perangkat_lain)): ?>
                        <div class="perangkat-lain-grid">
                            <?php foreach ($perangkat_lain as $s): ?>
                                <div class="struktur-card">
                                    <?php
                                    $foto_src = (!empty($s['foto']) && file_exists('../databases/photo/' . $s['foto']))
                                        ? '../databases/photo/' . htmlspecialchars($s['foto'])
                                        : $icon_default;
                                    ?>
                                    <img src="<?= $foto_src ?>" alt="Foto <?= htmlspecialchars($s['nama_lengkap']) ?>" class="struktur-foto">
                                    <div class="struktur-nama"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                    <div class="struktur-jabatan"><?= htmlspecialchars($s['jabatan']) ?></div>
                                    <div class="struktur-aksi">
                                        <a href="struktur_detail.php?id=<?= (int) $s['id'] ?>" class="btn-edit-struktur">Edit</a>
                                        <a href="delete_struktur.php?id=<?= (int) $s['id'] ?>" class="btn-hapus-struktur"
                                            onclick="return confirm('Yakin hapus data ini? Foto yang tersimpan juga akan ikut dihapus.');">Hapus</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
    <script src="../scriptss/leniss.js"></script>
    <script src="../scriptss/dashboard.js"></script>

</body>

</html>