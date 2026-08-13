<?php
/**
 * dashboard_helpers.php
 * Kumpulan fungsi bersama untuk dashboard.php dan dashboard_load.php,
 * supaya logika filter, grouping, dan render HTML tidak duplikat
 * (dan tetap konsisten antara load awal & load via AJAX).
 */

// ==========================
// Ambil & validasi filter dari $_GET (dipakai bareng oleh dashboard.php & dashboard_load.php)
// ==========================
function ambil_filter_dari_get()
{
    $rt_filter = "";
    if (isset($_GET['rt']) && $_GET['rt'] !== "") {
        $rt_digits = preg_replace('/\D/', '', $_GET['rt']);
        if ($rt_digits !== "") {
            $rt_filter = $rt_digits;
        }
    }

    $status_penduduk_filter = "";
    $status_penduduk_valid  = ['PERMANEN', 'NON PERMANEN', 'MENINGGAL', 'TIDAK LENGKAP'];
    if (isset($_GET['status_penduduk']) && in_array($_GET['status_penduduk'], $status_penduduk_valid, true)) {
        $status_penduduk_filter = $_GET['status_penduduk'];
    }

    $tampilan_khusus = "";
    if (isset($_GET['tampilan']) && in_array($_GET['tampilan'], ['meninggal', 'tidak_lengkap'], true)) {
        $tampilan_khusus = $_GET['tampilan'];
    }

    $search = "";
    if (isset($_GET['search'])) {
        $search = trim((string) $_GET['search']);
        // batasi panjang supaya tidak dipakai untuk query aneh-aneh
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }
    }

    return [
        'rt'              => $rt_filter,
        'status_penduduk' => $status_penduduk_filter,
        'tampilan'        => $tampilan_khusus,
        'search'          => $search,
    ];
}

// ==========================
// Bangun klausa WHERE (array kondisi) berdasarkan filter di atas
// ==========================
function build_where_penduduk($conn, array $filter)
{
    $where = [];

    if ($filter['rt'] !== "") {
        $where[] = "CAST(k.rt AS UNSIGNED) = " . (int) $filter['rt'];
    }

    // Mode "meninggal"/"tidak lengkap" bisa dipicu dari 2 sumber:
    // - $filter['tampilan']       (cara lama: ?tampilan=meninggal / ?tampilan=tidak_lengkap)
    // - $filter['status_penduduk'] (cara baru: ?status_penduduk=MENINGGAL / ?status_penduduk=TIDAK+LENGKAP)
    // Keduanya harus dicek, karena link di dashboard.php sekarang memakai status_penduduk.
    $mode_meninggal     = ($filter['tampilan'] === 'meninggal') || ($filter['status_penduduk'] === 'MENINGGAL');
    $mode_tidak_lengkap = ($filter['tampilan'] === 'tidak_lengkap') || ($filter['status_penduduk'] === 'TIDAK LENGKAP');

    if ($mode_meninggal) {
        // Tampilkan HANYA yang berstatus MENINGGAL, apa pun status_lengkap-nya
        $where[] = "p.status_penduduk = 'MENINGGAL'";
    } elseif ($mode_tidak_lengkap) {
        // Tampilkan HANYA data yang belum lengkap, apa pun status_penduduk-nya
        $where[] = "p.status_lengkap = 'TIDAK LENGKAP'";
    } else {
        // TAMPILAN NORMAL: hanya data LENGKAP dan berstatus PERMANEN/NON PERMANEN
        $where[] = "p.status_lengkap = 'LENGKAP'";
        $where[] = "p.status_penduduk IN ('PERMANEN', 'NON PERMANEN')";
        if ($filter['status_penduduk'] !== "" && in_array($filter['status_penduduk'], ['PERMANEN', 'NON PERMANEN'], true)) {
            $where[] = "p.status_penduduk = '" . mysqli_real_escape_string($conn, $filter['status_penduduk']) . "'";
        }
    }

    if ($filter['search'] !== "") {
        $kw = mysqli_real_escape_string($conn, $filter['search']);
        // Dicari langsung di database (bukan di browser) supaya tetap cepat
        // walau datanya ribuan baris. Mencakup NIK, nama, No. KK, alamat, dll.
        $where[] = "(
            p.nik LIKE '%$kw%'
            OR p.nama_lengkap LIKE '%$kw%'
            OR k.nomor_kk LIKE '%$kw%'
            OR k.alamat_domisili LIKE '%$kw%'
            OR p.tempat_lahir LIKE '%$kw%'
            OR p.pekerjaan LIKE '%$kw%'
            OR p.pendidikan_terakhir LIKE '%$kw%'
            OR p.agama LIKE '%$kw%'
            OR p.kewarganegaraan LIKE '%$kw%'
            OR p.status_penduduk LIKE '%$kw%'
            OR p.hubungan_keluarga LIKE '%$kw%'
            OR p.jenis_kelamin LIKE '%$kw%'
        )";
    }

    return $where;
}

