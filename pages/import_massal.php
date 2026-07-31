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

        .topbar {
            background: var(--hijau-tua);
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .topbar img {
            width: 32px;
            height: 36px;
            border-radius: 50%;
        }

        .page-wrap {
            max-width: 780px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 1.8rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.3rem;
            color: var(--hijau-gelap);
            margin-bottom: 0.4rem;
        }

        p.desc {
            color: var(--abu-teks);
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
        }

        .drop-zone {
            border: 2px dashed var(--border-soft);
            border-radius: 12px;
            padding: 2.2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--hijau-tua);
            background: #f6faf8;
        }

        .drop-zone strong {
            color: var(--hijau-tua);
        }

        #fileInput {
            display: none;
        }

        .file-info {
            margin-top: 1rem;
            font-size: 0.88rem;
            color: var(--hijau-gelap);
            display: none;
        }

        .btn-proses {
            margin-top: 1.2rem;
            background: var(--hijau-tua);
            color: #fff;
            border: none;
            padding: 0.75rem 1.4rem;
            border-radius: 8px;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            display: none;
        }

        .btn-proses:hover {
            background: var(--hijau-gelap);
        }

        .btn-proses:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        a.btn-kembali {
            display: inline-block;
            margin-top: 1rem;
            color: var(--hijau-tua);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.88rem;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-top: 1rem;
        }

        .preview-table th,
        .preview-table td {
            border: 1px solid var(--border-soft);
            padding: 0.4rem 0.6rem;
            text-align: left;
        }

        .preview-table th {
            background: #f6f5f1;
        }

        .ringkasan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .ringkasan-item {
            background: #f6f5f1;
            border-radius: 10px;
            padding: 0.9rem;
            text-align: center;
        }

        .ringkasan-item .angka {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--hijau-tua);
        }

        .ringkasan-item .label {
            font-size: 0.78rem;
            color: var(--abu-teks);
            margin-top: 0.2rem;
        }

        .gagal-list {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #b91c1c;
        }

        .gagal-list li {
            margin-bottom: 0.3rem;
        }

        .status-msg {
            font-size: 0.88rem;
            margin-top: 0.8rem;
        }

        .status-msg.error {
            color: #b91c1c;
        }

        .status-msg.ok {
            color: var(--hijau-tua);
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
    <script>
        // ==========================
        // FUZZY MATCHING (jarak Levenshtein)
        // Lapisan cadangan untuk menangkap typo yang BELUM terdaftar di dictionary manual,
        // mis. "Katholik" (tidak ada di daftar) tetap bisa ke-koreksi ke "KATOLIK" karena
        // jaraknya cukup dekat. Dijalankan HANYA kalau dictionary tidak menemukan kecocokan
        // persis, supaya hasil yang sudah pasti benar tidak ikut "ditebak-tebak" ulang.
        // ==========================
        function levenshtein(a, b) {
            const m = a.length,
                n = b.length;
            if (m === 0) return n;
            if (n === 0) return m;
            const dp = Array.from({
                length: m + 1
            }, () => new Array(n + 1).fill(0));
            for (let i = 0; i <= m; i++) dp[i][0] = i;
            for (let j = 0; j <= n; j++) dp[0][j] = j;
            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                    dp[i][j] = Math.min(
                        dp[i - 1][j] + 1, // hapus 1 huruf
                        dp[i][j - 1] + 1, // tambah 1 huruf
                        dp[i - 1][j - 1] + cost // ganti 1 huruf
                    );
                }
            }
            return dp[m][n];
        }

        function cariTerdekat(teks, daftarKandidat, ambangRasio = 0.3) {
            let terbaik = null;
            let jarakTerbaik = Infinity;
            for (const kandidat of daftarKandidat) {
                const jarak = levenshtein(teks, kandidat);
                const ambang = Math.max(1, Math.floor(Math.max(teks.length, kandidat.length) * ambangRasio));
                if (jarak <= ambang && jarak < jarakTerbaik) {
                    jarakTerbaik = jarak;
                    terbaik = kandidat;
                }
            }
            return terbaik;
        }
        const MAP_PENDIDIKAN = {
            'TIDAK SEKOLAH': 'TIDAK SEKOLAH',
            'BELUM SEKOLAH': 'TIDAK SEKOLAH',
            'BELUM/TIDAK SEKOLAH': 'TIDAK SEKOLAH',
            'TIDAK/BELUM SEKOLAH': 'TIDAK SEKOLAH',
            'TDKBELUM SEKOLAH': 'TIDAK SEKOLAH',
            'TDK/BELUM SEKOLAH': 'TIDAK SEKOLAH',
            'TIDAK/BELUM SEKOLAH': 'TIDAK SEKOLAH',
            '': 'TIDAK SEKOLAH',


            'SD/SEDERAJAT': 'SD/SEDERAJAT',
            'SD': 'SD/SEDERAJAT',
            'SEDERAJAT SD': 'SD/SEDERAJAT',
            'TAMAT SD/SEDERAJAT': 'SD/SEDERAJAT',

            'SLTP/SEDERAJAT': 'SLTP/SEDERAJAT',
            'SMP/SEDERAJAT': 'SLTP/SEDERAJAT',
            'SLTP': 'SLTP/SEDERAJAT',
            'SMP': 'SLTP/SEDERAJAT',
            'SLTA/SEDERAJAT': 'SLTA/SEDERAJAT',
            'SMA/SEDERAJAT': 'SLTA/SEDERAJAT',
            'SLTA': 'SLTA/SEDERAJAT',
            'SMA': 'SLTA/SEDERAJAT',
            'SMK/SEDERAJAT': 'SLTA/SEDERAJAT',
            'SMK': 'SLTA/SEDERAJAT',
            'DIPLOMA I/II/III': 'DIPLOMA I/II/III',
            'DIPLOMA/SEDERAJAT': 'DIPLOMA I/II/III',
            'DIPLOMA': 'DIPLOMA I/II/III',
            'DIPLOMA I': 'DIPLOMA I/II/III',
            'DIPLOMA II': 'DIPLOMA I/II/III',
            'DIPLOMA III': 'DIPLOMA I/II/III',
            'DIPLOMA I/SEDERAJAT': 'DIPLOMA I/II/III',
            'DIPLOMA II/SEDERAJAT': 'DIPLOMA I/II/III',
            'DIPLOMA III/SEDERAJAT': 'DIPLOMA I/II/III',
            'D1/SEDERAJAT': 'DIPLOMA I/II/III',
            'D2/SEDERAJAT': 'DIPLOMA I/II/III',
            'D3/SEDERAJAT': 'DIPLOMA I/II/III',
            'D1': 'DIPLOMA I/II/III',
            'D2': 'DIPLOMA I/II/III',
            'D3': 'DIPLOMA I/II/III',
            'AKADEMI/D3': 'DIPLOMA I/II/III',
            'DIPLOMA IV/STRATA I': 'DIPLOMA IV/STRATA I',
            'DIPLOMA IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'D-IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'D4/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'D4': 'DIPLOMA IV/STRATA I',
            'S1/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'S1': 'DIPLOMA IV/STRATA I',
            'STRATA I': 'DIPLOMA IV/STRATA I',
            'STRATA I/SEDERAJAT': 'DIPLOMA IV/STRATA I',

            'S2/SEDERAJAT': 'STRATA II',
            'S2': 'STRATA II',
            'STRATA II': 'STRATA II',
            'STRATA II/SEDERAJAT': 'STRATA II',
            'MAGISTER': 'STRATA II',

            'S3/SEDERAJAT': 'STRATA III',
            'S3': 'STRATA III',
            'STRATA III': 'STRATA III',
            'STRATA III/SEDERAJAT': 'STRATA III',
            'DOKTOR': 'STRATA III',
        };
        const DAFTAR_KEY_PENDIDIKAN = Object.keys(MAP_PENDIDIKAN);

        function normalisasiPendidikan(v) {
            if (!v) return '';
            // Rapikan spasi ganda/tidak rapi sebelum dicocokkan, mis. "S1 / SEDERAJAT" -> "S1/SEDERAJAT"
            const key = v.toString().trim().toUpperCase().replace(/\s*\/\s*/g, '/').replace(/\s+/g, ' ');
            if (MAP_PENDIDIKAN[key]) return MAP_PENDIDIKAN[key];
            // Tidak ketemu persis -> coba cari istilah yang mirip (typo), mis. "DIPOLMA/SEDERAJAT"
            const cocokFuzzy = cariTerdekat(key, DAFTAR_KEY_PENDIDIKAN);
            if (cocokFuzzy) return MAP_PENDIDIKAN[cocokFuzzy];
            return key; // tidak ada yang cukup mirip -> biarkan apa adanya, perlu dicek manual
        }

        const JENIS_KELAMIN_FUZZY = ['LAKI-LAKI', 'LAKI LAKI', 'PEREMPUAN', 'WANITA', 'PRIA'];

        function normalisasiJenisKelamin(v) {
            if (!v) return '';
            const key = v.toString().trim().toUpperCase().replace(/\s+/g, ' ');
            if (key === 'L' || key === 'LK' || key === 'LAKI2' || /^LAKI[\s-]*LAKI$/.test(key)) {
                return 'LAKI-LAKI'; // menambahkan strip kalau sebelumnya tertulis "LAKI LAKI"/"LAKI2"/dll
            }
            if (key === 'P' || key === 'PR' || key === 'WANITA' || key === 'PEREMPUAN') {
                return 'PEREMPUAN';
            }
            if (key.length > 2) {
                const cocokFuzzy = cariTerdekat(key, JENIS_KELAMIN_FUZZY);
                if (cocokFuzzy === 'LAKI-LAKI' || cocokFuzzy === 'LAKI LAKI' || cocokFuzzy === 'PRIA') return 'LAKI-LAKI';
                if (cocokFuzzy === 'PEREMPUAN' || cocokFuzzy === 'WANITA') return 'PEREMPUAN';
            }
            return key;
        }
        const MAP_AGAMA = {
            'ISLAM': 'ISLAM',
            'MUSLIM': 'ISLAM',

            'KRISTEN': 'KRISTEN',
            'KRISTEN PROTESTAN': 'KRISTEN',
            'PROTESTAN': 'KRISTEN',

            'KATOLIK': 'KATOLIK',
            'KATHOLIK': 'KATOLIK',
            'KATOLIK ROMA': 'KATOLIK',

            'HINDU': 'HINDU',
            'HINDHU': 'HINDU',

            'BUDDHA': 'BUDDHA',
            'BUDHA': 'BUDDHA',
            'BUDHHA': 'BUDDHA',

            'KONGHUCU': 'KONGHUCU',
            'KHONGHUCU': 'KONGHUCU',
            'CONGHUCU': 'KONGHUCU',
        };
        const DAFTAR_KEY_AGAMA = Object.keys(MAP_AGAMA);

        function normalisasiAgama(v) {
            if (!v) return '';
            const key = v.toString().trim().toUpperCase().replace(/\s+/g, ' ');
            if (MAP_AGAMA[key]) return MAP_AGAMA[key];
            const cocokFuzzy = cariTerdekat(key, DAFTAR_KEY_AGAMA);
            if (cocokFuzzy) return MAP_AGAMA[cocokFuzzy];
            return key;
        }
        const HUBUNGAN_DIKENAL = [
            'KEPALA KELUARGA', 'SUAMI', 'ISTRI', 'ANAK', 'CUCU',
            'ORANG TUA', 'MERTUA', 'MENANTU', 'SAUDARA', 'FAMILI LAIN',
        ];

        const MAP_HUBUNGAN = {
            'ORANGTUA': 'ORANG TUA',
            'ORANG TUA/MERTUA': 'ORANG TUA',
        };

        function normalisasiHubungan(v) {
            if (!v) return '';
            const key = v.toString().trim().toUpperCase().replace(/\s+/g, ' ');
            if (MAP_HUBUNGAN[key]) return MAP_HUBUNGAN[key];
            if (HUBUNGAN_DIKENAL.includes(key)) return key;
            const cocokFuzzy = cariTerdekat(key, HUBUNGAN_DIKENAL, 0.2);
            if (cocokFuzzy) return cocokFuzzy;
            return 'FAMILI LAIN';
        }

        // Ekstraksi alamat & RT dari teks bebas, menangani BEBERAPA format sekaligus:
        // Format 1: "ALAMAT : xxx, NAMA DUSUN : -, RT/RW : 001/- NO RUMAH ..."
        // Format 2: "ALAMAT : xxx RT. 01"  (tanpa label "NAMA DUSUN"/"RT/RW")
        function ekstrakAlamat(cellC) {
            const teks = cellC.toString();

            // Ambil semua teks setelah "ALAMAT :" sebagai bahan mentah
            const mMentah = teks.match(/ALAMAT\s*:\s*(.*)/i);
            const sisaTeks = mMentah ? mMentah[1] : '';

            // Potong di penanda pertama yang ditemukan, supaya alamat tetap bersih
            let alamat = sisaTeks
                .split(/,\s*NAMA DUSUN/i)[0] // buang ", Nama Dusun : ..." kalau ada
                .split(/,?\s*RT\/RW/i)[0] // buang ", RT/RW : ..." kalau ada
                .replace(/,?\s*RT\.?\s*\d{1,3}\s*$/i, '') // buang "RT. 01" kalau nempel di akhir kalimat
                .trim();

            return alamat;
        }

        function ekstrakRTdariNamaFile(namaFile) {
            // Buang ekstensi (.xlsx/.xls) dulu supaya tidak ikut ke-scan
            const namaBersih = namaFile.replace(/\.(xlsx|xls)$/i, '');

            // Cocok untuk: "RT1", "RT 1", "RT 01", "RT 001", "rt1", "rt 1", "RT.1", dst
            const m = namaBersih.match(/RT\s*\.?\s*(\d{1,3})/i);
            if (m) {
                return m[1].padStart(3, '0'); // hasil selalu 3 digit: "001", "010", "100"
            }
            return '';
        }

        function excelDateToISO(v) {
            if (!v) return '';
            if (v instanceof Date && !isNaN(v)) {
                const y = v.getFullYear();
                const m = String(v.getMonth() + 1).padStart(2, '0');
                const d = String(v.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
            const parts = v.toString().trim().split(/[\/\-]/);
            if (parts.length === 3) {
                let [a, b, c] = parts;
                if (c.length === 4) return `${c}-${b.padStart(2, '0')}-${a.padStart(2, '0')}`;
                if (a.length === 4) return `${a}-${b.padStart(2, '0')}-${c.padStart(2, '0')}`;
            }
            return '';
        }
        let dataKeluargaSiapKirim = [];
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const btnProses = document.getElementById('btnProses');
        const statusMsg = document.getElementById('statusMsg');
        const previewArea = document.getElementById('previewArea');
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) handleFile(e.target.files[0]);
        });



        function handleFile(file) {
            statusMsg.textContent = '';
            statusMsg.className = 'status-msg';
            fileInfo.style.display = 'block';
            fileInfo.textContent = `File dipilih: ${file.name}`;

            const rtDariFile = ekstrakRTdariNamaFile(file.name);
            if (rtDariFile === '') {
                statusMsg.textContent = 'Nama file tidak mengandung info RT (contoh format yang benar: "RT1.xlsx", "RT 01.xlsx"). Import dibatalkan.';
                statusMsg.className = 'status-msg error';
                previewArea.innerHTML = '';
                return;
            }


            const reader = new FileReader();
            reader.onload = function(evt) {
                try {
                    const data = new Uint8Array(evt.target.result);
                    const workbook = XLSX.read(data, {
                        type: 'array',
                        cellDates: true
                    });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet, {
                        header: 1,
                        raw: false,
                        defval: ''
                    });
                    dataKeluargaSiapKirim = parseSemuaKK(rows, rtDariFile);
                    tampilkanPreview(dataKeluargaSiapKirim);
                } catch (err) {
                    statusMsg.textContent = 'Gagal membaca file: ' + err.message;
                    statusMsg.className = 'status-msg error';
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function parseSemuaKK(rows, rtDariFile) {
            const statusHeaderList = []; // { rowIndex, status }
            for (let i = 0; i < rows.length; i++) {
                const cellA = (rows[i][0] || '').toString().trim().toUpperCase();
                if (cellA.startsWith('PERIODE')) {
                    const cellRT = (rows[i][12] || '').toString();
                    let status = 'PERMANEN';
                    if (/NON PERMANEN/i.test(cellRT)) status = 'NON PERMANEN';
                    else if (/PERMANEN/i.test(cellRT)) status = 'PERMANEN';
                    statusHeaderList.push({
                        rowIndex: i,
                        status: status
                    });
                }
            }

            function statusUntukBaris(rowIndex) {
                let status = 'PERMANEN';
                for (const h of statusHeaderList) {
                    if (h.rowIndex <= rowIndex) status = h.status;
                    else break;
                }
                return status;
            }
            const indexKK = [];
            for (let i = 0; i < rows.length; i++) {
                const cellA = (rows[i][0] || '').toString().trim().toUpperCase();
                if (cellA.startsWith('NO. KK')) indexKK.push(i);
            }
            const hasil = [];
            indexKK.forEach((startIdx, k) => {
                const statusPendudukHeader = statusUntukBaris(startIdx);
                const rowKK = rows[startIdx];
                const cellA = (rowKK[0] || '').toString();
                const cellC = (rowKK[2] || '').toString();

                let nomorKK = '';
                const mKK = cellA.match(/(\d{16})/);
                if (mKK) nomorKK = mKK[1];

                const alamat = ekstrakAlamat(cellC);
                const rt = rtDariFile;
                const endIdx = (k + 1 < indexKK.length) ? indexKK[k + 1] : rows.length;
                const anggota = [];
                for (let i = startIdx + 1; i < endIdx; i++) {
                    const row = rows[i];
                    const nama = (row[1] || '').toString().trim();
                    const nik = (row[2] || '').toString().trim();
                    if (!nama && !nik) continue;

                    anggota.push({
                        nik: nik,
                        nama_lengkap: nama,
                        tempat_lahir: (row[3] || '').toString().trim(),
                        tanggal_lahir: excelDateToISO(row[4]),
                        jenis_kelamin: normalisasiJenisKelamin(row[5]),
                        hubungan_keluarga: normalisasiHubungan(row[6]),
                        agama: normalisasiAgama(row[7]),
                        pendidikan_terakhir: normalisasiPendidikan(row[8]),
                        pekerjaan: (row[9] || '').toString().trim(),
                        kewarganegaraan: 'WNI',
                        status_penduduk: statusPendudukHeader,
                    });
                }

                if (nomorKK && anggota.length > 0) {
                    hasil.push({
                        nomor_kk: nomorKK,
                        rt: rt,
                        alamat_domisili: alamat,
                        anggota: anggota,
                    });
                }
            });

            return hasil;
        }
        const NILAI_VALID_ENUM = {
            jenis_kelamin: ['LAKI-LAKI', 'PEREMPUAN'],
            agama: ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'],
            pendidikan_terakhir: [
                'TIDAK SEKOLAH', 'SD/SEDERAJAT', 'SLTP/SEDERAJAT', 'SLTA/SEDERAJAT',
                'DIPLOMA I/II/III', 'DIPLOMA IV/STRATA I', 'STRATA II', 'STRATA III', 'PAUD/TK'
            ],
            kewarganegaraan: ['WNI', 'WNA'],
            status_penduduk: ['PERMANEN', 'NON PERMANEN', 'MENINGGAL'],
        };

        function nilaiEnumValid(kolom, nilai) {
            if (!NILAI_VALID_ENUM[kolom]) return true;
            if (!nilai) return true;
            return NILAI_VALID_ENUM[kolom].includes(nilai);
        }

        function tampilkanPreview(daftarKeluarga) {
            if (daftarKeluarga.length === 0) {
                previewArea.innerHTML = '';
                statusMsg.textContent = 'Tidak ada blok KK yang terbaca dari file. Pastikan formatnya sesuai.';
                statusMsg.className = 'status-msg error';
                btnProses.style.display = 'none';
                return;
            }

            let totalAnggota = 0;
            let adaNilaiMencurigakan = false;
            let html = '<table class="preview-table"><thead><tr><th>No. KK</th><th>RT</th><th>Alamat</th><th>Jumlah Anggota</th></tr></thead><tbody>';
            daftarKeluarga.forEach(k => {
                totalAnggota += k.anggota.length;
                html += `<tr><td>${k.nomor_kk}</td><td>${k.rt || '-'}</td><td>${k.alamat_domisili || '-'}</td><td>${k.anggota.length}</td></tr>`;
            });
            html += '</tbody></table>';
            let detailHtml = '<table class="preview-table anggota-table"><thead><tr>' +
                '<th>No. KK</th><th>NIK</th><th>Nama</th><th>Tempat Lahir</th><th>Tgl Lahir</th>' +
                '<th>JK</th><th>Hub. Keluarga</th><th>Agama</th><th>Pendidikan</th><th>Pekerjaan</th>' +
                '<th>Kewarganegaraan</th><th>Status</th></tr></thead><tbody>';

            function selEnum(kolom, nilai) {
                const valid = nilaiEnumValid(kolom, nilai);
                if (!valid) adaNilaiMencurigakan = true;
                const style = valid ? '' : ' style="color:#b91c1c; font-weight:600; background:#fef2f2;"';
                return `<td${style}>${nilai || '-'}</td>`;
            }

            daftarKeluarga.forEach(k => {
                k.anggota.forEach(a => {
                    detailHtml += '<tr>';
                    detailHtml += `<td>${k.nomor_kk}</td>`;
                    detailHtml += `<td>${a.nik || '<em>(kosong)</em>'}</td>`;
                    detailHtml += `<td>${a.nama_lengkap}</td>`;
                    detailHtml += `<td>${a.tempat_lahir || ' '}</td>`;
                    detailHtml += `<td>${a.tanggal_lahir || ' '}</td>`;
                    detailHtml += selEnum('jenis_kelamin', a.jenis_kelamin);
                    detailHtml += `<td>${a.hubungan_keluarga || ' '}</td>`;
                    detailHtml += selEnum('agama', a.agama);
                    detailHtml += selEnum('pendidikan_terakhir', a.pendidikan_terakhir);
                    detailHtml += `<td>${a.pekerjaan || ' '}</td>`;
                    detailHtml += selEnum('kewarganegaraan', a.kewarganegaraan);
                    detailHtml += selEnum('status_penduduk', a.status_penduduk);
                    detailHtml += '</tr>';
                });
            });
            detailHtml += '</tbody></table>';

            const peringatan = adaNilaiMencurigakan ?
                '<p style="color:#b91c1c; font-size:0.85rem; margin-top:0.6rem;">⚠️ Ada nilai (ditandai merah) yang tidak cocok dengan pilihan resmi di database. Ini kemungkinan besar akan menyebabkan error "Data truncated" saat proses import. Cek dan perbaiki dulu di file Excel sumbernya.</p>' :
                '';

            previewArea.innerHTML = html +
                `<details style="margin-top:1rem;"><summary style="cursor:pointer; color:var(--hijau-tua); font-weight:600; font-size:0.88rem;">Lihat detail per anggota (${totalAnggota} orang) — cek hasil parsing sebelum import</summary>${peringatan}${detailHtml}</details>`;

            statusMsg.textContent = `Terbaca ${daftarKeluarga.length} KK, total ${totalAnggota} anggota.`;
            statusMsg.className = 'status-msg';
            btnProses.style.display = 'inline-block';
        }

        btnProses.addEventListener('click', async () => {
            if (dataKeluargaSiapKirim.length === 0) return;
            btnProses.disabled = true;
            btnProses.textContent = 'Memproses...';
            statusMsg.textContent = '';

            try {
                const res = await fetch('import_proses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        keluarga: dataKeluargaSiapKirim
                    }),
                });
                const contentType = res.headers.get('content-type') || '';
                const mentahText = await res.text();
                if (!contentType.includes('application/json')) {
                    let petunjuk = 'Server tidak mengembalikan JSON (kemungkinan sesi login habis, file import_proses.php belum ada di folder yang sama, atau ada error PHP).';
                    if (mentahText.trim().toLowerCase().startsWith('<!doctype') || mentahText.trim().toLowerCase().startsWith('<html')) {
                        petunjuk += ' Server mengembalikan halaman HTML (kemungkinan redirect ke login atau halaman error 404/500).';
                    }
                    statusMsg.textContent = `Status HTTP ${res.status}. ${petunjuk} Cuplikan respons: ` + mentahText.slice(0, 200);
                    statusMsg.className = 'status-msg error';
                    return;
                }

                const hasil = JSON.parse(mentahText);

                if (!hasil.success) {
                    statusMsg.textContent = hasil.message || 'Import gagal.';
                    statusMsg.className = 'status-msg error';
                    return;
                }

                tampilkanHasil(hasil.ringkasan);
                btnProses.style.display = 'none';
                previewArea.innerHTML = '';
                statusMsg.textContent = 'Import selesai.';
                statusMsg.className = 'status-msg ok';
            } catch (err) {
                statusMsg.textContent = 'Terjadi kesalahan saat mengirim data: ' + err.message;
                statusMsg.className = 'status-msg error';
            } finally {
                btnProses.disabled = false;
                btnProses.textContent = 'Proses Import ke Database';
            }
        });

        function tampilkanHasil(r) {
            const hasilCard = document.getElementById('hasilCard');
            const grid = document.getElementById('ringkasanGrid');
            const gagalList = document.getElementById('gagalList');

            grid.innerHTML = `
                <div class="ringkasan-item"><div class="angka">${r.keluarga_baru}</div><div class="label">KK Baru</div></div>
                <div class="ringkasan-item"><div class="angka">${r.keluarga_ada}</div><div class="label">KK Sudah Ada</div></div>
                <div class="ringkasan-item"><div class="angka">${r.anggota_baru}</div><div class="label">Anggota Baru</div></div>
                <div class="ringkasan-item"><div class="angka">${r.anggota_diperbarui}</div><div class="label">Anggota Diperbarui</div></div>
                <div class="ringkasan-item"><div class="angka">${r.anggota_dilewati}</div><div class="label">Anggota Dilewati (Tidak Berubah)</div></div>
                <div class="ringkasan-item"><div class="angka">${r.gagal.length}</div><div class="label">Gagal</div></div>
            `;

            gagalList.innerHTML = '';
            r.gagal.forEach(pesan => {
                const li = document.createElement('li');
                li.textContent = pesan;
                gagalList.appendChild(li);
            });

            hasilCard.style.display = 'block';
        }
    </script>

</body>

</html>