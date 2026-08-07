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
    const key = v.toString().trim().toUpperCase().replace(/\s*\/\s*/g, '/').replace(/\s+/g, ' ');
    if (MAP_PENDIDIKAN[key]) return MAP_PENDIDIKAN[key];
    const cocokFuzzy = cariTerdekat(key, DAFTAR_KEY_PENDIDIKAN);
    if (cocokFuzzy) return MAP_PENDIDIKAN[cocokFuzzy];
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

function setSelectValue(block, name, value) {
    const el = block.querySelector(`[name="${name}"]`);
    if (!el || !value) return;
    const opsi = Array.from(el.options).find(o => o.value.toUpperCase() === value.toUpperCase());
    if (opsi) el.value = opsi.value;
}

function anggotaBlockKosongDiForm() {
    const container = document.getElementById('anggotaContainer');
    const blocks = container.querySelectorAll('.anggota-block');
    if (blocks.length === 0) return true;
    if (blocks.length === 1) {
        const nama = blocks[0].querySelector('[name="nama_lengkap[]"]');
        return !nama || nama.value.trim() === '';
    }
    return false;
}

const btnImportExcel = document.getElementById('btnImportExcel');
if (btnImportExcel) {
    btnImportExcel.addEventListener('click', () => {
        document.getElementById('importExcelInput').click();
    });
}

const importExcelInput = document.getElementById('importExcelInput');
if (importExcelInput) {
    importExcelInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        e.target.value = '';
        if (!file) return;

        if (!anggotaBlockKosongDiForm()) {
            const lanjut = confirm('Form sudah berisi data anggota. Import akan MENGGANTI seluruh anggota yang sudah ada di form ini. Lanjutkan?');
            if (!lanjut) return;
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
                prosesImportExcel(rows);
            } catch (err) {
                alert('Gagal membaca file Excel: ' + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
    });
}

function prosesImportExcel(rows) {
    let nomorKK = '',
        rt = '',
        alamat = '';
    let headerRowIdx = -1;
    let nomorKKRowIdx = -1;
    const statusHeaderList = [];

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cellA = (row[0] || '').toString();

        if (cellA.trim().toUpperCase().startsWith('NO. KK')) {
            const mKK = cellA.match(/(\d{16})/);
            if (mKK) {
                nomorKK = mKK[1];
                nomorKKRowIdx = i;
            }
        }
        if (cellA.trim().toUpperCase().startsWith('ALAMAT')) {
            const mAlamat = cellA.match(/ALAMAT\s*:\s*(.*?),\s*NAMA DUSUN/i);
            if (mAlamat) alamat = mAlamat[1].trim();
            const mRT = cellA.match(/RT\/RW\s*:\s*(\d+)/i);
            if (mRT) rt = mRT[1];
        }
        const cellC = (row[2] || '').toString();
        if (cellC.toUpperCase().includes('ALAMAT')) {
            const mAlamat = cellC.match(/ALAMAT\s*:\s*(.*?),\s*NAMA DUSUN/i);
            if (mAlamat) alamat = mAlamat[1].trim();
            const mRT = cellC.match(/RT\/RW\s*:\s*(\d+)/i);
            if (mRT) rt = mRT[1];
        }
        if (cellA.trim().toUpperCase() === 'NO' && (row[1] || '').toString().toUpperCase().includes('NAMA')) {
            headerRowIdx = i;
        }
        if (cellA.trim().toUpperCase().startsWith('PERIODE')) {
            const cellRT = (row[12] || '').toString();
            let status = 'PERMANEN';
            if (/NON PERMANEN/i.test(cellRT)) status = 'NON PERMANEN';
            else if (/PERMANEN/i.test(cellRT)) status = 'PERMANEN';
            statusHeaderList.push({
                rowIndex: i,
                status: status
            });
        }
    }
    let statusPendudukHeader = 'PERMANEN';
    const acuanBaris = nomorKKRowIdx > -1 ? nomorKKRowIdx : rows.length;
    for (const h of statusHeaderList) {
        if (h.rowIndex <= acuanBaris) statusPendudukHeader = h.status;
        else break;
    }

    if (!nomorKK && headerRowIdx === -1) {
        alert('Format Excel tidak dikenali. Pastikan menggunakan format Data Keluarga standar.');
        return;
    }

    if (nomorKK) document.querySelector('input[name="nomor_kk"]').value = nomorKK;
    if (rt) document.querySelector('input[name="rt"]').value = rt.padStart(3, '0');
    if (alamat) document.querySelector('input[name="alamat_domisili"]').value = alamat;

    const dataRows = [];
    for (let i = (headerRowIdx > -1 ? headerRowIdx + 1 : 0); i < rows.length; i++) {
        const row = rows[i];
        const cellA = (row[0] || '').toString().trim();
        if (cellA.toUpperCase().startsWith('NO. KK')) continue;
        const nama = (row[1] || '').toString().trim();
        const nik = (row[2] || '').toString().trim();
        if (!nama && !nik) continue;
        dataRows.push(row);
    }

    if (dataRows.length === 0) {
        alert('Tidak ada data anggota yang terbaca dari file.');
        return;
    }

    const container = document.getElementById('anggotaContainer');
    container.innerHTML = '';

    dataRows.forEach((row) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = blokAnggotaKosongHTML().trim();
        const block = wrapper.firstChild;

        const nama = (row[1] || '').toString().trim();
        const nik = (row[2] || '').toString().trim();
        const tempatLahir = (row[3] || '').toString().trim();
        const tglLahir = excelDateToISO(row[4]);
        const jk = normalisasiJenisKelamin(row[5]);
        const hubungan = normalisasiHubungan(row[6]);
        const agama = normalisasiAgama(row[7]);
        const pendidikan = normalisasiPendidikan(row[8]);
        const pekerjaan = (row[9] || '').toString().trim();

        block.querySelector('[name="nama_lengkap[]"]').value = nama;
        block.querySelector('[name="nik[]"]').value = nik;
        block.querySelector('[name="tempat_lahir[]"]').value = tempatLahir;
        block.querySelector('[name="tanggal_lahir[]"]').value = tglLahir;
        setSelectValue(block, 'jenis_kelamin[]', jk);
        block.querySelector('[name="hubungan_keluarga[]"]').value = hubungan;
        setSelectValue(block, 'agama[]', agama);
        setSelectValue(block, 'pendidikan_terakhir[]', pendidikan);
        block.querySelector('[name="pekerjaan[]"]').value = pekerjaan;
        setSelectValue(block, 'status_penduduk[]', statusPendudukHeader);
        setSelectValue(block, 'kewarganegaraan[]', 'WNI');

        container.appendChild(block);
    });

    renumberBlocks();
    
    const importStatus = document.getElementById('importStatus');
    if(importStatus) {
        importStatus.textContent = `${dataRows.length} anggota berhasil diimpor. Cek ulang data sebelum menyimpan.`;
    }
}

