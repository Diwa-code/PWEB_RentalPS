<?php
// ============================================================
// cetak_invoice.php - Halaman Cetak Struk / Invoice Transaksi
// ============================================================
require_once 'classes/Database.php';
require_once 'classes/Transaksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID transaksi tidak valid.");
}

$database     = new Database();
$db           = $database->getConnection();
$transaksiObj = new Transaksi($db);
$t            = $transaksiObj->getById($id);

if (!$t) {
    die("Transaksi tidak ditemukan.");
}

// Format waktu helper
function fmtWaktu($dt) {
    if (!$dt) return '—';
    return date('d M Y, H:i', strtotime($dt));
}
function fmtRp($angka) {
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

$is_selesai  = $t['status_transaksi'] === 'Selesai';
$total_bayar = (int)$t['harga_sewa'] + (int)$t['total_denda'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #<?= str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT) ?> - Rental PS</title>
  <meta name="description" content="Struk bukti transaksi sewa konsol Rental PS.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root {
      --primary: #2563eb;
      --accent:  #7c3aed;
      --green:   #16a34a;
      --red:     #dc2626;
      --orange:  #d97706;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f1f5f9;
      color: #1e293b;
      min-height: 100vh;
    }

    /* ─── Screen only: tombol cetak ─── */
    .print-toolbar {
      background: linear-gradient(135deg, #1e293b, #0f172a);
      padding: 14px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .print-toolbar h6 { color:#94a3b8; margin:0; font-size:.9rem; }
    .print-toolbar .btn-back { color:#94a3b8; text-decoration:none; }
    .print-toolbar .btn-back:hover { color:#fff; }

    /* ─── Invoice wrapper ─── */
    .invoice-wrapper {
      max-width: 720px;
      margin: 32px auto;
      padding: 0 16px 40px;
    }

    .invoice-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 32px rgba(0,0,0,.12);
      overflow: hidden;
    }

    /* Header invoice */
    .invoice-header {
      background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #7c3aed 100%);
      padding: 32px 36px;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    .invoice-brand h2 { font-size: 1.6rem; font-weight: 800; margin-bottom: 4px; }
    .invoice-brand p  { font-size: .85rem; opacity: .8; }

    .invoice-meta { text-align: right; }
    .invoice-meta .invoice-no { font-size: 1.1rem; font-weight: 700; letter-spacing: .5px; }
    .invoice-meta .invoice-date { font-size: .82rem; opacity:.8; margin-top:4px; }
    .invoice-meta .badge-status {
      display: inline-block;
      margin-top: 8px;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: .78rem;
      font-weight: 700;
    }
    .badge-aktif   { background: rgba(245,158,11,.25); color: #fbbf24; }
    .badge-selesai { background: rgba(16,185,129,.25);  color: #6ee7b7; }

    /* Body invoice */
    .invoice-body { padding: 28px 36px; }

    .section-title {
      font-size: .7rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #94a3b8;
      font-weight: 700;
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e2e8f0;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px 24px;
      margin-bottom: 24px;
    }
    .info-item {}
    .info-label { font-size: .75rem; color: #94a3b8; margin-bottom: 2px; }
    .info-value { font-size: .92rem; font-weight: 600; color: #1e293b; word-break: break-word; }

    /* KTP thumbnail */
    .ktp-thumb-invoice {
      width: 100px; height: 62px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #e2e8f0;
    }

    /* Tabel rincian biaya */
    .cost-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    .cost-table td { padding: 8px 0; font-size: .88rem; }
    .cost-table .cost-label { color: #64748b; }
    .cost-table .cost-value { text-align: right; font-weight: 600; }
    .cost-table .divider td { border-top: 1px dashed #e2e8f0; padding-top: 10px; }
    .cost-table .total-row td { font-size: 1.1rem; font-weight: 700; color: var(--primary); }
    .cost-table .denda-row td { color: var(--red); }
    .cost-table .denda-note { font-size: .75rem; color: #94a3b8; display: block; }

    /* Footer invoice */
    .invoice-footer {
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      padding: 20px 36px;
      text-align: center;
    }
    .invoice-footer p { font-size: .8rem; color: #94a3b8; line-height: 1.8; }
    .invoice-footer .brand { font-weight: 700; color: var(--primary); }

    /* Divider */
    .section-divider { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }

    /* Denda box */
    .denda-box {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 16px;
    }
    .denda-box .denda-amount { font-size: 1.5rem; font-weight: 800; color: var(--red); }
    .denda-box small { color: #b91c1c; font-size: .8rem; }

    .no-denda-box {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 10px;
      padding: 10px 16px;
      margin-bottom: 16px;
      color: var(--green);
      font-weight: 600;
      font-size: .88rem;
    }

    /* ─── Print styles ─── */
    @media print {
      body { background: #fff !important; }
      .print-toolbar { display: none !important; }
      .invoice-wrapper { margin: 0; padding: 0; max-width: 100%; }
      .invoice-card { box-shadow: none; border-radius: 0; }
      .invoice-header { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
  </style>
</head>
<body>

<!-- Toolbar (screen only) -->
<div class="print-toolbar">
  <a href="transaksi.php" class="btn-back">
    <i class="bi bi-arrow-left me-1"></i> Kembali ke Transaksi
  </a>
  <h6><i class="bi bi-file-earmark-text me-1"></i>
    Invoice #<?= str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT) ?>
  </h6>
  <button onclick="window.print()" class="btn btn-primary btn-sm" id="btn-cetak-invoice">
    <i class="bi bi-printer-fill me-1"></i> Cetak Invoice
  </button>
</div>

<!-- Invoice -->
<div class="invoice-wrapper">
  <div class="invoice-card">

    <!-- Header -->
    <div class="invoice-header">
      <div class="invoice-brand">
        <h2>🎮 Rental PS</h2>
        <p>Sistem Penyewaan Konsol Game<br>Invoice / Struk Bukti Transaksi</p>
      </div>
      <div class="invoice-meta">
        <div class="invoice-no">
          #<?= str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT) ?>
        </div>
        <div class="invoice-date">
          Dicetak: <?= date('d M Y, H:i') ?>
        </div>
        <span class="badge-status <?= $is_selesai ? 'badge-selesai' : 'badge-aktif' ?>">
          <i class="bi bi-<?= $is_selesai ? 'check-circle-fill' : 'clock-history' ?> me-1"></i>
          <?= htmlspecialchars($t['status_transaksi']) ?>
        </span>
      </div>
    </div>

    <!-- Body -->
    <div class="invoice-body">

      <!-- Data Customer -->
      <div class="section-title"><i class="bi bi-person-badge me-1"></i>Data Pelanggan</div>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Nama Lengkap</div>
          <div class="info-value"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">No. WhatsApp</div>
          <div class="info-value"><?= htmlspecialchars($t['no_wa']) ?></div>
        </div>
        <div class="info-item" style="grid-column:1/2">
          <div class="info-label">Alamat</div>
          <div class="info-value"><?= nl2br(htmlspecialchars($t['alamat'])) ?></div>
        </div>
        <div class="info-item" style="text-align:right">
          <div class="info-label">Foto KTP Jaminan</div>
          <?php
            $ktpPath = 'uploads/' . $t['foto_ktp'];
            if ($t['foto_ktp'] && file_exists($ktpPath)):
          ?>
            <img src="<?= htmlspecialchars($ktpPath) ?>"
                 alt="KTP <?= htmlspecialchars($t['nama_lengkap']) ?>"
                 class="ktp-thumb-invoice">
          <?php else: ?>
            <span style="font-size:.8rem; color:#94a3b8;">File tidak ada</span>
          <?php endif; ?>
        </div>
      </div>

      <hr class="section-divider">

      <!-- Detail Transaksi -->
      <div class="section-title"><i class="bi bi-controller me-1"></i>Detail Penyewaan</div>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Konsol Disewa</div>
          <div class="info-value"><?= htmlspecialchars($t['nama_konsol']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Durasi Sewa</div>
          <div class="info-value"><?= htmlspecialchars($t['pilihan_durasi']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Waktu Mulai Sewa</div>
          <div class="info-value"><?= fmtWaktu($t['waktu_mulai_sewa']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Jatuh Tempo</div>
          <div class="info-value" style="color:var(--orange)">
            <?= fmtWaktu($t['waktu_seharusnya_kembali']) ?>
          </div>
        </div>
        <?php if ($is_selesai): ?>
        <div class="info-item">
          <div class="info-label">Waktu Kembali Aktual</div>
          <div class="info-value" style="color:<?= (int)$t['total_denda'] > 0 ? 'var(--red)' : 'var(--green)' ?>">
            <?= fmtWaktu($t['waktu_kembali_aktual']) ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <hr class="section-divider">

      <!-- Rincian Biaya -->
      <div class="section-title"><i class="bi bi-cash-coin me-1"></i>Rincian Biaya</div>

      <?php if ($is_selesai && (int)$t['total_denda'] > 0): ?>
        <div class="denda-box">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div style="font-weight:700; color:#b91c1c; font-size:.85rem; margin-bottom:4px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Terdapat Denda Keterlambatan
              </div>
              <small>Melewati toleransi 3 jam dari jatuh tempo</small>
            </div>
            <div class="denda-amount"><?= fmtRp($t['total_denda']) ?></div>
          </div>
        </div>
      <?php elseif ($is_selesai): ?>
        <div class="no-denda-box">
          <i class="bi bi-check-circle-fill me-2"></i>Pengembalian tepat waktu — Tidak ada denda
        </div>
      <?php endif; ?>

      <table class="cost-table">
        <tr>
          <td class="cost-label">Harga Sewa (<?= htmlspecialchars($t['pilihan_durasi']) ?>)</td>
          <td class="cost-value"><?= fmtRp($t['harga_sewa']) ?></td>
        </tr>
        <tr>
          <td class="cost-label">
            Denda Keterlambatan
            <?php if ($is_selesai && (int)$t['total_denda'] > 0): ?>
              <span class="denda-note">Toleransi 3 jam, denda Rp 50.000/hari (bulatkan ke atas)</span>
            <?php endif; ?>
          </td>
          <td class="cost-value <?= (int)$t['total_denda'] > 0 ? 'denda-row' : '' ?>">
            <?= fmtRp($t['total_denda']) ?>
          </td>
        </tr>
        <tr class="divider">
          <td colspan="2"></td>
        </tr>
        <tr class="total-row">
          <td class="cost-label">TOTAL YANG HARUS DIBAYAR</td>
          <td class="cost-value"><?= fmtRp($total_bayar) ?></td>
        </tr>
      </table>

      <?php if (!$is_selesai): ?>
        <div class="alert alert-warning py-2 mt-2" style="font-size:.82rem;">
          <i class="bi bi-clock-history me-1"></i>
          Transaksi masih berjalan. Denda akan dihitung saat pengembalian jika melebihi toleransi 3 jam.
        </div>
      <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="invoice-footer">
      <p>
        Terima kasih telah menggunakan layanan <span class="brand">🎮 Rental PS</span>.<br>
        Dokumen ini dicetak otomatis oleh sistem. Harap simpan sebagai bukti transaksi.<br>
        <small>Invoice #<?= str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT) ?> · Dicetak: <?= date('d M Y H:i:s') ?></small>
      </p>
    </div>

  </div>
</div>

</body>
</html>
