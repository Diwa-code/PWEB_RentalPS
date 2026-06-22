<?php
// ============================================================
// konsol.php - CRUD Master Konsol
// ============================================================
$path_prefix = '../';
session_start();
require_once '../classes/Database.php';
require_once '../classes/Konsol.php';

$database  = new Database();
$db        = $database->getConnection();
$konsolObj = new Konsol($db);

$flash      = '';
$flash_type = '';

// ── POST Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE ──
    if ($action === 'create') {
        $data = [
            'id_kategori'   => (int)($_POST['id_kategori']    ?? 0),
            'nama_konsol'   => trim($_POST['nama_konsol']      ?? ''),
            'harga_per_hari'=> (int)($_POST['harga_per_hari']  ?? 0),
            'status'        => $_POST['status']                ?? 'Tersedia',
        ];
        $valid_status = ['Tersedia', 'Disewa', 'Maintenance'];

        if (empty($data['nama_konsol']) || $data['id_kategori'] <= 0 || $data['harga_per_hari'] <= 0) {
            $flash = 'Semua field wajib diisi dengan benar!';
            $flash_type = 'danger';
        } elseif (!in_array($data['status'], $valid_status)) {
            $flash = 'Status tidak valid!';
            $flash_type = 'danger';
        } else {
            $konsolObj->create($data);
            $flash = 'Konsol berhasil ditambahkan!';
            $flash_type = 'success';
        }
    }

    // ── UPDATE ──
    elseif ($action === 'update') {
        $id   = (int)($_POST['id_konsol'] ?? 0);
        $data = [
            'id_kategori'   => (int)($_POST['id_kategori']    ?? 0),
            'nama_konsol'   => trim($_POST['nama_konsol']      ?? ''),
            'harga_per_hari'=> (int)($_POST['harga_per_hari']  ?? 0),
            'status'        => $_POST['status']                ?? 'Tersedia',
        ];
        if (empty($data['nama_konsol']) || $data['id_kategori'] <= 0 || $data['harga_per_hari'] <= 0 || $id <= 0) {
            $flash = 'Data tidak valid!';
            $flash_type = 'danger';
        } else {
            $konsolObj->update($id, $data);
            $flash = 'Data konsol berhasil diperbarui!';
            $flash_type = 'success';
        }
    }

    // ── DELETE ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id_konsol'] ?? 0);
        if ($id > 0) {
            try {
                $konsolObj->delete($id);
                $flash = 'Konsol berhasil dihapus!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    $_SESSION['flash']      = $flash;
    $_SESSION['flash_type'] = $flash_type;
    header('Location: konsol.php' . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// ── GET flash dari session ──
if (!empty($_SESSION['flash'])) {
    $flash      = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

// ── Data ──
$search       = trim($_GET['search'] ?? '');
$hasil        = $konsolObj->getAll($search);
$list_kategori = $konsolObj->getAllKategori();
$current_page = 'konsol';

// Helper status badge
function statusBadge($status) {
    $map = [
        'Tersedia'    => 'badge-tersedia',
        'Disewa'      => 'badge-disewa',
        'Maintenance' => 'badge-maintenance',
    ];
    $cls = $map[$status] ?? 'badge-secondary';
    return "<span class=\"badge-status {$cls}\">{$status}</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konsol - Rental PS</title>
  <meta name="description" content="Kelola data unit konsol yang tersedia untuk disewakan beserta harga dan statusnya.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

  <div class="top-header">
    <div>
      <h1 class="page-title"><i class="bi bi-controller me-2 text-primary"></i>Master Konsol</h1>
      <p class="page-subtitle">Kelola unit konsol, harga sewa, dan status ketersediaan</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" id="btn-tambah-konsol">
      <i class="bi bi-plus-lg me-1"></i> Tambah Konsol
    </button>
  </div>

  <div class="page-body">

    <!-- Flash -->
    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= $flash_type ?> flash-message alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $flash_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-header">
        <span class="table-title"><i class="bi bi-table me-2"></i>Daftar Konsol</span>
        <form method="GET" id="form-search-konsol">
          <div class="search-bar">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                   id="input-search-konsol"
                   name="search"
                   class="form-control"
                   placeholder="Cari nama konsol / kategori..."
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-konsol">
          <thead>
            <tr>
              <th style="width:50px">#</th>
              <th>Nama Konsol</th>
              <th>Kategori</th>
              <th>Harga / Hari</th>
              <th class="text-center">Status</th>
              <th style="width:180px" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no   = 1;
              $rows = $hasil->fetchAll();
              if (empty($rows)):
            ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-controller"></i></div>
                  <p><?= !empty($search)
                       ? "Tidak ditemukan hasil untuk \"<strong>" . htmlspecialchars($search) . "</strong>\""
                       : "Belum ada data konsol. Klik <strong>Tambah Konsol</strong> untuk memulai." ?></p>
                </div>
              </td>
            </tr>
            <?php else: foreach ($rows as $row): ?>
            <tr id="row-konsol-<?= $row['id_konsol'] ?>">
              <td class="text-muted"><?= $no++ ?></td>
              <td class="fw-600"><?= htmlspecialchars($row['nama_konsol']) ?></td>
              <td>
                <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;">
                  <?= htmlspecialchars($row['nama_kategori'] ?? '-') ?>
                </span>
              </td>
              <td class="text-price">Rp <?= number_format($row['harga_per_hari'], 0, ',', '.') ?></td>
              <td class="text-center"><?= statusBadge($row['status']) ?></td>
              <td class="text-center">
                <div class="action-buttons">
                  <button class="btn btn-sm btn-warning action-btn"
                          id="btn-edit-konsol-<?= $row['id_konsol'] ?>"
                          onclick="bukaModeEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                          title="Edit">
                    <i class="bi bi-pencil-fill px-2"></i>Edit
                  </button>
                  <button class="btn btn-sm btn-danger action-btn"
                          id="btn-hapus-konsol-<?= $row['id_konsol'] ?>"
                          onclick="konfirmasiHapus(<?= $row['id_konsol'] ?>, '<?= addslashes(htmlspecialchars($row['nama_konsol'])) ?>')"
                          title="Hapus">
                    <i class="bi bi-trash3-fill mt-2"></i>Hapus
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($search)): ?>
      <div class="mt-2">
        <a href="konsol.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i>Hapus Filter
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>


<!-- ══ MODAL: Tambah Konsol ══ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="labelTambahKonsol" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="konsol.php" id="form-tambah-konsol">
        <input type="hidden" name="action" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="labelTambahKonsol"><i class="bi bi-plus-circle me-2"></i>Tambah Konsol Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="tambah_id_kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
            <select class="form-select" id="tambah_id_kategori" name="id_kategori" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($list_kategori as $kat): ?>
                <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="tambah_nama_konsol" class="form-label">Nama Konsol <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="tambah_nama_konsol" name="nama_konsol"
                   placeholder="e.g. PlayStation 5, Nintendo Switch" required autocomplete="off">
          </div>

          <div class="mb-3">
            <label for="tambah_harga" class="form-label">Harga / Hari (Rp) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="tambah_harga" name="harga_per_hari"
                     placeholder="50000" min="1000" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="tambah_status" class="form-label">Status</label>
            <select class="form-select" id="tambah_status" name="status">
              <option value="Tersedia">Tersedia</option>
              <option value="Disewa">Disewa</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-tambah-konsol"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══ MODAL: Edit Konsol ══ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="labelEditKonsol" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="konsol.php" id="form-edit-konsol">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id_konsol" id="edit_id_konsol">

        <div class="modal-header">
          <h5 class="modal-title" id="labelEditKonsol"><i class="bi bi-pencil-square me-2"></i>Edit Data Konsol</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_id_kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_id_kategori" name="id_kategori" required>
              <?php foreach ($list_kategori as $kat): ?>
                <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="edit_nama_konsol" class="form-label">Nama Konsol <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_nama_konsol" name="nama_konsol" required autocomplete="off">
          </div>

          <div class="mb-3">
            <label for="edit_harga" class="form-label">Harga / Hari (Rp) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="edit_harga" name="harga_per_hari" min="1000" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_status" class="form-label">Status</label>
            <select class="form-select" id="edit_status" name="status">
              <option value="Tersedia">Tersedia</option>
              <option value="Disewa">Disewa</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-edit-konsol"><i class="bi bi-check-lg me-1"></i>Perbarui</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══ MODAL: Konfirmasi Hapus ══ -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="labelHapusKonsol" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="POST" action="konsol.php" id="form-hapus-konsol">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_konsol" id="hapus_id_konsol">

        <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
          <h5 class="modal-title text-white" id="labelHapusKonsol"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center py-4">
          <i class="bi bi-trash3 fs-1 text-danger mb-3 d-block"></i>
          <p class="mb-1 fw-bold">Hapus konsol ini?</p>
          <p class="text-muted small mb-0"><strong id="hapus_nama_konsol"></strong></p>
        </div>

        <div class="modal-footer justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger" id="btn-submit-hapus-konsol"><i class="bi bi-trash3 me-1"></i>Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModeEdit(data) {
  document.getElementById('edit_id_konsol').value    = data.id_konsol;
  document.getElementById('edit_id_kategori').value  = data.id_kategori;
  document.getElementById('edit_nama_konsol').value  = data.nama_konsol;
  document.getElementById('edit_harga').value        = data.harga_per_hari;
  document.getElementById('edit_status').value       = data.status;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function konfirmasiHapus(id, nama) {
  document.getElementById('hapus_id_konsol').value    = id;
  document.getElementById('hapus_nama_konsol').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

// Debounce search
let debounceTimer;
const searchInput = document.getElementById('input-search-konsol');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('form-search-konsol').submit(), 400);
  });
}

// Auto-dismiss flash
setTimeout(() => {
  document.querySelectorAll('.alert.flash-message').forEach(a => {
    bootstrap.Alert.getOrCreateInstance(a).close();
  });
}, 4000);
</script>
</body>
</html>
