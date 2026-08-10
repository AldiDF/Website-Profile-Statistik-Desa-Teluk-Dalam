<?php
session_start();
require '../databases/connection.php';
if (!isset($conn)) {
  die("Koneksi database tidak tersedia.");
}
$total_penduduk     = 0;
$total_kk           = 0;
$total_laki         = 0;
$total_perempuan    = 0;
$total_tetap        = 0;
$total_tidak_tetap  = 0;
$total_rt           = 0;
$nama_kepala_desa   = "-";
$q = mysqli_query($conn, "
    SELECT COUNT(*) AS jumlah
    FROM penduduk
    WHERE status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
");
if ($q) $total_penduduk = (int) mysqli_fetch_assoc($q)['jumlah'];

$q = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM keluarga");
if ($q) $total_kk = (int) mysqli_fetch_assoc($q)['jumlah'];
$gender_labels = [];
$gender_data   = [];
$q = mysqli_query($conn, "
    SELECT jenis_kelamin, COUNT(*) AS jumlah
    FROM penduduk
    WHERE jenis_kelamin IS NOT NULL
      AND status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY jenis_kelamin
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    $label = ($row['jenis_kelamin'] === 'LAKI-LAKI') ? 'Laki-laki' : 'Perempuan';
    $gender_labels[] = $label;
    $gender_data[]   = (int) $row['jumlah'];
    if ($row['jenis_kelamin'] === 'LAKI-LAKI') {
      $total_laki = (int) $row['jumlah'];
    } else {
      $total_perempuan = (int) $row['jumlah'];
    }
  }
}
$status_labels = [];
$status_data   = [];
$total_meninggal = 0;
$q = mysqli_query($conn, "
    SELECT status_penduduk, COUNT(*) AS jumlah
    FROM penduduk
    WHERE status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY status_penduduk
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    if ($row['status_penduduk'] === 'PERMANEN') {
      $total_tetap = (int) $row['jumlah'];
      $status_labels[] = 'Penduduk Tetap';
      $status_data[]   = $total_tetap;
    } elseif ($row['status_penduduk'] === 'NON PERMANEN') {
      $total_tidak_tetap = (int) $row['jumlah'];
      $status_labels[] = 'Penduduk Tidak Tetap';
      $status_data[]   = $total_tidak_tetap;
    } else {
      $total_meninggal = (int) $row['jumlah'];
    }
  }
}
$pekerjaan_labels = [];
$pekerjaan_data   = [];
$q = mysqli_query($conn, "
    SELECT
        CASE
            WHEN pekerjaan IS NULL OR TRIM(pekerjaan) = '' THEN 'Tidak/Belum Bekerja'
            ELSE pekerjaan
        END AS pekerjaan_bersih,
        COUNT(*) AS jumlah
    FROM penduduk
    WHERE status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY pekerjaan_bersih
    ORDER BY jumlah DESC
");
$pekerjaan_raw = [];
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    $pekerjaan_raw[] = $row;
  }
}
$batas_top_pekerjaan = 7;
$lainnya_total = 0;
foreach ($pekerjaan_raw as $i => $row) {
  if ($i < $batas_top_pekerjaan) {
    $pekerjaan_labels[] = $row['pekerjaan_bersih'];
    $pekerjaan_data[]   = (int) $row['jumlah'];
  } else {
    $lainnya_total += (int) $row['jumlah'];
  }
}
if ($lainnya_total > 0) {
  $pekerjaan_labels[] = 'Lainnya';
  $pekerjaan_data[]   = $lainnya_total;
}
$rt_labels = [];
$rt_data   = [];
$q = mysqli_query($conn, "
    SELECT CAST(rt AS UNSIGNED) AS rt_num, COUNT(*) AS jumlah_kk
    FROM keluarga
    WHERE rt IS NOT NULL AND rt <> ''
    GROUP BY rt_num
    ORDER BY rt_num ASC
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    $rt_labels[] = 'RT ' . $row['rt_num'];
    $rt_data[]   = (int) $row['jumlah_kk'];
  }
}
$total_rt = count($rt_labels);
$kelompok_usia_urut = [
  '0-4',
  '5-9',
  '10-14',
  '15-19',
  '20-24',
  '25-29',
  '30-34',
  '35-39',
  '40-44',
  '45-49',
  '50-54',
  '55-59',
  '60-64',
  '65-69',
  '70-74',
  '75+',
];
$piramida_laki = array_fill_keys($kelompok_usia_urut, 0);
$piramida_perempuan = array_fill_keys($kelompok_usia_urut, 0);