function renumberBlocks() {
    document.querySelectorAll('#anggotaContainer .anggota-block .block-num').forEach((el, idx) => {
        el.textContent = idx + 1;
    });
}

// Fungsi harus diletakkan pada scope global (window) agar `onclick="hapusBlokAnggota(this)"` dari HTML bisa bekerja.
window.hapusBlokAnggota = function(btn) {
    const container = document.getElementById('anggotaContainer');
    if (container.children.length <= 1) {
        alert('Minimal harus ada 1 anggota keluarga.');
        return;
    }
    if (confirm('Hapus anggota ini dari form?')) {
        btn.closest('.anggota-block').remove();
        renumberBlocks();
    }
}

function blokAnggotaKosongHTML() {
    return `
    <div class="anggota-block">
        <div class="anggota-block-header">
            <h3>Anggota Keluarga <span class="block-num"></span></h3>
            <button type="button" class="btn-hapus-block" onclick="hapusBlokAnggota(this)">&times; Hapus</button>
        </div>
        <input type="hidden" name="id_penduduk[]" value="">
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap[]" maxlength="100" required>
            </div>
            <div class="form-group">
                <label>Nomor Induk Kependudukan</label>
                <input type="text" name="nik[]" maxlength="16" pattern="\\d{16}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin[]" required>
                    <option value="">-- Pilih --</option>
                    <option value="LAKI-LAKI">LAKI-LAKI</option>
                    <option value="PEREMPUAN">PEREMPUAN</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir[]" maxlength="50" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir[]" required>
            </div>
            <div class="form-group">
                <label>Agama</label>
                <select name="agama[]" required>
                    <option value="">-- Pilih --</option>
                    <option value="ISLAM">ISLAM</option>
                    <option value="KRISTEN">KRISTEN</option>
                    <option value="KATOLIK">KATOLIK</option>
                    <option value="HINDU">HINDU</option>
                    <option value="BUDDHA">BUDDHA</option>
                    <option value="KONGHUCU">KONGHUCU</option>
                </select>
            </div>
            <div class="form-group">
                <label>Pendidikan Terakhir</label>
                <select name="pendidikan_terakhir[]" required>
                    <option value="">-- Pilih --</option>
                    <option value="TIDAK SEKOLAH">TIDAK SEKOLAH</option>
                    <option value="SD/SEDERAJAT">SD/SEDERAJAT</option>
                    <option value="SLTP/SEDERAJAT">SLTP/SEDERAJAT</option>
                    <option value="SLTA/SEDERAJAT">SLTA/SEDERAJAT</option>
                    <option value="DIPLOMA I/II/III">DIPLOMA I/II/III</option>
                    <option value="DIPLOMA IV/STRATA I">DIPLOMA IV/STRATA I</option>
                    <option value="STRATA II">STRATA II</option>
                    <option value="STRATA III">STRATA III</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jenis Pekerjaan</label>
                <input type="text" name="pekerjaan[]" maxlength="100" required>
            </div>
            <div class="form-group">
                <label>Status Hubungan Dalam Keluarga</label>
                <input type="text" name="hubungan_keluarga[]" maxlength="20" required>
            </div>
            <div class="form-group">
                <label>Kewarganegaraan</label>
                <select name="kewarganegaraan[]" required>
                    <option value="WNI">WNI</option>
                    <option value="WNA">WNA</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status Penduduk</label>
                <select name="status_penduduk[]" required>
                    <option value="PERMANEN">PERMANEN</option>
                    <option value="NON PERMANEN">NON PERMANEN</option>
                    <option value="MENINGGAL">MENINGGAL</option>
                </select>
            </div>
        </div>
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
    const btnTambahAnggota = document.getElementById('btnTambahAnggota');
    if(btnTambahAnggota) {
        btnTambahAnggota.addEventListener('click', () => {
            const container = document.getElementById('anggotaContainer');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = blokAnggotaKosongHTML().trim();
            container.appendChild(wrapper.firstChild);
            renumberBlocks();
            container.lastElementChild.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        });
    }
    
    // Inisialisasi awal penomoran blok
    renumberBlocks();
});