// scripts/dashboard.js

// Fungsi inisialisasi yang dipanggil dari dashboard.php dengan menyertakan konfigurasi PHP
function initDashboard(config) {
    const searchInput = document.getElementById('searchInput');
    const dataTable = document.getElementById('dataTable');
    const noResult = document.getElementById('noResult');
    const paginationInfo = document.getElementById('paginationInfo');
    const loadMoreWrap = document.getElementById('loadMoreWrap');
    const loadMoreBtn = document.getElementById('loadMoreBtn');

    const PAGE_SIZE = 100;

    const FILTER_RT = config.filterRt;
    const FILTER_STATUS = config.filterStatus;
    const FILTER_TAMPILAN = config.filterTampilan;

    let offset = config.jumlahDimuatAwal;
    let totalPenduduk = config.totalPenduduk;
    let searchDebounce = null;
    let searchToken = 0;

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

    // Inisialisasi awal UI saat load pertama
    updatePaginationInfo(offset, totalPenduduk, false);
    loadMoreWrap.style.display = (offset < totalPenduduk) ? 'flex' : 'none';

    function buildQuery(params) {
        const usp = new URLSearchParams(params);
        if (FILTER_RT) usp.set('rt', FILTER_RT);
        if (FILTER_STATUS) usp.set('status_penduduk', FILTER_STATUS);
        if (FILTER_TAMPILAN) usp.set('tampilan', FILTER_TAMPILAN);
        return usp.toString();
    }

    async function muatData(offsetVal, keyword, append) {
        const myToken = ++searchToken;
        const qs = buildQuery({ offset: offsetVal, limit: PAGE_SIZE, search: keyword });

        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = 'Memuat...';

        try {
            const res = await fetch('dashboard_load?' + qs);
            const data = await res.json();

            if (myToken !== searchToken) return;

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

    searchInput.addEventListener('keyup', function() {
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
}

// Inisialisasi toggle navigasi yang independen
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
        });
    }
});

// Inisialisasi toggle navigasi yang independen dan form profil
document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle Navigasi Sidebar
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
        });
    }

    // ==========================
    // DRAG & DROP UPLOAD GAMBAR BAGAN (Halaman Profil)
    // ==========================
    const dropzone = document.getElementById('dropzone');
    
    // Pastikan kode ini hanya dieksekusi jika berada di halaman profil
    if (dropzone) { 
        const baganInput = document.getElementById('baganInput');
        const previewWrap = document.getElementById('dzPreviewWrap');
        const previewImg = document.getElementById('dzPreviewImg');
        const previewCaption = document.getElementById('dzPreviewCaption');
        const hapusCheck = document.getElementById('hapusBaganCheck');

        function tampilkanPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewCaption.textContent = 'Gambar baru (belum disimpan): ' + file.name;
                previewWrap.classList.add('show');
                
                // Kalau sebelumnya user centang "hapus gambar", batalkan otomatis
                if (hapusCheck) {
                    hapusCheck.checked = false;
                    hapusCheck.closest('.dz-remove-check').style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }

        dropzone.addEventListener('click', () => baganInput.click());

        baganInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                tampilkanPreview(this.files[0]);
            }
        });

        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', function(e) {
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('File yang ditarik bukan gambar. Silakan pilih file JPG, PNG, atau WEBP.');
                return;
            }
            
            // Supaya file yang di-drop ikut terkirim saat form disubmit
            const dt = new DataTransfer();
            dt.items.add(file);
            baganInput.files = dt.files;
            tampilkanPreview(file);
        });

        // Kosongkan pratinjau baru jika pengguna centang hapus gambar
        if (hapusCheck) {
            hapusCheck.addEventListener('change', function() {
                if (this.checked) {
                    baganInput.value = '';
                }
            });
        }
    }
});