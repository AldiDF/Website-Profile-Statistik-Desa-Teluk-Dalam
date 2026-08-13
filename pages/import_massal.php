<?php
require '../databases/auth_check.php';
require '../databases/connection.php';

if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Massal dari Excel - Desa Teluk Dalam</title>
    <link rel="icon" href="../assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../styless/import_data.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .peringatan-panjang,
        .peringatan-kosong {
            font-size: 0.85rem;
            margin-top: 0.6rem;
            line-height: 1.5;
        }

        .peringatan-panjang {
            color: #b91c1c;
        }

        .peringatan-kosong {
            color: #a16207;
        }

        .peringatan-tag-wrap {
            margin-top: 0.4rem;
        }

        .tag-merah,
        .tag-kuning {
            display: inline-block;
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            margin: 0.15rem 0.2rem 0 0;
            font-family: monospace;
        }

        .tag-merah {
            background: #fef2f2;
            color: #b91c1c;
        }

        .tag-kuning {
            background: #fef9c3;
            color: #a16207;
        }
    </style>
</head>

<body>

    <div class="topbar">
        <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo">
        <span>Import Massal Data Kependudukan</span>
    </div>

    <div class="page-wrap">
        <div class="card">
            <h1>Import Massal dari Excel</h1>
            <p class="desc">
                Upload 1 file Excel yang berisi banyak Kartu Keluarga (KK) sekaligus, format sama
                seperti data kependudukan standar (tiap KK diawali baris "No. KK : ..." diikuti
                anggotanya). Semua KK dan anggota di dalam file akan diproses otomatis:
                KK/anggota baru akan ditambahkan, yang sudah ada dan berubah akan diperbarui,
                yang tidak berubah akan dilewati.
            </p>

            <div class="drop-zone" id="dropZone">
                <div>📄 Klik di sini atau seret file <strong>.xlsx</strong> ke area ini</div>
                <div class="file-info" id="fileInfo"></div>
            </div>
            <input type="file" id="fileInput" accept=".xlsx,.xls">

            <div id="previewArea"></div>

            <button class="btn-proses" id="btnProses">Proses Import ke Database</button>
            <div class="status-msg" id="statusMsg"></div>
        </div>

        <div class="card" id="hasilCard" style="display:none;">
            <h1>Hasil Import</h1>
            <div class="ringkasan-grid" id="ringkasanGrid"></div>
            <ul class="gagal-list" id="gagalList"></ul>
        </div>

        <a href="dashboard.php" class="btn-kembali">&larr; Kembali ke Dashboard</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
    <script src="../scriptss/leniss.js"></script>
    <script src="../scriptss/import_data.js"></script>

</body>

</html>