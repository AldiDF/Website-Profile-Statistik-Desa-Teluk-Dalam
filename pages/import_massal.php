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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', 'Segoe UI', Arial, sans-serif; }
        body { background: var(--bg); color: #2b2b28; }
        .topbar {
            background: var(--hijau-tua);
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .topbar img { width: 32px; height: 36px; border-radius: 50%; }
        .page-wrap { max-width: 780px; margin: 2rem auto; padding: 0 1rem; }
        .card {
            background: #fff;
            border-radius: 14px;
            padding: 1.8rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
        }
        h1 { font-size: 1.3rem; color: var(--hijau-gelap); margin-bottom: 0.4rem; }
        p.desc { color: var(--abu-teks); font-size: 0.9rem; margin-bottom: 1.2rem; }
        .drop-zone {
            border: 2px dashed var(--border-soft);
            border-radius: 12px;
            padding: 2.2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: var(--hijau-tua);
            background: #f6faf8;
        }
        .drop-zone strong { color: var(--hijau-tua); }
        #fileInput { display: none; }
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
        .btn-proses:hover { background: var(--hijau-gelap); }
        .btn-proses:disabled { opacity: 0.6; cursor: not-allowed; }
        a.btn-kembali {
            display: inline-block;
            margin-top: 1rem;
            color: var(--hijau-tua);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.88rem;
        }
        .preview-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; margin-top: 1rem; }
        .preview-table th, .preview-table td { border: 1px solid var(--border-soft); padding: 0.4rem 0.6rem; text-align: left; }
        .preview-table th { background: #f6f5f1; }
        .ringkasan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: 0.8rem; margin-top: 1rem; }
        .ringkasan-item { background: #f6f5f1; border-radius: 10px; padding: 0.9rem; text-align: center; }
        .ringkasan-item .angka { font-size: 1.5rem; font-weight: 700; color: var(--hijau-tua); }
        .ringkasan-item .label { font-size: 0.78rem; color: var(--abu-teks); margin-top: 0.2rem; }
        .gagal-list { margin-top: 1rem; font-size: 0.85rem; color: #b91c1c; }
        .gagal-list li { margin-bottom: 0.3rem; }
        .status-msg { font-size: 0.88rem; margin-top: 0.8rem; }
        .status-msg.error { color: #b91c1c; }
        .status-msg.ok { color: var(--hijau-tua); }
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
        const MAP_PENDIDIKAN = {
            'TIDAK SEKOLAH': 'TIDAK SEKOLAH',
            'SD/SEDERAJAT': 'SD/SEDERAJAT',
            'SLTP/SEDERAJAT': 'SLTP/SEDERAJAT',
            'SLTA/SEDERAJAT': 'SLTA/SEDERAJAT',
            'DIPLOMA I': 'DIPLOMA I',
            'DIPLOMA II': 'DIPLOMA II',
            'DIPLOMA III': 'DIPLOMA III',
            'DIPLOMA IV/STRATA I': 'DIPLOMA IV/STRATA I',
            'D-IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'S1/SEDERAJAT': 'DIPLOMA IV/STRATA I',
            'S2/SEDERAJAT': 'STRATA II',
            'S3/SEDERAJAT': 'STRATA III',
        };

        function normalisasiPendidikan(v) {
            if (!v) return '';
            const key = v.toString().trim().toUpperCase();
            return MAP_PENDIDIKAN[key] || key;
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
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
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

            const reader = new FileReader();
            reader.onload = function (evt) {
                try {
                    const data = new Uint8Array(evt.target.result);
                    const workbook = XLSX.read(data, { type: 'array', cellDates: true });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                    dataKeluargaSiapKirim = parseSemuaKK(rows);
                    tampilkanPreview(dataKeluargaSiapKirim);
                } catch (err) {
                    statusMsg.textContent = 'Gagal membaca file: ' + err.message;
                    statusMsg.className = 'status-msg error';
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function parseSemuaKK(rows) {
            // Deteksi status penduduk per BLOK header "PERIODE/TANGGAL ... RT : 1 ( PENDUDUK PERMANEN/NON PERMANEN)".
            // Satu file bisa berisi lebih dari satu blok header (mis. PERMANEN di atas, NON PERMANEN di bawah),
            // jadi status yang dipakai harus mengikuti header TERDEKAT SEBELUM tiap KK, bukan header terakhir
            // yang ditemukan di seluruh file (kalau tidak, KK di blok pertama akan salah ikut status blok terakhir).
            const statusHeaderList = []; // { rowIndex, status }
            for (let i = 0; i < rows.length; i++) {
                const cellA = (rows[i][0] || '').toString().trim().toUpperCase();
                if (cellA.startsWith('PERIODE')) {
                    const cellRT = (rows[i][12] || '').toString();
                    let status = 'PERMANEN';
                    if (/NON PERMANEN/i.test(cellRT)) status = 'NON PERMANEN';
                    else if (/PERMANEN/i.test(cellRT)) status = 'PERMANEN';
                    statusHeaderList.push({ rowIndex: i, status: status });
                }
            }

            function statusUntukBaris(rowIndex) {
                let status = 'PERMANEN'; // default kalau belum ada header PERIODE sebelum baris ini
                for (const h of statusHeaderList) {
                    if (h.rowIndex <= rowIndex) status = h.status;
                    else break;
                }
                return status;
            }

            // Cari indeks tiap baris "No. KK : ..."
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

                let nomorKK = '', rt = '', alamat = '';
                const mKK = cellA.match(/(\d{16})/);
                if (mKK) nomorKK = mKK[1];
                const mAlamat = cellC.match(/ALAMAT\s*:\s*(.*?),\s*NAMA DUSUN/i);
                if (mAlamat) alamat = mAlamat[1].trim();
                const mRT = cellC.match(/RT\/RW\s*:\s*(\d+)/i);
                if (mRT) rt = mRT[1].padStart(3, '0');

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
                        jenis_kelamin: (row[5] || '').toString().trim().toUpperCase(),
                        hubungan_keluarga: (row[6] || '').toString().trim().toUpperCase(),
                        agama: (row[7] || '').toString().trim().toUpperCase(),
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

        function tampilkanPreview(daftarKeluarga) {
            if (daftarKeluarga.length === 0) {
                previewArea.innerHTML = '';
                statusMsg.textContent = 'Tidak ada blok KK yang terbaca dari file. Pastikan formatnya sesuai.';
                statusMsg.className = 'status-msg error';
                btnProses.style.display = 'none';
                return;
            }

            let totalAnggota = 0;
            let html = '<table class="preview-table"><thead><tr><th>No. KK</th><th>RT</th><th>Alamat</th><th>Jumlah Anggota</th></tr></thead><tbody>';
            daftarKeluarga.forEach(k => {
                totalAnggota += k.anggota.length;
                html += `<tr><td>${k.nomor_kk}</td><td>${k.rt || '-'}</td><td>${k.alamat_domisili || '-'}</td><td>${k.anggota.length}</td></tr>`;
            });
            html += '</tbody></table>';
            previewArea.innerHTML = html;

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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ keluarga: dataKeluargaSiapKirim }),
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