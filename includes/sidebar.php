<?php
// includes/sidebar.php
// Variabel $current_page harus didefinisikan di halaman pemanggil
// e.g.: $current_page = 'kategori';
if (!isset($current_page)) $current_page = '';
$prefix = isset($path_prefix) ? $path_prefix : '';
?>
<nav class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-logo">🎮</div>
    <h5>Planet Station</h5>
    <small>Sistem Penyewaan Konsol</small>
  </div>

  <!-- Navigation -->
  <div class="sidebar-nav">

    <div class="nav-section-label">Menu Utama</div>

    <a href="<?= $prefix ?>index.php"
       class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
      Dashboard
    </a>

    <div class="nav-section-label">Data Master</div>

    <a href="<?= $prefix ?>pages/kategori.php"
       class="nav-link <?= $current_page === 'kategori' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="bi bi-tags-fill"></i></span>
      Kategori
    </a>

    <a href="<?= $prefix ?>pages/konsol.php"
       class="nav-link <?= $current_page === 'konsol' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="bi bi-controller"></i></span>
      Konsol
    </a>

    <a href="<?= $prefix ?>pages/customer.php"
       class="nav-link <?= $current_page === 'customer' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="bi bi-person-badge"></i></span>
      Customer
    </a>

    <div class="nav-section-label">Transaksi</div>

    <a href="<?= $prefix ?>pages/transaksi.php"
       class="nav-link <?= $current_page === 'transaksi' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="bi bi-receipt-cutoff"></i></span>
      Data Sewa
    </a>

  </div>

  <!-- Footer -->
  <div class="sidebar-footer">
    <i class="bi bi-shield-check me-1"></i> Planet Station &copy; <?= date('Y') ?>
  </div>

</nav>
