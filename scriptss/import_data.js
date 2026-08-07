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
                dp[i - 1][j] + 1,
                dp[i][j - 1] + 1,
                dp[i - 1][j - 1] + cost
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
    'BELUM TAMATSD/SEDERAJAT': 'TIDAK SEKOLAH',
    'BELUM/TIDAK TURUN': 'TIDAK SEKOLAH',
    'TK': 'PAUD/TK',
    'PAUD': 'PAUD/TK',
    'PAUD/TK': 'PAUD/TK',
    'PAUD/TK SEDERAJAT': 'PAUD/TK',
    'PELAJAR TK/SEDERAJAT': 'PAUD/TK',
    'SD/SEDERAJAT': 'SD/SEDERAJAT',
    'SD': 'SD/SEDERAJAT',
    'Tk/SD': 'SD/SEDERAJAT',
    'SEDERAJAT SD': 'SD/SEDERAJAT',
    'TAMAT SD/SEDERAJAT': 'SD/SEDERAJAT',
    'PELAJAR/SD': 'SD/SEDERAJAT',
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
    'AKADEMI/DIPLOMA III/SARJANA MUDA': 'DIPLOMA I/II/III',
    'DIPLOMA IV/STRATA I': 'DIPLOMA IV/STRATA I',
    'DIPLOMA IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
    'D-IV/SEDERAJAT': 'DIPLOMA IV/STRATA I',
    'D4/SEDERAJAT': 'DIPLOMA IV/STRATA I',
    'D4': 'DIPLOMA IV/STRATA I',
    'S1/SEDERAJAT': 'DIPLOMA IV/STRATA I',
    'S1': 'DIPLOMA IV/STRATA I',
    'STRATA I': 'DIPLOMA IV/STRATA I',
    'STRATA I/SEDERAJAT': 'DIPLOMA IV/STRATA I',
    'SARJANA (S1)': 'DIPLOMA IV/STRATA I',
    'S2/SEDERAJAT': 'STRATA II',
    'S2': 'STRATA II',
    'STRATA II': 'STRATA II',
    'STRATA II/SEDERAJAT': 'STRATA II',
    'MAGISTER': 'STRATA II',
    'MAGISTER (S2)': 'STRATA II',
    'S3/SEDERAJAT': 'STRATA III',
    'S3': 'STRATA III',
    'STRATA III': 'STRATA III',
    'STRATA III/SEDERAJAT': 'STRATA III',
    'DOKTOR': 'STRATA III',
    'DOKTOR (S3)': 'STRATA III',
};
const DAFTAR_KEY_PENDIDIKAN = Object.keys(MAP_PENDIDIKAN);

function normalisasiPendidikan(v) {
    if (!v) return '';
    const key = v.toString().trim().toUpperCase().replace(/\s*\/\s*/g, '/').replace(/\s+/g, ' ');
    if (MAP_PENDIDIKAN[key]) return MAP_PENDIDIKAN[key];
    const cocokFuzzy = cariTerdekat(key, DAFTAR_KEY_PENDIDIKAN);
    if (cocokFuzzy) return MAP_PENDIDIKAN[cocokFuzzy];
    return key;
}

