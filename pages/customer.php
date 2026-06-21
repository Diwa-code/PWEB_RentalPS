<?php
// ============================================================
// customer.php - CRUD Master Customer
// ============================================================
$path_prefix = '../';
session_start();
require_once '../classes/Database.php';
require_once '../classes/Customer.php';

$database    = new Database();
$db          = $database->getConnection();
$customerObj = new Customer($db);

$flash      = '';
$flash_type = '';

// ── POST Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE ──
    if ($action === 'create') {
        $data = [
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'no_wa'        => trim($_POST['no_wa']        ?? ''),
            'alamat'       => trim($_POST['alamat']       ?? ''),
        ];
        $file = $_FILES['foto_ktp'] ?? null;

        if (empty($data['nama_lengkap']) || empty($data['no_wa']) || empty($data['alamat'])) {
            $flash = 'Semua field wajib diisi!';
            $flash_type = 'danger';
        } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
            $flash = 'Foto KTP wajib diupload!';
            $flash_type = 'danger';
        } else {
            try {
                $customerObj->create($data, $file);
                $flash = 'Data customer berhasil ditambahkan!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    // ── UPDATE ──
    elseif ($action === 'update') {
        $id   = (int)($_POST['id_customer'] ?? 0);
        $data = [
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'no_wa'        => trim($_POST['no_wa']        ?? ''),
            'alamat'       => trim($_POST['alamat']       ?? ''),
        ];
        $file = $_FILES['foto_ktp'] ?? null;

        if (empty($data['nama_lengkap']) || empty($data['no_wa']) || empty($data['alamat']) || $id <= 0) {
            $flash = 'Data tidak valid!';
            $flash_type = 'danger';
        } else {
            try {
                $customerObj->update($id, $data, $file);
                $flash = 'Data customer berhasil diperbarui!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    // ── DELETE ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id_customer'] ?? 0);
        if ($id > 0) {
            try {
                $customerObj->delete($id);
                $flash = 'Data customer berhasil dihapus!';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = $e->getMessage();
                $flash_type = 'danger';
            }
        }
    }

    $_SESSION['flash']      = $flash;
    $_SESSION['flash_type'] = $flash_type;
    header('Location: customer.php' . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// ── GET flash dari session ──
if (!empty($_SESSION['flash'])) {
    $flash      = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

$search       = trim($_GET['search'] ?? '');
$hasil        = $customerObj->getAll($search);
$current_page = 'customer';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer - Rental PS</title>
  <meta name="description" content="Kelola data pelanggan dan file KTP jaminan pada sistem penyewaan konsol game.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    /* Preview KTP saat memilih file baru */
    #preview-ktp-tambah, #preview-ktp-edit {
      max-width: 200px; max-height: 120px;
      border-radius: 8px; border: 2px solid var(--border-color);
      object-fit: cover; display: none;
      margin-top: 8px;
    }
  </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

  <div class="top-header">
    <div>
      <h1 class="page-title"><i class="bi bi-person-badge me-2 text-primary"></i>Master Customer</h1>
      <p class="page-subtitle">Kelola data pelanggan beserta foto KTP jaminan</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" id="btn-tambah-customer">
      <i class="bi bi-person-plus-fill me-1"></i> Tambah Customer
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
        <span class="table-title"><i class="bi bi-table me-2"></i>Daftar Customer</span>
        <form method="GET" id="form-search-customer">
          <div class="search-bar">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                   id="input-search-customer"
                   name="search"
                   class="form-control"
                   placeholder="Cari nama / no. WA..."
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0" id="table-customer">
          <thead>
            <tr>
              <th style="width:50px">#</th>
              <th>Nama Lengkap</th>
              <th>No. WhatsApp</th>
              <th>Alamat</th>
              <th class="text-center">Foto KTP</th>
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
                  <div class="empty-icon"><i class="bi bi-person-x"></i></div>
                  <p><?= !empty($search)
                       ? "Tidak ditemukan hasil untuk \"<strong>" . htmlspecialchars($search) . "</strong>\""
                       : "Belum ada data customer. Klik <strong>Tambah Customer</strong> untuk memulai." ?></p>
                </div>
              </td>
            </tr>
            <?php else: foreach ($rows as $row): ?>
            <?php $ktp_path = '../uploads/' . htmlspecialchars($row['foto_ktp']); ?>
            <tr id="row-customer-<?= $row['id_customer'] ?>">
              <td class="text-muted"><?= $no++ ?></td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
              </td>
              <td>
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['no_wa']) ?>"
                   target="_blank" class="text-decoration-none text-success fw-500">
                  <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($row['no_wa']) ?>
                </a>
              </td>
              <td class="text-muted" style="max-width:200px">
                <span style="white-space:normal; font-size:13px"><?= nl2br(htmlspecialchars($row['alamat'])) ?></span>
              </td>
              <td class="text-center">
                <?php if (file_exists($ktp_path)): ?>
                  <img src="<?= $ktp_path ?>"
                       class="ktp-thumb"
                       alt="KTP <?= htmlspecialchars($row['nama_lengkap']) ?>"
                       onclick="lihatKTP('<?= $ktp_path ?>', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>')"
                       title="Klik untuk memperbesar">
                <?php else: ?>
                  <span class="badge bg-warning text-dark">File tidak ada</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-action-edit me-1"
                        id="btn-edit-customer-<?= $row['id_customer'] ?>"
                        onclick="bukaModeEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                        title="Edit">
                  <i class="bi bi-pencil-fill me-1"></i>Edit
                </button>
                <button class="btn btn-sm btn-action-delete"
                        id="btn-hapus-customer-<?= $row['id_customer'] ?>"
                        onclick="konfirmasiHapus(<?= $row['id_customer'] ?>, '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>')"
                        title="Hapus">
                  <i class="bi bi-trash3-fill me-1"></i>Hapus
                </button>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($search)): ?>
      <div class="mt-2">
        <a href="customer.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i>Hapus Filter
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>


<!-- ══ MODAL: Tambah Customer ══ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="labelTambahCustomer" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="customer.php" enctype="multipart/form-data" id="form-tambah-customer">
        <input type="hidden" name="action" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="labelTambahCustomer"><i class="bi bi-person-plus-fill me-2"></i>Tambah Customer Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="tambah_nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="tambah_nama" name="nama_lengkap"
                     placeholder="Nama sesuai KTP" required autocomplete="off">
            </div>
            <div class="col-md-6">
              <label for="tambah_no_wa" class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                <input type="text" class="form-control" id="tambah_no_wa" name="no_wa"
                       placeholder="08xxxxxxxxxx" required autocomplete="off">
              </div>
            </div>
            <div class="col-12">
              <label for="tambah_alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
              <textarea class="form-control" id="tambah_alamat" name="alamat" rows="3"
                        placeholder="Alamat lengkap customer" required></textarea>
            </div>
            <div class="col-12">
              <label for="tambah_foto_ktp" class="form-label">
                Foto KTP <span class="text-danger">*</span>
                <small class="text-muted fw-normal ms-1">(.jpg / .png, maks 2MB)</small>
              </label>
              <input type="file" class="form-control" id="tambah_foto_ktp" name="foto_ktp"
                     accept=".jpg,.jpeg,.png" required onchange="previewKTP(this, 'preview-ktp-tambah')">
              <img id="preview-ktp-tambah" src="" alt="Preview KTP">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-tambah-customer"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══ MODAL: Edit Customer ══ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="labelEditCustomer" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="customer.php" enctype="multipart/form-data" id="form-edit-customer">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id_customer" id="edit_id_customer">

        <div class="modal-header">
          <h5 class="modal-title" id="labelEditCustomer"><i class="bi bi-pencil-square me-2"></i>Edit Data Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="edit_nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_nama" name="nama_lengkap" required autocomplete="off">
            </div>
            <div class="col-md-6">
              <label for="edit_no_wa" class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                <input type="text" class="form-control" id="edit_no_wa" name="no_wa" required autocomplete="off">
              </div>
            </div>
            <div class="col-12">
              <label for="edit_alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
              <textarea class="form-control" id="edit_alamat" name="alamat" rows="3" required></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">
                Ganti Foto KTP
                <small class="text-muted fw-normal ms-1">(kosongkan jika tidak ingin mengganti)</small>
              </label>
              <!-- Foto KTP lama -->
              <div class="mb-2 d-flex align-items-center gap-3">
                <span class="text-muted small">Foto saat ini:</span>
                <img id="edit_ktp_lama_preview" src="" alt="KTP Lama"
                     style="height:50px; border-radius:6px; border:2px solid var(--border-color); object-fit:cover;">
              </div>
              <input type="file" class="form-control" id="edit_foto_ktp" name="foto_ktp"
                     accept=".jpg,.jpeg,.png" onchange="previewKTP(this, 'preview-ktp-edit')">
              <img id="preview-ktp-edit" src="" alt="Preview KTP Baru">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-edit-customer"><i class="bi bi-check-lg me-1"></i>Perbarui</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══ MODAL: Lihat KTP Fullsize ══ -->
<div class="modal fade" id="modalLihatKTP" tabindex="-1" aria-labelledby="labelLihatKTP" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labelLihatKTP"><i class="bi bi-image me-2"></i>Foto KTP - <span id="ktp_nama_customer"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-0">
        <img id="ktp_fullsize" src="" alt="Foto KTP"
             style="width:100%; max-height:80vh; object-fit:contain; border-radius:0 0 var(--radius) var(--radius);">
      </div>
    </div>
  </div>
</div>


<!-- ══ MODAL: Konfirmasi Hapus ══ -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="labelHapusCustomer" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="POST" action="customer.php" id="form-hapus-customer">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_customer" id="hapus_id_customer">

        <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
          <h5 class="modal-title text-white" id="labelHapusCustomer"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center py-4">
          <i class="bi bi-person-x fs-1 text-danger mb-3 d-block"></i>
          <p class="mb-1 fw-bold">Hapus customer ini?</p>
          <p class="text-muted small"><strong id="hapus_nama_customer"></strong></p>
          <p class="text-danger small mt-1">
            <i class="bi bi-info-circle me-1"></i>File KTP akan ikut terhapus dari server.
          </p>
        </div>

        <div class="modal-footer justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger" id="btn-submit-hapus-customer"><i class="bi bi-trash3 me-1"></i>Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Preview file KTP sebelum submit ──
function previewKTP(input, previewId) {
  const preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    preview.style.display = 'none';
  }
}