function where_sql(array $where)
{
    return !empty($where) ? " WHERE " . implode(" AND ", $where) . " " : "";
}

// ==========================
// Query dasar (kolom + FROM + JOIN) yang dipakai untuk data & untuk statistik
// ==========================
function base_select_penduduk()
{
    return "
        SELECT
            p.id_penduduk,
            p.nik,
            k.id_keluarga,
            k.nomor_kk,
            p.nama_lengkap,
            p.tempat_lahir,
            p.tanggal_lahir,
            p.jenis_kelamin,
            p.agama,
            p.pekerjaan,
            p.pendidikan_terakhir,
            p.kewarganegaraan,
            p.status_penduduk,
            p.status_lengkap,
            p.hubungan_keluarga,
            k.rt,
            k.alamat_domisili
        FROM penduduk p
        LEFT JOIN keluarga k ON p.id_keluarga_fk = k.id_keluarga
    ";
    // LEFT JOIN (bukan INNER JOIN) supaya baris yang id_keluarga_fk-nya masih NULL
    // (misal hasil import massal yang datanya belum lengkap) tetap ikut tampil.
}

// ==========================
// Statistik (total penduduk, total KK, laki-laki, perempuan) dihitung LANGSUNG
// di database (SUM/COUNT), TIDAK dengan menarik semua baris ke PHP.
// Query ini ringan walau datanya ribuan baris karena tidak mengambil data mentah.
// ==========================
function ambil_statistik($conn, array $where)
{
    $sql = "
        SELECT
            COUNT(*) AS total_penduduk,
            COUNT(DISTINCT k.nomor_kk) AS total_kk,
            SUM(CASE WHEN UPPER(p.jenis_kelamin) = 'LAKI-LAKI' THEN 1 ELSE 0 END) AS total_laki,
            SUM(CASE WHEN UPPER(p.jenis_kelamin) = 'PEREMPUAN' THEN 1 ELSE 0 END) AS total_perempuan
        FROM penduduk p
        LEFT JOIN keluarga k ON p.id_keluarga_fk = k.id_keluarga
    " . where_sql($where);

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return ['total_penduduk' => 0, 'total_kk' => 0, 'total_laki' => 0, 'total_perempuan' => 0];
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    return [
        'total_penduduk' => (int) $row['total_penduduk'],
        'total_kk'       => (int) $row['total_kk'],
        'total_laki'     => (int) $row['total_laki'],
        'total_perempuan' => (int) $row['total_perempuan'],
    ];
}

// ==========================
// Ambil SEBAGIAN data saja (LIMIT/OFFSET) — inti dari perbaikan performa.
// Database hanya mengirim sebanyak $limit baris, bukan semua data.
// ==========================
function ambil_data_penduduk($conn, array $where, $limit, $offset)
{
    $limit  = max(1, min(500, (int) $limit));
    $offset = max(0, (int) $offset);

    $query = base_select_penduduk()
        . where_sql($where)
        . " ORDER BY k.rt ASC, k.nomor_kk ASC, p.id_penduduk ASC "
        . " LIMIT $limit OFFSET $offset ";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Gagal mengambil data: " . mysqli_error($conn));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_free_result($result);

    return $data;
}

// Helper untuk class CSS yang aman dari spasi (mis. "NON PERMANEN" -> "non-permanen")
function cls($v)
{
    return str_replace(' ', '-', strtolower(trim((string) ($v ?? ''))));
}