const MAP_PEKERJAAN = {
    'BELUM/TIDAK BEKERJA': 'BELUM/TIDAK BEKERJA',
    'TIDAK/BELUM BEKERJA': 'BELUM/TIDAK BEKERJA',
    'TIDAK BEKERJA': 'BELUM/TIDAK BEKERJA',
    'BELUM BEKERJA': 'BELUM/TIDAK BEKERJA',
    'TDK/BELUM BEKERJA': 'BELUM/TIDAK BEKERJA',
    'BLM BEKERJA': 'BELUM/TIDAK BEKERJA',
    'TIDAK/BLM BEKERJA': 'BELUM/TIDAK BEKERJA',
    '': 'BELUM/TIDAK BEKERJA',

    'MENGURUS RUMAH TANGGA': 'MENGURUS RUMAH TANGGA',
    'PENGURUS RUMAH TANGGA': 'MENGURUS RUMAH TANGGA',
    'IBU RUMAH TANGGA': 'MENGURUS RUMAH TANGGA',
    'IRT': 'MENGURUS RUMAH TANGGA',
    'RUMAH TANGGA': 'MENGURUS RUMAH TANGGA',

    'PELAJAR/MAHASISWA': 'PELAJAR/MAHASISWA',
    'PELAJAR': 'PELAJAR/MAHASISWA',
    'MAHASISWA': 'PELAJAR/MAHASISWA',
    'PELAJAR MAHASISWA': 'PELAJAR/MAHASISWA',
    'SISWA': 'PELAJAR/MAHASISWA',
    'SISWA/PELAJAR': 'PELAJAR/MAHASISWA',
    'PELAJAR/SISWA': 'PELAJAR/MAHASISWA',

    'PEGAWAI NEGERI SIPIL': 'PNS',
    'PNS': 'PNS',
    'PEGAWAI NEGRI SIPIL': 'PNS',
    'ASN': 'PNS',
    'PENSIUNAN PNS': 'PNS',
    'PNS/ASN': 'PNS',
    'ASN/PNS': 'PNS',
    'APARATUR SIPIL NEGARA': 'PNS',
    'PNS/PERAWAT': 'PNS',
    'PNS/DOSEN': 'PNS',
    'PENSINAN PNS': 'PNS',
    'PNS/TNI': 'PNS',
    'PENSIUNAN PNS/ASN': 'PNS',

    'WIRASWASTA': 'WIRASWASTA',
    'WIRASWASTA/UMKM': 'WIRASWASTA',
    'WIRAUSAHA': 'WIRASWASTA',
    'WIRA SWASTA': 'WIRASWASTA',
    'USAHA SENDIRI': 'WIRASWASTA',
    'PENGUSAHA': 'WIRASWASTA',

    'SWASTA': 'SWASTA',
    'KARYAWAN SWASTA': 'SWASTA',

    'HONORER': 'HONORER',
    'HONORER/SAPAM': 'HONORER',
    'KARYAWAN HONORER': 'HONORER',
};
const DAFTAR_KEY_PEKERJAAN = Object.keys(MAP_PEKERJAAN);

function normalisasiPekerjaan(v) {
    if (!v) return '';
    const key = v.toString().trim().toUpperCase().replace(/\s*\/\s*/g, '/').replace(/\s+/g, ' ');
    if (MAP_PEKERJAAN[key]) return MAP_PEKERJAAN[key];
    const cocokFuzzy = cariTerdekat(key, DAFTAR_KEY_PEKERJAAN);
    if (cocokFuzzy) return MAP_PEKERJAAN[cocokFuzzy];
    return key;
}

const JENIS_KELAMIN_FUZZY = ['LAKI-LAKI', 'LAKI LAKI', 'PEREMPUAN', 'WANITA', 'PRIA'];

