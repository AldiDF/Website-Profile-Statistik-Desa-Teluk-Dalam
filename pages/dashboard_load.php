<?php
/**
 * dashboard_load.php
 * Endpoint AJAX: mengembalikan JSON berisi potongan HTML tabel (per 100 baris)
 * sesuai filter/offset/kata kunci pencarian. Dipanggil dari JS di dashboard.php
 * saat klik "Tampilkan 100 Berikutnya" atau saat mengetik di kotak pencarian.
 *
 * Kuncinya: query ke database SELALU pakai LIMIT, jadi berapa pun jumlah data
 * penduduk di database, yang ditarik & dikirim ke browser tetap sebesar limit
 * per request (default 100) — bukan semua data sekaligus.
 */

require '../databases/auth_check.php';
require '../databases/connection.php';
require 'dashboard_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database tidak tersedia.']);
    exit;
}

$filter = ambil_filter_dari_get();

$limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit  = max(1, min(200, $limit));
$offset = max(0, $offset);

$where = build_where_penduduk($conn, $filter);

$stat  = ambil_statistik($conn, $where);
$total = $stat['total_penduduk'];

$data_penduduk = ambil_data_penduduk($conn, $where, $limit, $offset);
$grouped       = group_by_kk($data_penduduk);

$html         = render_grup_html($grouped);
$jumlah_baris = count($data_penduduk);
$new_offset   = $offset + $jumlah_baris;

echo json_encode([
    'html'     => $html,
    'total'    => $total,
    'offset'   => $new_offset,
    'has_more' => $new_offset < $total,
    'shown_now_batch' => $jumlah_baris,
]);