// ==========================
// KELOMPOKKAN DATA PER KK (meniru struktur excel)
// ==========================
function group_by_kk(array $data_penduduk)
{
    $grouped = [];
    foreach ($data_penduduk as $p) {
        $kk = $p['nomor_kk'];
        if (!isset($grouped[$kk])) {
            $grouped[$kk] = [
                'id_keluarga'     => $p['id_keluarga'],
                'nomor_kk'        => $kk,
                'alamat_domisili' => $p['alamat_domisili'],
                'rt'              => $p['rt'],
                'anggota'         => [],
            ];
        }
        $grouped[$kk]['anggota'][] = $p;
    }

    // Urutkan anggota dalam tiap keluarga: Kepala Keluarga dulu, baru yang lain
    $prioritas_hubungan = [
        'KEPALA KELUARGA' => 0,
        'SUAMI'           => 1,
        'ISTRI'           => 1,
        'ANAK'            => 2,
    ];
    foreach ($grouped as &$kel) {
        usort($kel['anggota'], function ($a, $b) use ($prioritas_hubungan) {
            $pa = $prioritas_hubungan[strtoupper($a['hubungan_keluarga'] ?? '')] ?? 3;
            $pb = $prioritas_hubungan[strtoupper($b['hubungan_keluarga'] ?? '')] ?? 3;
            return $pa <=> $pb;
        });
    }
    unset($kel);

    return $grouped;
}

// ==========================
// HELPER: HITUNG UMUR
// ==========================
function hitung_umur($tanggal_lahir)
{
    if (empty($tanggal_lahir) || $tanggal_lahir === '0000-00-00') {
        return '-';
    }
    try {
        $lahir    = new DateTime($tanggal_lahir);
        $sekarang = new DateTime();
        $diff     = $lahir->diff($sekarang);
        return "{$diff->y} THN {$diff->m} BLN {$diff->d} HARI";
    } catch (Exception $e) {
        return '-';
    }
}

// ==========================
// Render HTML <tbody> per KK (dipakai baik untuk load awal maupun AJAX load more/search)
// ==========================
function render_grup_html(array $grouped)
{
    if (empty($grouped)) {
        return '<tbody><tr><td colspan="12" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada data.</td></tr></tbody>';
    }

    $html = '';
    foreach ($grouped as $kel) {
        $html .= '<tbody class="kk-group">';
        $html .= '<tr class="kk-header-row"><th colspan="12">';
        $html .= '<span class="kk-tag">No. KK: ' . htmlspecialchars($kel['nomor_kk'] ?? '-') . '</span>';
        $html .= '<span class="rt-tag">RT ' . htmlspecialchars($kel['rt'] ?? '-') . '</span>';
        $html .= 'Alamat: ' . htmlspecialchars($kel['alamat_domisili'] ?? '-');
        $html .= '<a class="btn-edit-kk" href="data_detail?id_keluarga=' . urlencode($kel['id_keluarga'] ?? '') . '">Edit</a>';
        $html .= '</th></tr>';

        foreach ($kel['anggota'] as $i => $p) {
            $html .= '<tr class="data-row" data-kk="' . htmlspecialchars(strtoupper($kel['nomor_kk'] ?? '')) . '">';
            $html .= '<td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['nik'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['nama_lengkap'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['tempat_lahir'] ?? '') . ', '
                . (!empty($p['tanggal_lahir']) ? htmlspecialchars(date('d-m-Y', strtotime($p['tanggal_lahir']))) : '-') . '</td>';
            $html .= '<td>' . htmlspecialchars(hitung_umur($p['tanggal_lahir'] ?? null)) . '</td>';
            $html .= '<td>' . htmlspecialchars($p['jenis_kelamin'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['hubungan_keluarga'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['agama'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['pendidikan_terakhir'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['pekerjaan'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['kewarganegaraan'] ?? '') . '</td>';
            $statusClass = cls($p['status_penduduk'] ?? '');
            $html .= '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($p['status_penduduk'] ?? '') . '</span></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
    }

    return $html;
}