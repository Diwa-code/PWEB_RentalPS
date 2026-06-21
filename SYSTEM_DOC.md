# 🎮 SYSTEM_DOC — Dokumentasi Teknis Sistem Penyewaan Konsol (Planet Station)

> Dokumen ini membahas sistem **Rental PS** secara menyeluruh: mulai dari arsitektur koneksi database, alur debugging, integrasi front-end ke back-end, hingga kebutuhan fungsional dan non-fungsional.

---

## 📋 Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Arsitektur & Struktur Direktori](#2-arsitektur--struktur-direktori)
3. [Koneksi Database](#3-koneksi-database)
4. [Proses Debugging Sistem](#4-proses-debugging-sistem)
5. [Integrasi Front-End ke Back-End](#5-integrasi-front-end-ke-back-end)
6. [Kebutuhan Fungsional](#6-kebutuhan-fungsional)
7. [Kebutuhan Non-Fungsional](#7-kebutuhan-non-fungsional)
8. [Diagram Alur Sistem](#8-diagram-alur-sistem)

---

## 1. Gambaran Umum Sistem

**Planet Station - Rental PS** adalah aplikasi web berbasis *Point of Sales* (POS) yang dibangun menggunakan:

| Komponen         | Teknologi                        |
|------------------|----------------------------------|
| Bahasa Server    | PHP 8.x (Full OOP)               |
| Database         | MySQL / MariaDB via XAMPP        |
| Antarmuka DB     | PDO (PHP Data Objects)           |
| Front-End        | HTML5, CSS3, Bootstrap 5.3       |
| Ikon UI          | Bootstrap Icons 1.11             |
| Server Lokal     | Apache (XAMPP)                   |

Sistem ini digunakan oleh **admin/kasir toko** untuk:
- Mengelola data master (Kategori, Konsol, Customer)
- Mencatat transaksi penyewaan take-home
- Menghitung denda keterlambatan secara otomatis
- Mencetak struk/invoice per transaksi

---

## 2. Arsitektur & Struktur Direktori

Sistem menggunakan pola arsitektur **Separation of Concerns** sederhana: logika bisnis dipisahkan ke dalam `classes/`, sedangkan tampilan ada di file PHP root level.

```text
PWEB-RentalPS/
│
├── classes/                  ← Layer Back-End (Business Logic / Model)
│   ├── Database.php          ← Koneksi PDO + helper dd()
│   ├── Kategori.php          ← CRUD tabel kategori
│   ├── Konsol.php            ← CRUD tabel konsol + updateStatus()
│   ├── Customer.php          ← CRUD tabel customer + upload KTP
│   └── Transaksi.php         ← CRUD transaksi + logika denda & waktu
│
├── includes/                 ← Komponen UI yang Dapat Dipakai Ulang
│   └── sidebar.php           ← Navigasi sidebar (stateful: active page)
│
├── assets/                   ← Aset Statis Front-End
│   └── style.css             ← Custom CSS (design system, dark-ish theme)
│
├── uploads/                  ← Penyimpanan file KTP customer (server-side)
│
├── dashboard.php             ← Halaman utama (metrik ringkasan)
├── kategori.php              ← Interface CRUD Kategori
├── konsol.php                ← Interface CRUD Konsol
├── customer.php              ← Interface CRUD Customer + upload KTP
├── transaksi.php             ← Interface transaksi sewa + proses kembali
├── cetak_invoice.php         ← Halaman cetak struk (print-friendly)
│
├── README.md                 ← Spesifikasi teknis (panduan AI agent)
└── SYSTEM_DOC.md             ← Dokumen ini (dokumentasi sistem lengkap)
```

### Pola Arsitektur per Halaman

Setiap halaman PHP root level mengikuti pola yang konsisten:

```
[1] require_once class-class yang dibutuhkan
     ↓
[2] Buat objek Database → ambil koneksi PDO
     ↓
[3] Buat objek class bisnis (Kategori / Konsol / Customer / Transaksi)
     ↓
[4] Handle POST (CREATE / UPDATE / DELETE / KEMBALI) → redirect (PRG Pattern)
     ↓
[5] Handle GET (READ + search) → render HTML dengan data dari class
```

---

## 3. Koneksi Database

### 3.1 Konfigurasi Koneksi

Seluruh koneksi database dikelola oleh satu class terpusat di [`classes/Database.php`](classes/Database.php):

```php
class Database {
    private $host     = "localhost";      // Host MySQL (XAMPP default)
    private $db_name  = "db_rental_ps";  // Nama database yang harus dibuat
    private $username = "root";           // Username MySQL default XAMPP
    private $password = "";               // Password kosong (default XAMPP)
    public  $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host
                . ";dbname=" . $this->db_name
                . ";charset=utf8",
                $this->username,
                $this->password
            );
            // Lempar exception jika ada error SQL (bukan silent fail)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Hasil fetch selalu berupa array asosiatif
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            die("Koneksi database gagal: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
```

### 3.2 Cara Penggunaan Koneksi di Setiap Halaman

Semua halaman PHP menggunakan pola yang sama untuk mendapatkan koneksi:

```php
// Langkah 1: Muat class Database
require_once 'classes/Database.php';
require_once 'classes/Transaksi.php'; // atau class lain yang dibutuhkan

// Langkah 2: Buat instance dan ambil koneksi PDO
$database     = new Database();
$db           = $database->getConnection(); // $db adalah objek PDO

// Langkah 3: Inject koneksi ke class bisnis (Dependency Injection)
$transaksiObj = new Transaksi($db);
```

> **Catatan Penting:** Koneksi dibuat **sekali per request halaman** dan **diinjeksikan** ke semua class yang membutuhkan. Ini mencegah banyak koneksi terbuka ke database.

### 3.3 Skema Database

Sistem menggunakan database bernama `db_rental_ps` dengan 4 tabel berelasi:

```
┌─────────────┐          ┌───────────────────┐
│  kategori   │          │      konsol       │
├─────────────┤ 1      * ├───────────────────┤
│ id_kategori │◄─────────│ id_kategori (FK)  │
│ nama_kategori│         │ id_konsol         │
└─────────────┘          │ nama_konsol       │
                         │ harga_per_hari    │
                         │ status            │
                         └────────┬──────────┘
                                  │ 1
                                  │
┌─────────────┐                   │ *
│  customer   │     ┌─────────────▼──────────────┐
├─────────────┤     │         transaksi           │
│ id_customer │     ├────────────────────────────-│
│ nama_lengkap│ 1 * │ id_transaksi               │
│ no_wa       │◄────│ id_customer (FK)            │
│ alamat      │     │ id_konsol (FK)              │
│ foto_ktp    │     │ pilihan_durasi              │
└─────────────┘     │ harga_sewa                  │
                    │ waktu_mulai_sewa             │
                    │ waktu_seharusnya_kembali     │
                    │ waktu_kembali_aktual (NULL)  │
                    │ total_denda                  │
                    │ status_transaksi             │
                    └──────────────────────────────┘
```

**Relasi Foreign Key:**
- `konsol.id_kategori → kategori.id_kategori` (`ON UPDATE CASCADE, ON DELETE RESTRICT`)
- `transaksi.id_customer → customer.id_customer` (`ON DELETE CASCADE, ON UPDATE CASCADE`)
- `transaksi.id_konsol → konsol.id_konsol` (`ON DELETE RESTRICT, ON UPDATE CASCADE`)

### 3.4 Cara Setup Database

1. Buka **phpMyAdmin** di `http://localhost/phpmyadmin`
2. Buat database baru bernama `db_rental_ps`
3. Jalankan SQL berikut:

```sql
CREATE DATABASE IF NOT EXISTS db_rental_ps;
USE db_rental_ps;

CREATE TABLE kategori (
    id_kategori   INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

CREATE TABLE konsol (
    id_konsol      INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori    INT NOT NULL,
    nama_konsol    VARCHAR(50) NOT NULL,
    harga_per_hari INT NOT NULL,
    status         ENUM('Tersedia','Disewa','Maintenance') DEFAULT 'Tersedia',
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE customer (
    id_customer  INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    no_wa        VARCHAR(20) NOT NULL,
    alamat       TEXT NOT NULL,
    foto_ktp     VARCHAR(255) NOT NULL
);

CREATE TABLE transaksi (
    id_transaksi              INT AUTO_INCREMENT PRIMARY KEY,
    id_customer               INT NOT NULL,
    id_konsol                 INT NOT NULL,
    pilihan_durasi            ENUM('1 Hari','1 Minggu','1 Bulan') NOT NULL,
    harga_sewa                INT NOT NULL,
    waktu_mulai_sewa          DATETIME NOT NULL,
    waktu_seharusnya_kembali  DATETIME NOT NULL,
    waktu_kembali_aktual      DATETIME NULL,
    total_denda               INT DEFAULT 0,
    status_transaksi          ENUM('Sedang Disewa','Selesai') DEFAULT 'Sedang Disewa',
    FOREIGN KEY (id_customer) REFERENCES customer(id_customer)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_konsol)   REFERENCES konsol(id_konsol)
        ON DELETE RESTRICT ON UPDATE CASCADE
);
```

### 3.5 Prepared Statements — Keamanan Query

Semua query menggunakan **PDO Prepared Statements** dengan parameter binding untuk mencegah SQL Injection:

```php
// ✅ BENAR — Menggunakan parameter binding
$query = "SELECT * FROM customer WHERE id_customer = :id LIMIT 1";
$stmt  = $this->conn->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

// ❌ SALAH — Interpolasi string langsung (rentan SQL Injection)
$query = "SELECT * FROM customer WHERE id_customer = $id";
```

---

## 4. Proses Debugging Sistem

### 4.1 Error Reporting PHP (Development Mode)

Di [`classes/Database.php`](classes/Database.php), konfigurasi ini **wajib aktif** selama pengembangan:

```php
error_reporting(E_ALL);         // Tampilkan semua jenis error
ini_set('display_errors', 1);   // Tampilkan error langsung di browser
```

> ⚠️ **Matikan** kedua baris ini (atau set `display_errors = 0`) saat deployment ke production.

### 4.2 Fungsi `dd()` — Dump & Die

Helper untuk menginspeksi nilai variabel, khususnya array `$_POST` atau `$_FILES`:

```php
// Definisi (ada di Database.php, otomatis tersedia di semua halaman)
function dd($data) {
    echo "<pre style='background:#111; color:#0f0; padding:10px;
          border-radius:5px; font-family:monospace; font-size:13px;'>";
    print_r($data);
    echo "</pre>";
    die(); // Hentikan eksekusi
}

// Cara pakai — tambahkan sementara di handler POST:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dd($_POST);           // Lihat semua data form yang dikirim
    // dd($_FILES);       // Lihat data file upload
    // dd($someArray);    // Inspeksi array apa pun
}
```

**Output `dd()`** akan muncul dengan latar hitam dan teks hijau di browser, mirip terminal.

### 4.3 PDO Exception Handling

Semua operasi database dibungkus `try...catch` untuk menangkap error SQL secara detail:

```php
try {
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
} catch (PDOException $e) {
    die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
    // Contoh output: "Gagal Mengeksekusi Operasi Database: SQLSTATE[42S02]:
    //                 Base table or view not found: 1146 Table 'db_rental_ps.xxx' doesn't exist"
}
```

### 4.4 Skenario Error Umum & Solusinya

| Error yang Muncul | Penyebab | Solusi |
|---|---|---|
| `Koneksi database gagal: Access denied for user 'root'@'localhost'` | Password MySQL bukan string kosong | Ubah `$password = ""` menjadi password MySQL Anda |
| `Koneksi database gagal: Unknown database 'db_rental_ps'` | Database belum dibuat | Jalankan SQL `CREATE DATABASE db_rental_ps` di phpMyAdmin |
| `Gagal Mengeksekusi: Table 'db_rental_ps.kategori' doesn't exist` | Tabel belum dibuat | Jalankan script SQL lengkap dari bagian 3.4 |
| `move_uploaded_file(): Unable to move` | Folder `uploads/` tidak ada atau permission ditolak | Buat folder `uploads/` dan beri permission `chmod 755 uploads/` |
| `Kategori tidak bisa dihapus karena masih digunakan oleh Konsol` | FK RESTRICT mencegah penghapusan | Hapus/pindahkan semua Konsol yang memakai kategori ini terlebih dahulu |
| `Customer tidak bisa dihapus karena masih memiliki riwayat Transaksi` | FK constraint (bukan CASCADE) | Transaksi harus diselesaikan dulu sebelum customer bisa dihapus |

### 4.5 Debugging Upload File KTP

Jika upload KTP gagal, lakukan pengecekan bertahap:

```php
// Tambahkan sementara di customer.php sebelum memanggil $customerObj->create()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    dd($_FILES['foto_ktp']);
    // Output yang diharapkan:
    // Array (
    //   [name]     => ktp_john.jpg
    //   [type]     => image/jpeg
    //   [tmp_name] => /tmp/phpXXXXX
    //   [error]    => 0           ← 0 berarti UPLOAD_ERR_OK
    //   [size]     => 245678
    // )
}
```

**Kode error upload PHP:**
- `0` = OK
- `1` = File melebihi `upload_max_filesize` di php.ini
- `2` = File melebihi `MAX_FILE_SIZE` di form HTML
- `4` = Tidak ada file yang diunggah
- `6` = Folder `tmp` tidak ditemukan

### 4.6 Debugging Logika Denda

Logika denda ada dua tempat: PHP (server) dan JavaScript (client preview). Keduanya harus sinkron.

**PHP — `classes/Transaksi.php`:**
```php
public function hitungDenda($waktu_seharusnya, $waktu_aktual) {
    $seharusnya = new DateTime($waktu_seharusnya);
    $aktual     = new DateTime($waktu_aktual);

    if ($aktual <= $seharusnya) return 0;              // Tidak terlambat

    $interval            = $seharusnya->diff($aktual);
    $total_jam_terlambat = ($interval->days * 24) + $interval->h;

    if ($total_jam_terlambat <= 3) return 0;           // Dalam toleransi

    $hari_terlambat = ceil($total_jam_terlambat / 24); // Bulatkan ke atas
    return $hari_terlambat * 50000;                    // Rp50.000/hari
}
```

**JavaScript mirror — `transaksi.php` (fungsi `kalkulasiDendaLive`):**
```js
const diffMs        = aktualDt - seharusnyaDt;
const totalJam      = diffMs / (1000 * 60 * 60);
if (totalJam <= 3) { /* tidak ada denda */ }
const hariTerlambat = Math.ceil(totalJam / 24);
const denda         = hariTerlambat * 50000;
```

> Jika hasil preview JS berbeda dengan hasil final PHP, periksa **format string datetime** yang dikirim. JS menggunakan `'T'` sebagai separator (`2024-01-15T14:30`), PHP menggunakan spasi (`2024-01-15 14:30:00`). Konversi dilakukan via `.replace(' ', 'T')`.

---

## 5. Integrasi Front-End ke Back-End

### 5.1 Pola Umum: POST-Redirect-GET (PRG Pattern)

Seluruh operasi yang mengubah data mengikuti pola **PRG** untuk mencegah form submission ganda:

```
Browser (Form HTML)
      │
      │ POST /transaksi.php
      ▼
  transaksi.php
  ├── Validasi input $_POST
  ├── Panggil method class (create / prosesKembali)
  ├── Simpan pesan flash ke $_SESSION
  └── header('Location: transaksi.php') ← Redirect
            │
            │ GET /transaksi.php
            ▼
        transaksi.php
        ├── Baca & hapus flash dari $_SESSION
        └── Render HTML dengan data terbaru
```

**Implementasi di `transaksi.php`:**
```php
// ── Setelah handle POST ──
$_SESSION['flash']      = $flash;
$_SESSION['flash_type'] = $flash_type;
header('Location: transaksi.php');
exit; // ← WAJIB setelah header redirect

// ── Di bagian GET (render) ──
if (!empty($_SESSION['flash'])) {
    $flash      = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash'], $_SESSION['flash_type']); // Hapus setelah dibaca
}
```

### 5.2 Alur Integrasi: Membuat Transaksi Sewa Baru

```
[Front-End]                              [Back-End]
    │                                        │
    │  User klik tombol "Sewa Baru"          │
    │  Bootstrap Modal terbuka               │
    │  JS set waktu_mulai = "sekarang"       │
    │                                        │
    │  User pilih Customer dari <select>     │
    │  JS: tampilInfoCustomer()              │
    │  → Ambil data dari data-* attribute    │
    │  → Tampilkan info + preview KTP        │
    │                                        │
    │  User pilih Konsol                     │
    │  JS: updateHarga()                     │
    │  → Set hidden input harga_per_hari     │
    │                                        │
    │  User pilih Durasi                     │
    │  JS: hitungHargaTotal()                │
    │  → Kalkulasi & tampilkan total         │
    │                                        │
    │  User submit form                      │
    │─────────────── POST ─────────────────►│
    │  action=create                         │
    │  id_customer, id_konsol                │
    │  pilihan_durasi, harga_per_hari        │ PHP validasi input
    │  waktu_mulai_sewa                      │ Transaksi::create($data)
    │                                        │ ├── hitungEstimasiKembali()
    │                                        │ ├── hitungHargaSewa()
    │                                        │ ├── INSERT INTO transaksi
    │                                        │ └── UPDATE konsol SET status='Disewa'
    │◄────────────── Redirect ───────────────│
    │  (flash: "Transaksi berhasil dicatat") │
```

### 5.3 Alur Integrasi: Proses Pengembalian Konsol

```
[Front-End]                              [Back-End]
    │                                        │
    │  User klik "Kembali" pada baris tabel  │
    │  JS: bukaModalKembali(data)            │
    │  → Isi form modal dari objek JSON PHP  │
    │  → Set waktu_kembali = "sekarang"      │
    │                                        │
    │  User ubah waktu_kembali_aktual        │
    │  JS: kalkulasiDendaLive(waktu)         │
    │  → Preview estimasi denda real-time    │
    │                                        │
    │  User klik "Konfirmasi Kembali"        │
    │──────────────── POST ────────────────►│
    │  action=kembali                        │
    │  id_transaksi, id_konsol               │  PHP validasi input
    │  waktu_seharusnya_kembali              │  Transaksi::prosesKembali()
    │  waktu_kembali_aktual                  │  ├── hitungDenda()
    │                                        │  ├── UPDATE transaksi
    │                                        │  │     SET status='Selesai'
    │                                        │  │         total_denda = X
    │                                        │  └── UPDATE konsol
    │                                        │        SET status='Tersedia'
    │◄────────────── Redirect ───────────────│
    │  (flash: "Pengembalian berhasil /      │
    │           denda Rp X.XXX")             │
```

### 5.4 Alur Integrasi: Cetak Invoice

Invoice adalah halaman **read-only** terpisah yang diakses via query parameter:

```
transaksi.php
  │
  │ User klik tombol "Invoice" (target="_blank")
  │
  ▼
GET cetak_invoice.php?id=42
  │
  ├── require_once Database.php, Transaksi.php
  ├── $t = $transaksiObj->getById(42)
  │   → JOIN ke customer & konsol
  │
  ├── Render HTML invoice (full-page, tanpa sidebar)
  │
  └── User klik tombol "Cetak Invoice"
      → JS: window.print()
      → CSS @media print menyembunyikan toolbar
```

### 5.5 Pengiriman Data Customer ke Modal via `data-*` Attribute

PHP meng-embed data customer langsung ke dalam atribut HTML `data-*` agar JavaScript bisa mengaksesnya tanpa AJAX:

```php
<!-- PHP render option dengan data-* -->
<option value="<?= $c['id_customer'] ?>"
        data-nama="<?= htmlspecialchars($c['nama_lengkap']) ?>"
        data-wa="<?= htmlspecialchars($c['no_wa']) ?>"
        data-alamat="<?= htmlspecialchars($c['alamat']) ?>"
        data-ktp="<?= htmlspecialchars($c['foto_ktp']) ?>">
    <?= htmlspecialchars($c['nama_lengkap']) ?>
</option>
```

```javascript
// JavaScript membaca data-* attribute
function tampilInfoCustomer(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('info-nama').textContent   = opt.dataset.nama;
    document.getElementById('info-wa').textContent     = opt.dataset.wa;
    document.getElementById('info-alamat').textContent = opt.dataset.alamat;
    // Tampilkan preview foto KTP
    document.getElementById('info-ktp-img').src = 'uploads/' + opt.dataset.ktp;
}
```

### 5.6 Pengiriman Data Transaksi ke Modal via JSON (`json_encode`)

Data baris transaksi dikirim ke fungsi JS modal melalui atribut `onclick`:

```php
<button onclick="bukaModalKembali(<?= htmlspecialchars(json_encode($row)) ?>)">
    Kembali
</button>
```

PHP mengubah array `$row` menjadi JSON string, lalu JavaScript menerimanya sebagai object:

```javascript
function bukaModalKembali(data) {
    // data adalah object JavaScript dengan semua kolom tabel transaksi
    document.getElementById('kembali_id_transaksi').value = data.id_transaksi;
    document.getElementById('kembali_nama_customer').textContent = data.nama_lengkap;
    // dst...
}
```

### 5.7 Debounce pada Fitur Pencarian

Pencarian tidak memerlukan tombol submit — input langsung memicu GET request setelah jeda 400ms:

```javascript
let debounceTimer;
const searchInput = document.getElementById('input-search-transaksi');
searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        document.getElementById('form-search-transaksi').submit();
    }, 400); // Tunggu 400ms setelah pengguna berhenti mengetik
});
```

Di sisi PHP, query menggunakan `LIKE` dengan wildcard:
```php
WHERE c.nama_lengkap LIKE :search OR k.nama_konsol LIKE :search2
// :search = "%kata_kunci%"
```

### 5.8 Sidebar Stateful (Halaman Aktif)

Sidebar mengetahui halaman mana yang sedang aktif melalui variabel `$current_page` yang didefinisikan di setiap halaman:

```php
// Di dashboard.php
$current_page = 'dashboard';

// Di transaksi.php
$current_page = 'transaksi';
```

```php
// Di includes/sidebar.php
<a href="transaksi.php"
   class="nav-link <?= $current_page === 'transaksi' ? 'active' : '' ?>">
    Data Sewa
</a>
```

---

## 6. Kebutuhan Fungsional

Kebutuhan fungsional adalah apa yang **harus dapat dilakukan** oleh sistem.

### 6.1 Manajemen Data Master

#### F-01: Manajemen Kategori
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-01.1 | Admin dapat menambah kategori baru | Form POST → `Kategori::create()` |
| F-01.2 | Admin dapat mengubah nama kategori | Form POST → `Kategori::update()` |
| F-01.3 | Admin dapat menghapus kategori (jika tidak dipakai) | Form POST → `Kategori::delete()` + error FK |
| F-01.4 | Admin dapat mencari kategori berdasarkan nama | GET `?search=` → `Kategori::getAll($search)` |

#### F-02: Manajemen Konsol
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-02.1 | Admin dapat menambah unit konsol baru dengan kategori | Form POST → `Konsol::create()` |
| F-02.2 | Admin dapat mengubah data konsol (nama, harga, kategori, status) | Form POST → `Konsol::update()` |
| F-02.3 | Admin dapat menghapus konsol (jika tidak ada transaksi aktif) | Form POST → `Konsol::delete()` |
| F-02.4 | Admin dapat mencari konsol berdasarkan nama atau kategori | GET `?search=` → `Konsol::getAll($search)` |
| F-02.5 | Status konsol berubah otomatis saat transaksi dibuat/diselesaikan | `Transaksi::create()` & `prosesKembali()` |

#### F-03: Manajemen Customer
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-03.1 | Admin dapat mendaftarkan customer baru dengan foto KTP | Form POST multipart → `Customer::create()` + `uploadKTP()` |
| F-03.2 | Admin dapat mengubah data customer (termasuk ganti foto KTP) | Form POST → `Customer::update()` + hapus file lama |
| F-03.3 | Admin dapat menghapus customer beserta file KTP-nya | Form POST → `Customer::delete()` + `unlink()` file |
| F-03.4 | Admin dapat mencari customer berdasarkan nama atau no. WhatsApp | GET `?search=` → `Customer::getAll($search)` |

### 6.2 Manajemen Transaksi

#### F-04: Pencatatan Sewa
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-04.1 | Admin dapat mencatat transaksi sewa baru | Form modal POST → `Transaksi::create()` |
| F-04.2 | Sistem menghitung harga sewa otomatis berdasarkan durasi | `Transaksi::hitungHargaSewa()` |
| F-04.3 | Sistem menghitung waktu jatuh tempo otomatis | `Transaksi::hitungEstimasiKembali()` |
| F-04.4 | Preview harga total tampil real-time di form (tanpa submit) | JavaScript `hitungHargaTotal()` |
| F-04.5 | Hanya konsol berstatus "Tersedia" yang dapat dipilih | `Transaksi::getKonsolTersedia()` |

#### F-05: Proses Pengembalian & Denda
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-05.1 | Admin dapat memproses pengembalian konsol | Form modal POST → `Transaksi::prosesKembali()` |
| F-05.2 | Sistem menghitung denda otomatis jika keterlambatan > 3 jam | `Transaksi::hitungDenda()` |
| F-05.3 | Toleransi keterlambatan 3 jam (denda = 0 jika ≤ 3 jam) | Kondisi di `hitungDenda()` |
| F-05.4 | Tarif denda Rp 50.000/hari dibulatkan ke atas | `ceil($total_jam / 24) * 50000` |
| F-05.5 | Preview estimasi denda tampil real-time saat pengguna memilih waktu kembali | JavaScript `kalkulasiDendaLive()` |
| F-05.6 | Konsol otomatis kembali ke status "Tersedia" setelah pengembalian | `UPDATE konsol SET status='Tersedia'` |

#### F-06: Cetak Invoice
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-06.1 | Admin dapat membuka invoice untuk setiap transaksi | Link `cetak_invoice.php?id=X` |
| F-06.2 | Invoice menampilkan data lengkap (customer, konsol, waktu, biaya) | `Transaksi::getById()` dengan JOIN |
| F-06.3 | Invoice dapat dicetak langsung ke printer | `window.print()` + `@media print` CSS |
| F-06.4 | Elemen toolbar disembunyikan saat cetak | CSS `@media print { .print-toolbar { display:none } }` |

### 6.3 Dashboard & Navigasi

#### F-07: Dashboard
| Kode | Deskripsi | Implementasi |
|------|-----------|--------------|
| F-07.1 | Dashboard menampilkan total customer | `Customer::countAll()` |
| F-07.2 | Dashboard menampilkan jumlah konsol tersedia | `Konsol::countTersedia()` |
| F-07.3 | Dashboard menampilkan jumlah transaksi aktif | Query langsung: `COUNT(*) WHERE status='Sedang Disewa'` |
| F-07.4 | Dashboard menampilkan total kategori | `Kategori::countAll()` |
| F-07.5 | Quick access links ke semua modul | HTML anchor ke masing-masing halaman |

---

## 7. Kebutuhan Non-Fungsional

Kebutuhan non-fungsional adalah **kualitas dan batasan** sistem.

### 7.1 Keamanan (Security)

| ID | Kebutuhan | Cara Implementasi |
|----|-----------|-------------------|
| NF-S1 | Mencegah SQL Injection | Semua query menggunakan PDO Prepared Statements dengan `bindParam()` |
| NF-S2 | Mencegah XSS (Cross-Site Scripting) | Semua output ke HTML menggunakan `htmlspecialchars()` |
| NF-S3 | Validasi tipe file upload | Hanya ekstensi `.jpg`, `.jpeg`, `.png` yang diterima di `Customer::uploadKTP()` |
| NF-S4 | Validasi ukuran file upload | Maksimum 2 MB per file KTP |
| NF-S5 | Nama file upload di-randomize | Nama file menggunakan `'ktp_' . time() . '_' . uniqid() . '.' . $ext` untuk mencegah tebakan nama file |
| NF-S6 | Session flash message | Pesan sukses/error dikirim via `$_SESSION` untuk mencegah manipulasi di URL |

### 7.2 Keandalan (Reliability)

| ID | Kebutuhan | Cara Implementasi |
|----|-----------|-------------------|
| NF-R1 | Aplikasi tidak crash total saat error database | Semua operasi DB dibungkus `try...catch(PDOException)` |
| NF-R2 | Error ditampilkan secara informatif | `die()` dengan pesan error PDO yang jelas |
| NF-R3 | File KTP tidak orphan jika query gagal | Pada `Customer::create()`, file yang terupload dihapus (`@unlink`) jika INSERT gagal |
| NF-R4 | Integritas data referensial dijaga | Foreign Key dengan `RESTRICT` dan `CASCADE` sesuai kebutuhan bisnis |
| NF-R5 | Duplikasi form submission dicegah | PRG Pattern (Post-Redirect-Get) di semua handler POST |

### 7.3 Performa (Performance)

| ID | Kebutuhan | Cara Implementasi |
|----|-----------|-------------------|
| NF-P1 | Pencarian tidak membebani server dengan request beruntun | Debounce 400ms pada input pencarian (JavaScript) |
| NF-P2 | Koneksi database dibuat satu kali per request | Satu instance `Database` per halaman, koneksi di-inject ke class |
| NF-P3 | Query JOIN efisien untuk data transaksi | `LEFT JOIN` dengan indeks FK otomatis dari MySQL |
| NF-P4 | Kalkulasi denda preview tanpa request ke server | Logika denda direplikasi di JavaScript untuk feedback instan |

### 7.4 Kegunaan (Usability)

| ID | Kebutuhan | Cara Implementasi |
|----|-----------|-------------------|
| NF-U1 | Feedback visual setelah setiap aksi | Alert Bootstrap (success/warning/danger) dengan auto-dismiss 5 detik |
| NF-U2 | Waktu mulai sewa otomatis terisi "sekarang" | JavaScript mengisi `datetime-local` saat modal dibuka |
| NF-U3 | Preview informasi customer setelah dipilih | JavaScript membaca `data-*` attribute dan render card info + foto KTP |
| NF-U4 | Baris terlambat ditandai secara visual | Class CSS `.terlambat` (warna merah) + ikon peringatan pada kolom Jatuh Tempo |
| NF-U5 | Navigasi aktif ditandai di sidebar | Variabel `$current_page` + class `active` pada sidebar |
| NF-U6 | Halaman invoice ramah cetak | CSS `@media print` menyesuaikan tampilan untuk printer |
| NF-U7 | Konsol tidak tersedia tidak muncul di pilihan sewa | Query `getKonsolTersedia()` hanya mengembalikan konsol berstatus "Tersedia" |

### 7.5 Maintainability (Kemudahan Pemeliharaan)

| ID | Kebutuhan | Cara Implementasi |
|----|-----------|-------------------|
| NF-M1 | Kode terorganisir dalam class OOP | Setiap entitas data punya class tersendiri di `classes/` |
| NF-M2 | Komponen UI dapat dipakai ulang | Sidebar dibuat sebagai partial `includes/sidebar.php` |
| NF-M3 | Konfigurasi database terpusat | Hanya `Database.php` yang perlu diubah jika konfigurasi DB berubah |
| NF-M4 | Kode terdokumentasi | PHPDoc di setiap method class |
| NF-M5 | Satu file CSS terpusat | `assets/style.css` sebagai design system utama |

---

## 8. Diagram Alur Sistem

### 8.1 Alur Sewa Konsol (Happy Path)

```
Admin buka transaksi.php
        │
        ▼
Klik "Sewa Baru"
        │
        ▼
Pilih Customer → Preview info + KTP tampil
        │
        ▼
Pilih Konsol (hanya yang "Tersedia") → Harga/hari otomatis
        │
        ▼
Pilih Durasi → Total harga tampil (real-time)
        │
        ▼
Klik "Simpan Transaksi"
        │
        ▼ [POST → PHP]
Validasi server-side
        │
        ├── GAGAL → Flash error, redirect kembali
        │
        └── SUKSES
              │
              ├── INSERT ke tabel transaksi
              │     - hitung waktu jatuh tempo otomatis
              │     - hitung harga sewa otomatis
              │
              └── UPDATE konsol SET status = 'Disewa'
                    │
                    ▼
              Flash "Berhasil!" → Redirect → Tampilkan daftar transaksi
```

### 8.2 Alur Pengembalian & Denda

```
Baris transaksi status "Sedang Disewa"
        │
        ▼
Klik "Kembali" → Modal terbuka dengan info transaksi
        │
        ▼
Admin ubah/konfirmasi waktu pengembalian aktual
        │
        ▼ [JavaScript]
kalkulasiDendaLive() → Preview denda tampil
        │
        ├── ≤ Jatuh tempo       → Denda: Rp 0 (tepat waktu)
        ├── > Jatuh tempo ≤ 3jam → Denda: Rp 0 (toleransi)
        └── > 3 jam terlambat    → Denda: ceil(jam/24) × Rp 50.000
        │
        ▼
Klik "Konfirmasi Kembali"
        │
        ▼ [POST → PHP]
Transaksi::prosesKembali()
        │
        ├── hitungDenda() → kalkulasi final server-side
        ├── UPDATE transaksi SET status='Selesai', total_denda=X
        └── UPDATE konsol SET status='Tersedia'
              │
              ▼
        Flash hasil → Redirect → Konsol siap disewa lagi
```

---

*Dokumentasi ini dibuat otomatis berdasarkan analisis source code sistem. Diperbarui: Juni 2026.*
