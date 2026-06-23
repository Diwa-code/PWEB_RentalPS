<?php
// ============================================================
// transaksi.php - Interface Transaksi Sewa Konsol
// ============================================================
$path_prefix = '../';
session_start();
require_once '../classes/Database.php';
require_once '../classes/Transaksi.php';

$database      = new Database();
$db            = $database->getConnection();
$transaksiObj  = new Transaksi($db);

$flash      = '';
$flash_type = '';

// ── POST Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE: Transaksi Sewa Baru ──
    if ($action === 'create') {
        $id_customer    = (int)($_POST['id_customer']    ?? 0);
        $id_konsol      = (int)($_POST['id_konsol']      ?? 0);
        $durasi_hari    = (int)($_POST['durasi_hari']    ?? 1);
        $pilihan_durasi = $durasi_hari . ' Hari';
        $harga_per_hari = (int)($_POST['harga_per_hari'] ?? 0);
        $waktu_mulai    = trim($_POST['waktu_mulai_sewa'] ?? '');

        if ($id_customer <= 0 || $id_konsol <= 0 || empty($pilihan_durasi) || empty($waktu_mulai)) {
            $flash      = 'Semua field wajib diisi!';
            $flash_type = 'danger';
        } else {
            try {
                $transaksiObj->create([
                    'id_customer'    => $id_customer,
                    'id_konsol'      => $id_konsol,
                    'pilihan_durasi' => $pilihan_durasi,
                    'harga_per_hari' => $harga_per_hari,
                    'waktu_mulai_sewa' => $waktu_mulai,
                ]);
                $flash      = 'Transaksi sewa baru berhasil dicatat!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash      = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    // ── KEMBALI: Proses Pengembalian Konsol ──
    elseif ($action === 'kembali') {
        $id_transaksi       = (int)($_POST['id_transaksi']          ?? 0);
        $id_konsol          = (int)($_POST['id_konsol']             ?? 0);
        $waktu_seharusnya   = trim($_POST['waktu_seharusnya_kembali'] ?? '');
        $waktu_aktual       = trim($_POST['waktu_kembali_aktual']    ?? '');

        if ($id_transaksi <= 0 || empty($waktu_aktual)) {
            $flash      = 'Data pengembalian tidak valid!';
            $flash_type = 'danger';
        } else {
            try {
                $total_denda = $transaksiObj->prosesKembali(
                    $id_transaksi,
                    $waktu_aktual,
                    $waktu_seharusnya,
                    $id_konsol
                );
                if ($total_denda > 0) {
                    $flash      = 'Pengembalian berhasil dicatat. Terdapat denda keterlambatan sebesar Rp ' . number_format($total_denda, 0, ',', '.');
                    $flash_type = 'warning';
                } else {
                    $flash      = 'Pengembalian berhasil dicatat. Tidak ada denda!';
                    $flash_type = 'success';
                }
            } catch (Exception $e) {
                $flash      = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    $_SESSION['flash']      = $flash;
    $_SESSION['flash_type'] = $flash_type;
    header('Location: transaksi.php' . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// ── GET flash dari session ──
if (!empty($_SESSION['flash'])) {
    $flash      = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

$search      = trim($_GET['search'] ?? '');
$transaksi   = $transaksiObj->getAll($search);
$jumlah_sedang_disewa = count(array_filter($transaksi, function ($row) {
    return $row['status_transaksi'] === 'Sedang Disewa';
}));
$total_penyewaan = count($transaksi);
$total_harga_sewa = array_sum(array_map(function ($row) {
    return (int) $row['harga_sewa'];
}, $transaksi));
$customers   = $transaksiObj->getAllCustomer();
$konsol_list = $transaksiObj->getKonsolTersedia();

$current_page = 'transaksi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Sewa - Rental PS</title>
  <meta name="description" content="Kelola transaksi sewa konsol, catat peminjaman baru, dan proses pengembalian dengan kalkulasi denda otomatis.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    /* ─── Badge status transaksi ─── */
    .badge-sedang   { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
    .badge-selesai  { background: linear-gradient(135deg,#10b981,#059669); color:#fff; }

    /* ─── Info card customer di form ─── */
    #info-customer-card {
      display:none;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      padding: 14px 16px;
      margin-top: 10px;
      animation: fadeIn .3s ease;
    }
    #info-customer-card .ktp-preview {
      width: 90px; height: 56px;
      object-fit: cover;
      border-radius: 6px;
      border: 2px solid var(--border-color);
    }

    /* ─── Harga otomatis ─── */
    #harga-preview-box {
      display:none;
      padding: 10px 14px;
      border-radius: 8px;
      background: linear-gradient(135deg,#1e3a5f,#1e40af);
      color:#fff;
      font-weight:700;
      font-size:1.1rem;
      margin-top:8px;
      animation: fadeIn .3s ease;
    }

    @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

    /* ─── Tabel aksi kembali ─── */
    .btn-kembali { background: linear-gradient(135deg,#7c3aed,#5b21b6); color:#fff; border:none; }
    .btn-kembali:hover { background: linear-gradient(135deg,#6d28d9,#4c1d95); color:#fff; }

    /* ─── Denda badge ─── */
    .badge-denda { background:linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; font-size:.75rem; }
    .badge-aman  { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; font-size:.75rem; }

    /* ─── Waktu kembali merah jika terlambat ─── */
    .terlambat { color:#dc2626; font-weight:600; }

    /* Modal info */
    .modal-info-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border-color); }
    .modal-info-row:last-child { border-bottom:none; }
    .modal-info-label { color:var(--text-muted); font-size:.85rem; }
    .modal-info-value { font-weight:600; font-size:.85rem; text-align:right; }
  </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

  <div class="top-header">
    <div>
      <h1 class="page-title"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Data Sewa</h1>
      <p class="page-subtitle">Catat transaksi sewa baru dan kelola pengembalian konsol</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSewaBaru" id="btn-sewa-baru">
      <i class="bi bi-plus-circle-fill me-1"></i> Sewa Baru
    </button>
  </div>

  <div class="page-body">

    <!-- Flash Message -->
    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= $flash_type ?> flash-message alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $flash_type === 'success' ? 'check-circle-fill' : ($flash_type === 'warning' ? 'exclamation-triangle-fill' : 'exclamation-triangle-fill') ?> me-2"></i>
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-header">
        <span class="table-title"><i class="bi bi-table me-2"></i>Riwayat Transaksi</span>
        <form method="GET" id="form-search-transaksi">
          <div class="search-bar">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                   id="input-search-transaksi"
                   name="search"
                   class="form-control"
                   placeholder="Cari customer / konsol..."
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-transaksi">
          <thead>
            <tr>
              <th style="width:50px">#</th>
              <th>Customer</th>
              <th>Konsol</th>
              <th>Durasi</th>
              <th>Harga Sewa</th>
              <th>Mulai Sewa</th>
              <th>Jatuh Tempo</th>
              <th class="text-center">Status</th>
              <th class="text-center">Denda</th>
              <th style="width:200px" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no = 1;
              if (empty($transaksi)):
            ?>
            <tr>
              <td colspan="10">
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-receipt"></i></div>
                  <p><?= !empty($search)
                       ? "Tidak ditemukan hasil untuk \"<strong>" . htmlspecialchars($search) . "</strong>\""
                       : "Belum ada transaksi. Klik <strong>Sewa Baru</strong> untuk memulai." ?></p>
                </div>
              </td>
            </tr>
            <?php else: foreach ($transaksi as $row): ?>
            <?php
              $is_aktif   = $row['status_transaksi'] === 'Sedang Disewa';
              $seharusnya = new DateTime($row['waktu_seharusnya_kembali']);
              $now        = new DateTime();
              $terlambat  = $is_aktif && $now > $seharusnya;
            ?>
            <tr id="row-transaksi-<?= $row['id_transaksi'] ?>">
              <td class="text-muted"><?= $no++ ?></td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($row['no_wa']) ?></small>
              </td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($row['nama_konsol']) ?></div>
                <small class="text-muted">Rp <?= number_format($row['harga_per_hari'], 0, ',', '.') ?>/hari</small>
              </td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($row['pilihan_durasi']) ?></span></td>
              <td class="fw-600" style="color:var(--primary)">
                Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?>
              </td>
              <td>
                <small><?= date('d M Y', strtotime($row['waktu_mulai_sewa'])) ?></small><br>
                <small class="text-muted"><?= date('H:i', strtotime($row['waktu_mulai_sewa'])) ?></small>
              </td>
              <td>
                <small class="<?= $terlambat ? 'terlambat' : '' ?>">
                  <?php if ($terlambat): ?>
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                  <?php endif; ?>
                  <?= date('d M Y', strtotime($row['waktu_seharusnya_kembali'])) ?>
                </small><br>
                <small class="text-muted"><?= date('H:i', strtotime($row['waktu_seharusnya_kembali'])) ?></small>
              </td>
              <td class="text-center">
                <span class="badge <?= $is_aktif ? 'badge-sedang' : 'badge-selesai' ?> px-2 py-1">
                  <i class="bi bi-<?= $is_aktif ? 'clock-history' : 'check-circle-fill' ?> me-1"></i>
                  <?= htmlspecialchars($row['status_transaksi']) ?>
                </span>
              </td>
              <td class="text-center">
                <?php if ($row['status_transaksi'] === 'Selesai'): ?>
                  <?php if ($row['total_denda'] > 0): ?>
                    <span class="badge badge-denda px-2 py-1">
                      Rp <?= number_format($row['total_denda'], 0, ',', '.') ?>
                    </span>
                  <?php else: ?>
                    <span class="badge badge-aman px-2 py-1"><i class="bi bi-check me-1"></i>Tepat Waktu</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <!-- Tombol Lihat Detail / Invoice -->
                <a href="../cetak_invoice.php?id=<?= $row['id_transaksi'] ?>"
                   class="btn btn-sm btn-outline-secondary me-1"
                   target="_blank"
                   id="btn-invoice-<?= $row['id_transaksi'] ?>"
                   title="Lihat Invoice">
                  <i class="bi bi-printer-fill "></i>Invoice
                </a>
                <!-- Tombol Proses Kembali (hanya jika masih aktif) -->
                <?php if ($is_aktif): ?>
                  <button class="btn btn-sm btn-kembali mt-2"
                          id="btn-kembali-<?= $row['id_transaksi'] ?>"
                          onclick="bukaModalKembali(<?= htmlspecialchars(json_encode($row)) ?>)"
                          title="Proses Pengembalian">
                    <i class="bi bi-box-arrow-in-down"></i>Kembali
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>

    <div class="table-card mt-3">
      <div class="table-header">
        <span class="table-title"><i class="bi bi-clipboard-data me-2"></i>Laporan Total Penyewaan</span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-laporan-sewa">
          <thead>
            <tr>
              <th class="text-center">Jumlah Sedang Disewa</th>
              <th class="text-center">Jumlah Total Penyewaan</th>
              <th class="text-center">Total Pendapatan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-center fw-600"><?= number_format($jumlah_sedang_disewa, 0, ',', '.') ?></td>
              <td class="text-center fw-600"><?= number_format($total_penyewaan, 0, ',', '.') ?></td>
              <td class="text-center text-price">Rp <?= number_format($total_harga_sewa, 0, ',', '.') ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($search)): ?>
      <div class="mt-2">
        <a href="transaksi.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i>Hapus Filter
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>


<!-- ══ MODAL: Sewa Baru ══ -->
<div class="modal fade" id="modalSewaBaru" tabindex="-1" aria-labelledby="labelSewaBaru" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="transaksi.php" id="form-sewa-baru">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="harga_per_hari" id="hidden_harga_per_hari" value="0">

        <div class="modal-header">
          <h5 class="modal-title" id="labelSewaBaru"><i class="bi bi-plus-circle-fill me-2"></i>Form Sewa Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Pilih Customer -->
            <div class="col-12">
              <label for="select_customer" class="form-label">
                Customer <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="select_customer" name="id_customer" required
                      onchange="tampilInfoCustomer(this)">
                <option value="">-- Pilih Customer --</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id_customer'] ?>"
                          data-nama="<?= htmlspecialchars($c['nama_lengkap']) ?>"
                          data-wa="<?= htmlspecialchars($c['no_wa']) ?>"
                          data-alamat="<?= htmlspecialchars($c['alamat']) ?>"
                          data-ktp="<?= htmlspecialchars($c['foto_ktp']) ?>">
                    <?= htmlspecialchars($c['nama_lengkap']) ?> — <?= htmlspecialchars($c['no_wa']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <!-- Info Customer Otomatis -->
              <div id="info-customer-card">
                <div class="d-flex align-items-center gap-3">
                  <img id="info-ktp-img" src="" alt="KTP Customer" class="ktp-preview">
                  <div>
                    <div class="fw-bold" id="info-nama"></div>
                    <div class="text-muted small" id="info-wa"></div>
                    <div class="text-muted small" id="info-alamat" style="max-width:350px"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pilih Konsol -->
            <div class="col-md-6">
              <label for="select_konsol" class="form-label">
                Konsol <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="select_konsol" name="id_konsol" required
                      onchange="updateHarga(this)">
                <option value="">-- Pilih Konsol Tersedia --</option>
                <?php foreach ($konsol_list as $k): ?>
                  <option value="<?= $k['id_konsol'] ?>"
                          data-harga="<?= $k['harga_per_hari'] ?>"
                          data-kategori="<?= htmlspecialchars($k['nama_kategori']) ?>">
                    <?= htmlspecialchars($k['nama_konsol']) ?>
                    (<?= htmlspecialchars($k['nama_kategori']) ?>)
                    — Rp <?= number_format($k['harga_per_hari'], 0, ',', '.') ?>/hari
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($konsol_list)): ?>
                <div class="form-text text-danger">
                  <i class="bi bi-exclamation-triangle-fill me-1"></i>
                  Tidak ada konsol yang tersedia saat ini.
                </div>
              <?php endif; ?>
            </div>

            <!-- Pilih Durasi -->
            <div class="col-md-6">
              <label for="input_durasi_hari" class="form-label">
                Durasi Sewa <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input type="number" class="form-control" id="input_durasi_hari" name="durasi_hari" min="1" value="1" required
                       onchange="hitungHargaTotal()" oninput="hitungHargaTotal()">
                <span class="input-group-text">Hari</span>
              </div>
            </div>

            <!-- Preview Harga Total -->
            <div class="col-12">
              <div id="harga-preview-box">
                <i class="bi bi-cash-coin me-2"></i>
                Total Harga Sewa: <span id="harga-total-text">Rp 0</span>
              </div>
            </div>

            <!-- Waktu Mulai Sewa -->
            <div class="col-12">
              <label for="waktu_mulai_sewa" class="form-label">
                Waktu Mulai Sewa <span class="text-danger">*</span>
              </label>
              <input type="datetime-local" class="form-control" id="waktu_mulai_sewa"
                     name="waktu_mulai_sewa" required>
              <div class="form-text text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Waktu jatuh tempo akan dihitung otomatis berdasarkan durasi yang dipilih.
              </div>
            </div>

            <!-- Info Denda -->
            <div class="col-12">
              <div class="alert alert-info py-2 mb-0" style="font-size:.85rem;">
                <i class="bi bi-clock-history me-2"></i>
                <strong>Kebijakan Denda:</strong> Toleransi keterlambatan <strong>3 jam</strong>.
                Jika melebihi toleransi, denda <strong>Rp 50.000/hari</strong> (pembulatan ke atas).
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-lg me-1"></i>Batal
          </button>
          <button type="submit" class="btn btn-primary" id="btn-submit-sewa">
            <i class="bi bi-check-lg me-1"></i>Simpan Transaksi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══ MODAL: Proses Pengembalian ══ -->
<div class="modal fade" id="modalKembali" tabindex="-1" aria-labelledby="labelKembali" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form method="POST" action="transaksi.php" id="form-kembali">
        <input type="hidden" name="action" value="kembali">
        <input type="hidden" name="id_transaksi" id="kembali_id_transaksi">
        <input type="hidden" name="id_konsol" id="kembali_id_konsol">
        <input type="hidden" name="waktu_seharusnya_kembali" id="kembali_waktu_seharusnya">

        <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);">
          <h5 class="modal-title text-white" id="labelKembali">
            <i class="bi bi-box-arrow-in-down me-2"></i>Proses Pengembalian
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- Info Transaksi -->
          <div class="mb-3 p-3 rounded-3" style="background:var(--bg-page); border:1px solid var(--border-color);">
            <div class="modal-info-row">
              <span class="modal-info-label"><i class="bi bi-person me-1"></i>Customer</span>
              <span class="modal-info-value" id="kembali_nama_customer">—</span>
            </div>
            <div class="modal-info-row">
              <span class="modal-info-label"><i class="bi bi-controller me-1"></i>Konsol</span>
              <span class="modal-info-value" id="kembali_nama_konsol">—</span>
            </div>
            <div class="modal-info-row">
              <span class="modal-info-label"><i class="bi bi-cash me-1"></i>Harga Sewa</span>
              <span class="modal-info-value" id="kembali_harga_sewa">—</span>
            </div>
            <div class="modal-info-row">
              <span class="modal-info-label"><i class="bi bi-calendar-check me-1"></i>Jatuh Tempo</span>
              <span class="modal-info-value" id="kembali_jatuh_tempo" style="color:#d97706;">—</span>
            </div>
          </div>

          <!-- Waktu Kembali Aktual -->
          <div class="mb-3">
            <label for="waktu_kembali_aktual" class="form-label">
              Waktu Pengembalian Aktual <span class="text-danger">*</span>
            </label>
            <input type="datetime-local" class="form-control" id="waktu_kembali_aktual"
                   name="waktu_kembali_aktual" required
                   onchange="kalkulasiDendaLive(this.value)">
          </div>

          <!-- Preview Denda Live -->
          <div id="denda-preview-box" class="p-3 rounded-3" style="display:none; border:1px solid var(--border-color);">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small"><i class="bi bi-calculator me-1"></i>Estimasi Denda:</span>
              <span id="denda-preview-value" class="fw-bold fs-5" style="color:#dc2626">Rp 0</span>
            </div>
            <div class="mt-1">
              <small class="text-muted" id="denda-preview-keterangan"></small>
            </div>
          </div>

          <!-- Kebijakan -->
          <div class="alert alert-warning py-2 mt-3 mb-0" style="font-size:.82rem;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Toleransi <strong>3 jam</strong> dari jatuh tempo. Lewat dari 3 jam: denda <strong>Rp 50.000/hari</strong>.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-lg me-1"></i>Batal
          </button>
          <button type="submit" class="btn btn-success" id="btn-submit-kembali">
            <i class="bi bi-check-lg me-1"></i>Konfirmasi Kembali
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── Set waktu mulai sewa ke "sekarang" saat modal dibuka ───
document.getElementById('modalSewaBaru').addEventListener('show.bs.modal', () => {
  const now = new Date();
  const pad = n => String(n).padStart(2, '0');
  const local = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
  document.getElementById('waktu_mulai_sewa').value = local;
  // Reset info customer & durasi
  document.getElementById('info-customer-card').style.display = 'none';
  document.getElementById('harga-preview-box').style.display  = 'none';
  document.getElementById('input_durasi_hari').value = 1;
});

// ─── Tampilkan info customer otomatis saat dipilih ───
function tampilInfoCustomer(sel) {
  const opt = sel.options[sel.selectedIndex];
  const card = document.getElementById('info-customer-card');
  if (!opt.value) { card.style.display = 'none'; return; }

  document.getElementById('info-nama').textContent   = opt.dataset.nama;
  document.getElementById('info-wa').textContent     = opt.dataset.wa;
  document.getElementById('info-alamat').textContent = opt.dataset.alamat;

  const ktpSrc = opt.dataset.ktp ? '../uploads/' + opt.dataset.ktp : '';
  const img = document.getElementById('info-ktp-img');
  if (ktpSrc) {
    img.src = ktpSrc;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
  card.style.display = 'block';
}

// ─── Update hidden harga_per_hari dan preview harga total saat konsol dipilih ───
function updateHarga(sel) {
  const opt = sel.options[sel.selectedIndex];
  const harga = opt.dataset.harga ? parseInt(opt.dataset.harga) : 0;
  document.getElementById('hidden_harga_per_hari').value = harga;
  hitungHargaTotal();
}

// ─── Hitung & tampilkan harga total ───
function hitungHargaTotal() {
  const harga = parseInt(document.getElementById('hidden_harga_per_hari').value) || 0;
  const hari  = parseInt(document.getElementById('input_durasi_hari').value) || 1;
  const box   = document.getElementById('harga-preview-box');

  if (!harga || hari <= 0) { box.style.display = 'none'; return; }

  const total = harga * hari;
  document.getElementById('harga-total-text').textContent = 'Rp ' + total.toLocaleString('id-ID');
  box.style.display = 'block';
}

// ─── Buka modal proses kembali ───
function bukaModalKembali(data) {
  document.getElementById('kembali_id_transaksi').value  = data.id_transaksi;
  document.getElementById('kembali_id_konsol').value     = data.id_konsol;
  document.getElementById('kembali_waktu_seharusnya').value = data.waktu_seharusnya_kembali;
  document.getElementById('kembali_nama_customer').textContent = data.nama_lengkap;
  document.getElementById('kembali_nama_konsol').textContent   = data.nama_konsol;
  document.getElementById('kembali_harga_sewa').textContent    = 'Rp ' + parseInt(data.harga_sewa).toLocaleString('id-ID');

  // Format waktu jatuh tempo
  const jt = new Date(data.waktu_seharusnya_kembali.replace(' ', 'T'));
  document.getElementById('kembali_jatuh_tempo').textContent = jt.toLocaleString('id-ID', {
    day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'
  });

  // Set waktu kembali aktual = sekarang
  const now = new Date();
  const pad = n => String(n).padStart(2, '0');
  const local = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
  document.getElementById('waktu_kembali_aktual').value = local;
  document.getElementById('denda-preview-box').style.display = 'none';
  kalkulasiDendaLive(local);

  new bootstrap.Modal(document.getElementById('modalKembali')).show();
}

// ─── Kalkulasi denda live (JavaScript mirror dari logika PHP) ───
function kalkulasiDendaLive(waktuAktualStr) {
  const box        = document.getElementById('denda-preview-box');
  const valEl      = document.getElementById('denda-preview-value');
  const ketEl      = document.getElementById('denda-preview-keterangan');
  const seharusnya = document.getElementById('kembali_waktu_seharusnya').value;

  if (!waktuAktualStr || !seharusnya) { box.style.display = 'none'; return; }

  const seharusnyaDt = new Date(seharusnya.replace(' ', 'T'));
  const aktualDt     = new Date(waktuAktualStr);

  box.style.display = 'block';

  if (aktualDt <= seharusnyaDt) {
    valEl.textContent  = 'Rp 0';
    valEl.style.color  = '#16a34a';
    ketEl.textContent  = '✅ Tepat waktu / lebih awal. Tidak ada denda.';
    return;
  }

  const diffMs        = aktualDt - seharusnyaDt;
  const totalJam      = diffMs / (1000 * 60 * 60);

  if (totalJam <= 3) {
    valEl.textContent  = 'Rp 0';
    valEl.style.color  = '#f59e0b';
    ketEl.textContent  = `⏳ Terlambat ${totalJam.toFixed(1)} jam. Masih dalam toleransi 3 jam. Tidak ada denda.`;
    return;
  }

  const hariTerlambat = Math.ceil(totalJam / 24);
  const denda         = hariTerlambat * 50000;
  valEl.textContent   = 'Rp ' + denda.toLocaleString('id-ID');
  valEl.style.color   = '#dc2626';
  ketEl.textContent   = `⚠️ Terlambat ${Math.floor(totalJam)} jam → ${hariTerlambat} hari × Rp 50.000 = Rp ${denda.toLocaleString('id-ID')}`;
}

// ─── Debounce search ───
let debounceTimer;
const searchInput = document.getElementById('input-search-transaksi');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('form-search-transaksi').submit(), 400);
  });
}

// ─── Auto-dismiss flash ───
setTimeout(() => {
  document.querySelectorAll('.alert.flash-message').forEach(a => {
    bootstrap.Alert.getOrCreateInstance(a).close();
  });
}, 5000);
</script>
</body>
</html>
