<?php
// ============================================================
// kategori.php - CRUD Master Kategori
// ============================================================
$path_prefix = '../';
session_start();
require_once '../classes/Database.php';
require_once '../classes/Kategori.php';

$database    = new Database();
$db          = $database->getConnection();
$kategoriObj = new Kategori($db);

$flash   = '';
$flash_type = '';

// ── POST Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE ──
    if ($action === 'create') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        if (empty($nama)) {
            $flash = 'Nama kategori tidak boleh kosong!';
            $flash_type = 'danger';
        } else {
            $kategoriObj->create($nama);
            $flash = 'Kategori berhasil ditambahkan!';
            $flash_type = 'success';
        }
    }

    // ── UPDATE ──
    elseif ($action === 'update') {
        $id   = (int)($_POST['id_kategori'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');
        if (empty($nama) || $id <= 0) {
            $flash = 'Data tidak valid!';
            $flash_type = 'danger';
        } else {
            $kategoriObj->update($id, $nama);
            $flash = 'Kategori berhasil diperbarui!';
            $flash_type = 'success';
        }
    }

    // ── DELETE ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id_kategori'] ?? 0);
        if ($id > 0) {
            try {
                $kategoriObj->delete($id);
                $flash = 'Kategori berhasil dihapus!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    // Redirect after POST (PRG pattern)
    $_SESSION['flash']      = $flash;
    $_SESSION['flash_type'] = $flash_type;
    header('Location: kategori.php' . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// ── GET flash dari session ──
if (!empty($_SESSION['flash'])) {
    $flash      = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

// ── Pencarian & Data ──
$search      = trim($_GET['search'] ?? '');
$hasil       = $kategoriObj->getAll($search);
$current_page = 'kategori';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kategori - Rental PS</title>
  <meta name="description" content="Halaman CRUD data master kategori konsol pada sistem penyewaan konsol game.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

  <!-- Top Header -->
  <div class="top-header">
    <div>
      <h1 class="page-title"><i class="bi bi-tags-fill me-2 text-primary"></i>Master Kategori</h1>
      <p class="page-subtitle">Kelola data kategori konsol yang tersedia</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" id="btn-tambah-kategori">
      <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
    </button>
  </div>

  <div class="page-body">

    <!-- Flash Message -->
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
        <span class="table-title"><i class="bi bi-table me-2"></i>Daftar Kategori</span>

        <!-- Search Form -->
        <form method="GET" id="form-search-kategori">
          <div class="search-bar">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                   id="input-search-kategori"
                   name="search"
                   class="form-control"
                   placeholder="Cari nama kategori..."
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-kategori">
          <thead>
            <tr>
              <th style="width:60px">#</th>
              <th>Nama Kategori</th>
              <th style="width:160px" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no = 1;
              $rows = $hasil->fetchAll();
              if (empty($rows)):
            ?>
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-tags"></i></div>
                  <p><?= !empty($search) ? "Tidak ditemukan hasil untuk \"<strong>" . htmlspecialchars($search) . "</strong>\"" : "Belum ada data kategori. Klik <strong>Tambah Kategori</strong> untuk memulai." ?></p>
                </div>
              </td>
            </tr>
            <?php else: foreach ($rows as $row): ?>
            <tr id="row-kategori-<?= $row['id_kategori'] ?>">
              <td class="text-muted fw-500"><?= $no++ ?></td>
              <td class="fw-500"><?= htmlspecialchars($row['nama_kategori']) ?></td>
              <td class="text-center">
                <div class="action-buttons">
                  <button class="btn btn-sm btn-warning action-btn"
                          id="btn-edit-kategori-<?= $row['id_kategori'] ?>"
                          onclick="bukaModeEdit(<?= $row['id_kategori'] ?>, '<?= addslashes(htmlspecialchars($row['nama_kategori'])) ?>')"
                          title="Edit">
                    <i class="bi bi-pencil-fill px-2 me-1"></i>Edit
                  </button>
                  <button class="btn btn-sm btn-danger action-btn mt-2"
                          id="btn-hapus-kategori-<?= $row['id_kategori'] ?>"
                          onclick="konfirmasiHapus(<?= $row['id_kategori'] ?>, '<?= addslashes(htmlspecialchars($row['nama_kategori'])) ?>')"
                          title="Hapus">
                    <i class="bi bi-trash3-fill "></i>Hapus
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div><!-- /table-card -->

    <?php if (!empty($search)): ?>
      <div class="mt-2">
        <a href="kategori.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i>Hapus Filter
        </a>
      </div>
    <?php endif; ?>

  </div><!-- /page-body -->
</div><!-- /main-content -->


<!-- ══════════════════════════════════════════
     MODAL: Tambah Kategori
══════════════════════════════════════════ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="labelModalTambah" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="kategori.php" id="form-tambah-kategori">
        <input type="hidden" name="action" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="labelModalTambah">
            <i class="bi bi-plus-circle me-2"></i>Tambah Kategori Baru
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="nama_kategori_tambah" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control"
                   id="nama_kategori_tambah"
                   name="nama_kategori"
                   placeholder="e.g. Handheld, Home Console"
                   required
                   autocomplete="off">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-lg me-1"></i>Batal
          </button>
          <button type="submit" class="btn btn-primary" id="btn-submit-tambah">
            <i class="bi bi-check-lg me-1"></i>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: Edit Kategori
══════════════════════════════════════════ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="labelModalEdit" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="kategori.php" id="form-edit-kategori">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id_kategori" id="edit_id_kategori">

        <div class="modal-header">
          <h5 class="modal-title" id="labelModalEdit">
            <i class="bi bi-pencil-square me-2"></i>Edit Kategori
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="nama_kategori_edit" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control"
                   id="nama_kategori_edit"
                   name="nama_kategori"
                   required
                   autocomplete="off">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-lg me-1"></i>Batal
          </button>
          <button type="submit" class="btn btn-primary" id="btn-submit-edit">
            <i class="bi bi-check-lg me-1"></i>Perbarui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: Konfirmasi Hapus
══════════════════════════════════════════ -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="labelModalHapus" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="POST" action="kategori.php" id="form-hapus-kategori">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_kategori" id="hapus_id_kategori">

        <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
          <h5 class="modal-title text-white" id="labelModalHapus">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center py-4">
          <i class="bi bi-trash3 fs-1 text-danger mb-3 d-block"></i>
          <p class="mb-1 fw-600">Hapus kategori ini?</p>
          <p class="text-muted small mb-0"><strong id="hapus_nama_kategori"></strong></p>
          <p class="text-danger small mt-2">
            <i class="bi bi-info-circle me-1"></i>Tidak dapat dihapus jika masih digunakan oleh Konsol.
          </p>
        </div>

        <div class="modal-footer justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger" id="btn-submit-hapus">
            <i class="bi bi-trash3 me-1"></i>Ya, Hapus
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Buka Modal Edit dengan data yang sudah diisi ──
function bukaModeEdit(id, nama) {
  document.getElementById('edit_id_kategori').value = id;
  document.getElementById('nama_kategori_edit').value = nama;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ── Konfirmasi Hapus ──
function konfirmasiHapus(id, nama) {
  document.getElementById('hapus_id_kategori').value = id;
  document.getElementById('hapus_nama_kategori').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

// ── Auto-submit search saat mengetik (debounce 400ms) ──
let debounceTimer;
const searchInput = document.getElementById('input-search-kategori');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      document.getElementById('form-search-kategori').submit();
    }, 400);
  });
}

// ── Auto-dismiss flash setelah 4 detik ──
setTimeout(() => {
  const alerts = document.querySelectorAll('.alert.flash-message');
  alerts.forEach(a => {
    const bsAlert = bootstrap.Alert.getOrCreateInstance(a);
    bsAlert.close();
  });
}, 4000);
</script>
</body>
</html>