$q = mysqli_query($conn, "
    SELECT
        CASE
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 0 AND 4 THEN '0-4'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 5 AND 9 THEN '5-9'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 10 AND 14 THEN '10-14'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 15 AND 19 THEN '15-19'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 20 AND 24 THEN '20-24'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 25 AND 29 THEN '25-29'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 30 AND 34 THEN '30-34'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 35 AND 39 THEN '35-39'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 40 AND 44 THEN '40-44'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 45 AND 49 THEN '45-49'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 50 AND 54 THEN '50-54'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 55 AND 59 THEN '55-59'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 60 AND 64 THEN '60-64'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 65 AND 69 THEN '65-69'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 70 AND 74 THEN '70-74'
            ELSE '75+'
        END AS kelompok_usia,
        jenis_kelamin,
        COUNT(*) AS jumlah
    FROM penduduk
    WHERE tanggal_lahir IS NOT NULL
      AND jenis_kelamin IS NOT NULL
      AND status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY kelompok_usia, jenis_kelamin
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    $kelompok = $row['kelompok_usia'];
    if (!isset($piramida_laki[$kelompok])) continue;
    if ($row['jenis_kelamin'] === 'LAKI-LAKI') {
      $piramida_laki[$kelompok] = (int) $row['jumlah'];
    } else {
      $piramida_perempuan[$kelompok] = (int) $row['jumlah'];
    }
  }
}
$piramida_laki_data = array_map(fn($v) => -$v, array_values($piramida_laki));
$piramida_perempuan_data = array_values($piramida_perempuan);
$agama_labels = [];
$agama_data   = [];
$q = mysqli_query($conn, "
    SELECT agama, COUNT(*) AS jumlah
    FROM penduduk
    WHERE agama IS NOT NULL
      AND status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY agama
    ORDER BY jumlah DESC
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    $agama_labels[] = ucfirst(strtolower($row['agama']));
    $agama_data[]   = (int) $row['jumlah'];
  }
}
$urutan_pendidikan = [
  'TIDAK SEKOLAH',
  'PAUD/TK',
  'SD/SEDERAJAT',
  'SLTP/SEDERAJAT',
  'SLTA/SEDERAJAT',
  'DIPLOMA I/II/III',
  'DIPLOMA IV/STRATA I',
  'STRATA II',
  'STRATA III',
];
$pendidikan_jumlah = array_fill_keys($urutan_pendidikan, 0);
$q = mysqli_query($conn, "
    SELECT pendidikan_terakhir, COUNT(*) AS jumlah
    FROM penduduk
    WHERE pendidikan_terakhir IS NOT NULL
      AND status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
    GROUP BY pendidikan_terakhir
");
if ($q) {
  while ($row = mysqli_fetch_assoc($q)) {
    if (isset($pendidikan_jumlah[$row['pendidikan_terakhir']])) {
      $pendidikan_jumlah[$row['pendidikan_terakhir']] = (int) $row['jumlah'];
    }
  }
}
$pendidikan_labels = [];
$pendidikan_data   = [];
foreach ($pendidikan_jumlah as $label => $jumlah) {
  if ($jumlah > 0) {
    $pendidikan_labels[] = $label;
    $pendidikan_data[]   = $jumlah;
  }
}
$usia_produktif = 0;
$usia_muda      = 0;
$usia_tua       = 0;
$q = mysqli_query($conn, "
    SELECT
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 15 AND 64 THEN 1 ELSE 0 END) AS produktif,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 15 THEN 1 ELSE 0 END) AS usia_muda,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) > 64 THEN 1 ELSE 0 END) AS usia_tua
    FROM penduduk
    WHERE tanggal_lahir IS NOT NULL
      AND status_penduduk IN ('PERMANEN', 'NON PERMANEN')
      AND status_lengkap = 'LENGKAP'
");
if ($q) {
  $row = mysqli_fetch_assoc($q);
  $usia_produktif = (int) $row['produktif'];
  $usia_muda      = (int) $row['usia_muda'];
  $usia_tua       = (int) $row['usia_tua'];
}
$rasio_ketergantungan = $usia_produktif > 0
  ? round((($usia_muda + $usia_tua) / $usia_produktif) * 100, 1)
  : 0;
