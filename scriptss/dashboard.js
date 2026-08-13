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