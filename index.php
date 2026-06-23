<?php
// ============================================================
// index.php - Halaman Utama Ringkasan Sistem Rental PS
// ============================================================
$path_prefix = '';
session_start();
require_once 'classes/Database.php';
require_once 'classes/Kategori.php';
require_once 'classes/Konsol.php';
require_once 'classes/Customer.php';

$database = new Database();
$db       = $database->getConnection();

$kategoriObj = new Kategori($db);
$konsolObj   = new Konsol($db);
$customerObj = new Customer($db);

// Ambil metrik ringkasan
$total_kategori  = $kategoriObj->countAll();
$total_tersedia  = $konsolObj->countTersedia();
$total_customer  = $customerObj->countAll();

// Hitung total konsol & transaksi aktif
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM konsol");
    $total_konsol = (int)$stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM transaksi WHERE status_transaksi = 'Sedang Disewa'");
    $total_aktif = (int)$stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_konsol = 0;
    $total_aktif  = 0;
}

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Rental PS</title>
  <meta name="description" content="Dashboard sistem penyewaan konsol game - ringkasan data master dan transaksi aktif.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- ── SIDEBAR ── -->
<?php include 'includes/sidebar.php'; ?>

<!-- ── MAIN CONTENT ── -->
<div class="main-content">
  <!-- Top Header -->
  <div class="top-header">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Selamat datang kembali! Berikut ringkasan sistem hari ini.</p>
    </div>
    <span class="badge bg-primary px-3 py-2 fs-6">
      <i class="bi bi-calendar3 me-1"></i>
      <?= date('d M Y') ?>
    </span>
  </div>

  <!-- Page Body -->
  <div class="page-body">

    <!-- Stat Cards Row -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <div class="stat-value"><?= $total_customer ?></div>
          <div class="stat-label">Total Customer</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-controller"></i></div>
          <div class="stat-value"><?= $total_tersedia ?></div>
          <div class="stat-label">Konsol Tersedia</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card orange">
          <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
          <div class="stat-value"><?= $total_aktif ?></div>
          <div class="stat-label">Transaksi Aktif</div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card purple">
          <div class="stat-icon"><i class="bi bi-tags-fill"></i></div>
          <div class="stat-value"><?= $total_kategori ?></div>
          <div class="stat-label">Total Kategori</div>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-4">
      <div class="col-12">
        <div class="card p-4">
          <h6 class="fw-bold mb-3" style="color:var(--text-dark)"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Akses Cepat</h6>
          <div class="row g-3">
            <?php
              $links = [
                ['href'=>'pages/kategori.php',  'icon'=>'bi-tags-fill',    'label'=>'Kelola Kategori',  'color'=>'text-purple-600',  'bg'=>'#ede9fe'],
                ['href'=>'pages/konsol.php',    'icon'=>'bi-controller',   'label'=>'Kelola Konsol',    'color'=>'#2563eb', 'bg'=>'#eff6ff'],
                ['href'=>'pages/customer.php',  'icon'=>'bi-person-badge', 'label'=>'Kelola Customer',  'color'=>'#16a34a', 'bg'=>'#f0fdf4'],
                ['href'=>'pages/transaksi.php', 'icon'=>'bi-receipt',      'label'=>'Data Sewa',   'color'=>'#d97706', 'bg'=>'#fffbeb'],
              ];
              foreach ($links as $l): ?>
                <div class="col-6 col-md-3">
                  <a href="<?= $l['href'] ?>" class="d-block text-decoration-none p-3 rounded-3 text-center"
                     style="background:<?= $l['bg'] ?>; transition:all .2s ease;"
                     onmouseover="this.style.transform='translateY(-3px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <i class="bi <?= $l['icon'] ?> d-block mb-2 fs-3" style="color:<?= $l['color'] ?>"></i>
                    <span class="fw-600 fs-13" style="color:var(--text-dark); font-size:13px; font-weight:600"><?= $l['label'] ?></span>
                  </a>
                </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