function normalisasiJenisKelamin(v) {
    if (!v) return '';
    const key = v.toString().trim().toUpperCase().replace(/\s+/g, ' ');
    if (key === 'L' || key === 'LK' || key === 'LAKI2' || /^LAKI[\s-]*LAKI$/.test(key)) {
        return 'LAKI-LAKI';
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

function ekstrakAlamat(cellC) {
    const teks = cellC.toString();
    const mMentah = teks.match(/ALAMAT\s*:\s*(.*)/i);
    const sisaTeks = mMentah ? mMentah[1] : '';
    let alamat = sisaTeks
        .split(/,\s*NAMA DUSUN/i)[0]
        .split(/,?\s*RT\/RW/i)[0]
        .replace(/,?\s*RT\.?\s*\d{1,3}\s*$/i, '')
        .trim();

    return alamat;
}

function ekstrakRTdariNamaFile(namaFile) {
    const namaBersih = namaFile.replace(/\.(xlsx|xls)$/i, '');
    const m = namaBersih.match(/RT\s*\.?\s*(\d{1,3})/i);
    if (m) {
        return m[1].padStart(3, '0');
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

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const btnProses = document.getElementById('btnProses');
    const statusMsg = document.getElementById('statusMsg');
    const previewArea = document.getElementById('previewArea');

    if (dropZone && fileInput) {
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
    }

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
        const statusHeaderList = [];
        for (let i = 0; i < rows.length; i++) {
            const barisTeks = (rows[i] || []).map(c => (c || '').toString()).join(' ').toUpperCase();
            if (barisTeks.includes('BUKU INDUK PENDUDUK WNI')) {
                let status = null;
                if (/NON\s*PERMANEN/i.test(barisTeks)) {
                    status = 'NON PERMANEN';
                } else if (/\bPERMANEN\b/i.test(barisTeks)) {
                    status = 'PERMANEN';
                }

                if (status) {
                    statusHeaderList.push({
                        rowIndex: i,
                        status: status
                    });
                }
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
            const mKK = cellA.match(/(\d{10,18})/); 
            if (mKK) nomorKK = mKK[1];

            const alamat = ekstrakAlamat(cellC);
            const rt = rtDariFile;
            const endIdx = (k + 1 < indexKK.length) ? indexKK[k + 1] : rows.length;
            const anggota = [];
            for (let i = startIdx + 1; i < endIdx; i++) {
                const row = rows[i];
                const cellKolomKK = (row[0] || '').toString().trim();
                if (/^-+$/.test(cellKolomKK)) {
                    break;
                }

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
                    pekerjaan: normalisasiPekerjaan(row[9]),
                    kewarganegaraan: 'WNI',
                    status_penduduk: statusPendudukHeader,
                });
            }

            if (anggota.length > 0) {
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

    function deteksiNikDuplikatLintasKK(daftarKeluarga) {
        const petaNik = {};

        daftarKeluarga.forEach(k => {
            k.anggota.forEach(a => {
                const nik = (a.nik || '').trim();
                if (nik === '') return;

                if (!petaNik[nik]) petaNik[nik] = [];
                petaNik[nik].push({
                    nomor_kk: k.nomor_kk,
                    nama: a.nama_lengkap || '(tanpa nama)',
                    hubungan: a.hubungan_keluarga || '(tanpa hubungan)',
                });
            });
        });
        const duplikat = [];
        for (const nik in petaNik) {
            const kkUnik = [...new Set(petaNik[nik].map(x => x.nomor_kk))];
            if (kkUnik.length > 1) {
                duplikat.push({
                    nik,
                    kemunculan: petaNik[nik]
                });
            }
        }
        return duplikat;
    }

    function deteksiKKDuplikat(daftarKeluarga) {
        const petaKK = {}; 

        daftarKeluarga.forEach((k, idx) => {
            if (!petaKK[k.nomor_kk]) petaKK[k.nomor_kk] = [];
            petaKK[k.nomor_kk].push(idx);
        });

        const duplikat = [];
        for (const nomorKK in petaKK) {
            const indexList = petaKK[nomorKK];
            if (indexList.length > 1) {
                duplikat.push({
                    nomor_kk: nomorKK,
                    jumlah_blok: indexList.length,
                    nomor_urut: indexList.map(i => i + 1), 
                });
            }
        }
        return duplikat;
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
        let html = '<table class="preview-table"><thead><tr>' +
            '<th>No.</th><th>No. KK</th><th>RT</th><th>Alamat</th><th>Jumlah Anggota</th><th>Status Penduduk</th>' +
            '</tr></thead><tbody>';
        let adaKKKosong = false;
        daftarKeluarga.forEach((k, idx) => {
            totalAnggota += k.anggota.length;
            const statusKK = k.anggota.length > 0 ? k.anggota[0].status_penduduk : '-';
            const badgeClass = statusKK === 'NON PERMANEN' ? 'status-non-permanen' : 'status-permanen';

            const kkKosong = (k.nomor_kk === '');
            if (kkKosong) adaKKKosong = true;
            const rowStyle = kkKosong ? ' style="background:#fee2e2;"' : '';
            const isiNoKK = kkKosong ?
                '<span style="color:#b91c1c; font-weight:600;">⚠️ Tidak terbaca</span>' :
                k.nomor_kk;

            html += `<tr${rowStyle}>
    <td>${idx + 1}.</td>
    <td>${isiNoKK}</td>
    <td>${k.rt || '-'}</td>
    <td>${k.alamat_domisili || '-'}</td>
    <td>${k.anggota.length}</td>
    <td><span class="${badgeClass}">${statusKK || '-'}</span></td>
</tr>`;
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

        const peringatanEnum = adaNilaiMencurigakan ?
            '<p style="color:#b91c1c; font-size:0.85rem; margin-top:0.6rem;">⚠️ Ada nilai (ditandai merah) yang tidak cocok dengan pilihan resmi di database. Ini kemungkinan besar akan menyebabkan error "Data truncated" saat proses import. Cek dan perbaiki dulu di file Excel sumbernya.</p>' :
            '';
        const nikDuplikat = deteksiNikDuplikatLintasKK(daftarKeluarga);
        let peringatanDuplikat = '';
        if (nikDuplikat.length > 0) {
            let daftarHtml = '<ul style="margin:0.4rem 0 0 1.2rem; padding:0;">';
            nikDuplikat.forEach(d => {
                const lokasi = d.kemunculan
                    .map(k => `KK ${k.nomor_kk} (sebagai ${k.hubungan})`)
                    .join(', ');
                daftarHtml += `<li style="margin-bottom:0.2rem;">NIK <strong>${d.nik}</strong> (${d.kemunculan[0].nama}) ditemukan di: ${lokasi}</li>`;
            });
            daftarHtml += '</ul>';

            peringatanDuplikat = `<p style="color:#a16207; font-size:0.85rem; margin-top:0.6rem;">
    ⚠️ Ditemukan ${nikDuplikat.length} NIK yang tercatat di lebih dari 1 KK berbeda dalam file ini.
    Ini bisa jadi orang yang sama sudah "pindah" KK (misal dari anak jadi istri), atau bisa juga salah ketik NIK.
    Sistem tetap akan memproses semuanya (data terakhir akan menimpa KK sebelumnya), tapi sebaiknya diperiksa dulu:
    ${daftarHtml}
</p>`;
        }

        const kkDuplikat = deteksiKKDuplikat(daftarKeluarga);
        let peringatanKKDuplikat = '';
        if (kkDuplikat.length > 0) {
            let daftarHtml = '<ul style="margin:0.4rem 0 0 1.2rem; padding:0;">';
            kkDuplikat.forEach(d => {
                daftarHtml += `<li style="margin-bottom:0.2rem;">No. KK <strong>${d.nomor_kk}</strong> muncul sebagai ${d.jumlah_blok} blok terpisah (baris No. ${d.nomor_urut.join(', ')} di tabel ringkasan di atas)</li>`;
            });
            daftarHtml += '</ul>';

            peringatanKKDuplikat = `<p style="color:#a16207; font-size:0.85rem; margin-top:0.6rem;">
    ⚠️ Ditemukan ${kkDuplikat.length} No. KK yang terbaca lebih dari 1 kali sebagai blok keluarga terpisah dalam file ini.
    Ini bisa terjadi kalau ada 1 keluarga tercatat 2 kali di Excel (misal masuk di tabel PERMANEN dan NON PERMANEN sekaligus), atau salah ketik No. KK.
    Sistem tetap akan memproses semuanya secara berurutan (blok terakhir akan menimpa data blok sebelumnya untuk KK yang sama), tapi sebaiknya diperiksa dulu:
    ${daftarHtml}
</p>`;
        }

        previewArea.innerHTML = html +
            `<details style="margin-top:1rem;"><summary style="cursor:pointer; color:var(--hijau-tua); font-weight:600; font-size:0.88rem;">Lihat detail per anggota (${totalAnggota} orang) — cek hasil parsing sebelum import</summary>${peringatanEnum}${peringatanDuplikat}${peringatanKKDuplikat}${detailHtml}</details>`;

        statusMsg.textContent = `Terbaca ${daftarKeluarga.length} KK, total ${totalAnggota} anggota.`;
        statusMsg.className = 'status-msg';
        btnProses.style.display = 'inline-block';
    }

    if (btnProses) {
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
    }

    function tampilkanHasil(r) {
        const hasilCard = document.getElementById('hasilCard');
        const grid = document.getElementById('ringkasanGrid');
        const gagalList = document.getElementById('gagalList');

        if (grid && gagalList && hasilCard) {
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
    }
});