function fmt(int $n): string
{
  return number_format($n, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Desa Teluk Dalam - Beranda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styless/beranda.css">
  <link rel="icon" href="../assets/Lambang_Kab._Kutai_Kertanegara.png" type="image/png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
</head>

<body>

  <header>
    <nav>
      <div class="brand">
        <img src="../assets/Lambang_Kab._Kutai_Kertanegara.png" alt="Logo Desa Teluk Dalam">
        <h1>Desa Teluk Dalam</h1>
      </div>
      <button class="nav-toggle-mobile" id="navToggleBeranda" aria-label="Buka menu" type="button">&#9776;</button>
      <ul id="navMenuBeranda">
        <li><a href="#beranda">Beranda</a></li>
        <li><a href="#profil">Profil Desa</a></li>
        <li><a href="#statistik">Statistik Desa</a></li>
        <li><a href="login.php">Masuk</a></li>
      </ul>
    </nav>
  </header>

  <section class="hero" id="beranda">
    <div class="hero-slider" id="heroSlider">
      <img src="../assets/teas.jpeg" alt="Suasana Desa Teluk Dalam 1" class="hero-slide active">
      <img src="../assets/dermaga_teluk dalam.jpg" alt="Suasana Desa Teluk Dalam 2" class="hero-slide" loading="lazy">
      <img src="../assets/sekolah_unmul_kkn_52.jpg" alt="Suasana Desa Teluk Dalam 3" class="hero-slide" loading="lazy">
      <img src="../assets/Kantor_Desa_Teluk_Dalam,_Kutai_Kartanegara.jpg" alt="Suasana Desa Teluk Dalam 4" class="hero-slide" loading="lazy">
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h2>Selamat Datang di Website Resmi Desa Teluk Dalam</h2>
      <a href="#profil" class="btn">Lihat Profil Desa</a>
    </div>
    <div class="hero-dots" id="heroDots"></div>
  </section>

  <section id="profil">
    <div class="section-title">
      <h3>Profil Desa</h3>
    </div>

    <div class="about">
      <iframe
        src="https://www.google.com/maps?q=Teluk%20Dalam,%20East%20Kalimantan,%20Indonesia&output=embed"
        style="width:100%; height:100%; min-height:320px; border:0; border-radius:8px;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        title="Peta Desa Teluk Dalam">
      </iframe>
      <div>
        <h3>Tentang Desa Teluk Dalam</h3>
        <p class="justify">Desa Teluk Dalam adalah desa yang memiliki kekayaan alam, budaya, dan sumber daya masyarakat yang unggul. Pemerintah desa berkomitmen untuk memberikan pelayanan terbaik, meningkatkan kesejahteraan warga, serta menjaga kelestarian lingkungan. Website ini hadir sebagai sarana informasi resmi bagi masyarakat desa maupun pengunjung.</p>
        
      </div>
    </div>
  </section>
  <section id="statistik">
    <div class="section-title">
      <h3>Statistik Desa</h3>
      <p>Data singkat kondisi Desa Teluk Dalam</p>
    </div>

    <div class="cards">
      <div class="card">
        <h4>Jumlah Penduduk</h4>
        <p><strong><?= fmt($total_penduduk) ?> Jiwa</strong></p>
      </div>
      <div class="card">
        <h4>Jumlah RT</h4>
        <p><strong><?= $total_rt ?> RT</strong></p>
      </div>
      <div class="card">
        <h4>Kepala Keluarga</h4>
        <p><strong><?= fmt($total_kk) ?> KK</strong></p>
      </div>
      <div class="card">
        <h4>Penduduk Tetap</h4>
        <p><strong><?= fmt($total_tetap) ?> Jiwa</strong></p>
      </div>
      <div class="card">
        <h4>Penduduk Tidak Tetap</h4>
        <p><strong><?= fmt($total_tidak_tetap) ?> Jiwa</strong></p>
      </div>
    </div>

    <div class="chart-group-title">Gambaran Umum Penduduk</div>
    <div class="chart-grid">
      <div class="chart-card">
        <h4>Penduduk Berdasarkan Jenis Kelamin</h4>
        <div class="canvas-wrap">
          <canvas id="genderChart" role="img" aria-label="Diagram lingkaran perbandingan penduduk laki-laki dan perempuan Desa Teluk Dalam"></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h4>Penduduk Berdasarkan Status Tempat Tinggal</h4>
        <div class="canvas-wrap">
          <canvas id="statusChart" role="img" aria-label="Diagram batang perbandingan penduduk tetap dan tidak tetap"></canvas>
        </div>
      </div>
    </div>

    <div class="chart-group-title">Struktur Usia Penduduk</div>
    <div class="chart-grid">
      <div class="chart-card full">
        <h4>Piramida Penduduk Menurut Usia &amp; Jenis Kelamin</h4>
        <div class="canvas-wrap tall">
          <canvas id="piramidaChart" role="img" aria-label="Piramida penduduk berdasarkan kelompok usia dan jenis kelamin"></canvas>
        </div>
      </div>
    </div>

    <div class="chart-group-title">Kondisi Sosial &amp; Ekonomi</div>
    <div class="chart-grid two-cols">
      <div class="chart-card">
        <h4>Penduduk Berdasarkan Agama</h4>
        <div class="canvas-wrap">
          <canvas id="agamaChart" role="img" aria-label="Diagram lingkaran sebaran agama penduduk"></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h4>Tingkat Pendidikan Terakhir</h4>
        <div class="canvas-wrap">
          <canvas id="pendidikanChart" role="img" aria-label="Diagram batang tingkat pendidikan terakhir penduduk, diurutkan dari jenjang terendah ke tertinggi"></canvas>
        </div>
      </div>
    </div>

    <div class="chart-grid chart-grid-pekerjaan">
      <div class="chart-card full">
        <h4>Penduduk Berdasarkan Pekerjaan</h4>
        <div class="canvas-wrap">
          <canvas id="pekerjaanChart" role="img" aria-label="Diagram batang jumlah penduduk per jenis pekerjaan"></canvas>
        </div>
      </div>
    </div>

    <div class="chart-group-title">Wilayah &amp; Analisis Kependudukan</div>
    <div class="chart-grid">
      <div class="chart-card">
        <h4>Jumlah KK per RT</h4>
        <div class="canvas-wrap">
          <canvas id="rtChart" role="img" aria-label="Diagram batang jumlah kepala keluarga per RT"></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h4>Rasio Ketergantungan Usia</h4>
        <p class="sub">Perbandingan usia produktif (15–64 th) vs tidak produktif</p>
        <div class="canvas-wrap">
          <canvas id="dependencyChart" role="img" aria-label="Diagram lingkaran rasio penduduk usia produktif dan tidak produktif"></canvas>
        </div>
        <p class="highlight">
          <strong>Rasio ketergantungan: <?= $rasio_ketergantungan ?>%</strong><br>
          Tiap 100 penduduk usia produktif menanggung ±<?= round($rasio_ketergantungan) ?> penduduk usia non-produktif.
        </p>
      </div>
    </div>
  </section>

  <footer id="kontak">
    <p><strong>Website Resmi Desa Teluk Dalam</strong></p>
    <p>Jl. Teluk Dalam | Email: info@desatelukdalam.id | Telp: (0541) 123456</p>
    <p>&copy; 2026 Desa Teluk Dalam. Seluruh hak cipta dilindungi.</p>
  </footer>

  <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
  <script src="../scriptss/beranda.js"></script>

<!-- Script untuk konfigurasi Chart.js (Tetap di PHP karena butuh data dinamis) -->
  <script>
    const genderLabels = <?= json_encode($gender_labels, JSON_UNESCAPED_UNICODE) ?>;
    const genderData = <?= json_encode($gender_data) ?>;
    const statusLabels = <?= json_encode($status_labels, JSON_UNESCAPED_UNICODE) ?>;
    const statusData = <?= json_encode($status_data) ?>;
    const pekerjaanLabels = <?= json_encode($pekerjaan_labels, JSON_UNESCAPED_UNICODE) ?>;
    const pekerjaanData = <?= json_encode($pekerjaan_data) ?>;
    const rtLabels = <?= json_encode($rt_labels, JSON_UNESCAPED_UNICODE) ?>;
    const rtData = <?= json_encode($rt_data) ?>;
    const piramidaKelompokUsia = <?= json_encode($kelompok_usia_urut, JSON_UNESCAPED_UNICODE) ?>;
    const piramidaLakiData = <?= json_encode($piramida_laki_data) ?>;
    const piramidaPerempuanData = <?= json_encode($piramida_perempuan_data) ?>;
    const agamaLabels = <?= json_encode($agama_labels, JSON_UNESCAPED_UNICODE) ?>;
    const agamaData = <?= json_encode($agama_data) ?>;
    const pendidikanLabels = <?= json_encode($pendidikan_labels, JSON_UNESCAPED_UNICODE) ?>;
    const pendidikanData = <?= json_encode($pendidikan_data) ?>;
    const dependencyLabels = ['Usia Produktif (15-64 th)', 'Usia Non-Produktif'];
    const dependencyData = [<?= $usia_produktif ?>, <?= $usia_muda + $usia_tua ?>];
    
    new Chart(document.getElementById('genderChart'), {
      type: 'doughnut',
      data: {
        labels: genderLabels,
        datasets: [{
          data: genderData,
          backgroundColor: ['#e87ba4','#2a78d6'],
          borderColor: '#ffffff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    new Chart(document.getElementById('statusChart'), {
      type: 'bar',
      data: {
        labels: statusLabels,
        datasets: [{
          data: statusData,
          backgroundColor: ['#2a78d6', '#eda100'],
          borderRadius: 4,
          maxBarThickness: 60
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#898781' }, grid: { color: '#e1e0d9' } },
          x: { ticks: { color: '#898781' }, grid: { display: false } }
        }
      }
    });

    new Chart(document.getElementById('pekerjaanChart'), {
      type: 'bar',
      data: {
        labels: pekerjaanLabels,
        datasets: [{ data: pekerjaanData, backgroundColor: '#0f4c3a', borderRadius: 4 }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, ticks: { color: '#898781' }, grid: { color: '#e1e0d9' } },
          y: { ticks: { color: '#898781' }, grid: { display: false } }
        }
      }
    });

    new Chart(document.getElementById('rtChart'), {
      type: 'bar',
      data: {
        labels: rtLabels,
        datasets: [{ data: rtData, backgroundColor: '#f4b400', borderRadius: 4, maxBarThickness: 60 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#898781', stepSize: 1 }, grid: { color: '#e1e0d9' } },
          x: { ticks: { color: '#898781' }, grid: { display: false } }
        }
      }
    });

    const piramidaMaxValue = Math.max(
      ...piramidaLakiData.map(v => Math.abs(v)),
      ...piramidaPerempuanData
    );
    const piramidaAxisLimit = Math.ceil(piramidaMaxValue / 5) * 5;
    
    new Chart(document.getElementById('piramidaChart'), {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: piramidaKelompokUsia,
        datasets: [
          { label: 'Laki-laki', data: piramidaLakiData, backgroundColor: '#2a78d6', borderRadius: 3 },
          { label: 'Perempuan', data: piramidaPerempuanData, backgroundColor: '#e87ba4', borderRadius: 3 }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${Math.abs(ctx.raw)} jiwa` } },
          datalabels: {
            color: '#4a4a4a',
            font: { size: 10, weight: '600' },
            formatter: (value) => Math.abs(value) > 0 ? Math.abs(value) : '',
            anchor: (ctx) => ctx.dataset.data[ctx.dataIndex] < 0 ? 'start' : 'end',
            align: (ctx) => ctx.dataset.data[ctx.dataIndex] < 0 ? 'start' : 'end',
            offset: 4
          }
        },
        scales: {
          x: {
            stacked: true,
            min: -piramidaAxisLimit,
            max: piramidaAxisLimit,
            ticks: { color: '#898781', callback: (val) => Math.abs(val) },
            grid: { color: '#e1e0d9' }
          },
          y: {
            stacked: true,
            reverse: true,
            ticks: { color: '#898781' },
            grid: { display: false }
          }
        }
      }
    });
    new Chart(document.getElementById('agamaChart'), {
      type: 'doughnut',
      data: {
        labels: agamaLabels,
        datasets: [{ data: agamaData, backgroundColor: ['#0f4c3a', '#2a78d6', '#e87ba4', '#f4b400', '#8e44ad', '#e74c3c'], borderColor: '#ffffff', borderWidth: 2 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
      }
    });

    new Chart(document.getElementById('pendidikanChart'), {
      type: 'bar',
      data: {
        labels: pendidikanLabels,
        datasets: [{ data: pendidikanData, backgroundColor: '#0c3c2e', borderRadius: 4 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#898781' }, grid: { color: '#e1e0d9' } },
          x: { ticks: { color: '#898781', font: { size: 10 }, maxRotation: 40, minRotation: 40 }, grid: { display: false } }
        }
      }
    });

    new Chart(document.getElementById('dependencyChart'), {
      type: 'doughnut',
      data: {
        labels: dependencyLabels,
        datasets: [{ data: dependencyData, backgroundColor: ['#0f4c3a', '#eda100'], borderColor: '#ffffff', borderWidth: 2 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
      }
    });
  </script>

</body>

</html>