// ── Buka Modal Edit ──
function bukaModeEdit(data) {
  document.getElementById('edit_id_customer').value = data.id_customer;
  document.getElementById('edit_nama').value         = data.nama_lengkap;
  document.getElementById('edit_no_wa').value        = data.no_wa;
  document.getElementById('edit_alamat').value       = data.alamat;
  // Tampilkan foto KTP lama
  document.getElementById('edit_ktp_lama_preview').src = '../uploads/' + data.foto_ktp;
  // Reset file input & preview baru
  document.getElementById('edit_foto_ktp').value    = '';
  document.getElementById('preview-ktp-edit').style.display = 'none';
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ── Lihat KTP Fullsize ──
function lihatKTP(path, nama) {
  document.getElementById('ktp_fullsize').src      = path;
  document.getElementById('ktp_nama_customer').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalLihatKTP')).show();
}

// ── Konfirmasi Hapus ──
function konfirmasiHapus(id, nama) {
  document.getElementById('hapus_id_customer').value        = id;
  document.getElementById('hapus_nama_customer').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

// ── Debounce search ──
let debounceTimer;
const searchInput = document.getElementById('input-search-customer');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('form-search-customer').submit(), 400);
  });
}

// ── Auto-dismiss flash ──
setTimeout(() => {
  document.querySelectorAll('.alert.flash-message').forEach(a => {
    bootstrap.Alert.getOrCreateInstance(a).close();
  });
}, 4000);
</script>
</body>
</html>
