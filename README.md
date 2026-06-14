# Spesifikasi Sistem Penyewaan Konsol (Point of Sales - OOP Version)

Dokumen ini berisi spesifikasi teknis, analisis kebutuhan, arsitektur OOP, rancangan database, dan standar debugging untuk sistem penyewaan Konsol berbasis **PHP Native (OOP)** dan **MySQL**. File ini dirancang sebagai panduan utama bagi **AI Agent** di IDE untuk melakukan *code generation* yang patuh terhadap seluruh parameter instrumen evaluasi akademis.

---

## 1. Analisis Kebutuhan Sistem (System Requirements)

### A. Deskripsi Singkat Sistem
Sistem Penyewaan Konsol ini adalah aplikasi berbasis web bertipe *Point of Sales* (POS) yang digunakan oleh admin/kasir toko rental. Sistem ini menangani pendaftaran data pelanggan, pengelolaan inventaris konsol beserta kategorinya, serta pencatatan transaksi sewa *take-home* secara *real-time* lengkap dengan kalkulasi denda keterlambatan otomatis dan pencetakan struk (invoice).

### B. Masalah yang Ingin Diselesaikan
1. Menggantikan pencatatan transaksi manual yang rentan terhadap kesalahan hitung durasi sewa.
2. Membantu admin melacak unit konsol yang sedang dibawa pulang (*take-home*) oleh pelanggan.
3. Mengotomatisasi perhitungan denda keterlambatan pengembalian secara presisi berdasarkan jam dengan batas toleransi yang adil.
4. Menyediakan bukti transaksi fisik/digital (Invoice) yang jelas bagi pelanggan.

### C. Pengguna Sistem
* **Admin / Staf Kasir Toko:** Memiliki akses penuh untuk mengelola data master, menginput transaksi baru ketika pelanggan datang ke toko, memproses pengembalian unit, dan mencetak invoice.

### D. Kebutuhan Fungsional
* Sistem harus dapat mengelola CRUD data Kategori, Konsol, dan Pelanggan.
* Sistem harus dapat mencatat transaksi sewa dan otomatis menghitung tanggal jatuh tempo.
* Sistem harus dapat menghitung denda secara otomatis jika pengembalian melewati batas toleransi 3 jam.
* Sistem harus menyediakan fitur pencarian data di setiap tabel master dan transaksi.
* Sistem harus dapat menghasilkan halaman Cetak Invoice untuk setiap transaksi yang selesai atau baru dibuat.

### E. Kebutuhan Non-Fungsional
* **Keamanan:** Validasi file upload untuk mencegah eksekusi skrip berbahaya.
* **Keandalan:** Penanganan error database menggunakan Exception Handling agar aplikasi tidak mati total saat terjadi kegagalan query.

---

## 2. Lingkungan Pengembangan (Tech Stack)
* **Bahasa Pemrograman:** PHP Native dengan paradigma **Full Object-Oriented Programming (OOP)**
* **Database:** MySQL / MariaDB (Minimal 4 Tabel Berelasi)
* **User Interface:** HTML5, CSS3, Bootstrap 5 (Untuk Layout Dashboard & Tabel)
* **Ekstensi PHP Wajib:** `PDO` (Mendukung Prepared Statements & OOP Exception)

---

## 3. Komponen Data Master & Transaksi

Sistem ini menggunakan **3 Data Master** dan **1 Data Transaksi** yang tersebar ke dalam **4 Tabel Database** berelasi:
1. **Data Master 1 (Kategori):** Mengelola kategori konsol (e.g., Handheld, Home Console).
2. **Data Master 2 (Konsol):** Mengelola unit fisik yang disewakan (e.g., PS5, Nintendo Switch).
3. **Data Master 3 (Customer):** Mengelola identitas pelanggan beserta file jaminan KTP.
4. **Data Transaksi (Sewa):** Mencatat relasi antara Pelanggan, Konsol, Durasi, dan perhitungan biaya/denda.

---

## 4. Rancangan Struktur Database (MySQL Schema)

```sql
CREATE DATABASE IF NOT EXISTS db_rental_ps;
USE db_rental_ps;

-- Master 1: Tabel Kategori
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

-- Master 2: Tabel Konsol (Berelasi dengan Kategori)
CREATE TABLE konsol (
    id_konsol INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    nama_konsol VARCHAR(50) NOT NULL,
    harga_per_hari INT NOT NULL,
    status ENUM('Tersedia', 'Disewa', 'Maintenance') DEFAULT 'Tersedia',
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Master 3: Tabel Customer
CREATE TABLE customer (
    id_customer INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    no_wa VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    foto_ktp VARCHAR(255) NOT NULL
);

-- Transaksi: Tabel Transaksi Penyewaan
CREATE TABLE transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    id_customer INT NOT NULL,
    id_konsol INT NOT NULL,
    pilihan_durasi ENUM('1 Hari', '1 Minggu', '1 Bulan') NOT NULL,
    harga_sewa INT NOT NULL,
    waktu_mulai_sewa DATETIME NOT NULL,
    waktu_seharusnya_kembali DATETIME NOT NULL,
    waktu_kembali_aktual DATETIME NULL,
    total_denda INT DEFAULT 0,
    status_transaksi ENUM('Sedang Disewa', 'Selesai') DEFAULT 'Sedang Disewa',
    FOREIGN KEY (id_customer) REFERENCES customer(id_customer) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_konsol) REFERENCES konsol(id_konsol) ON DELETE RESTRICT ON UPDATE CASCADE
);
```

## 5. Fitur Aplikasi

### A. Fitur Minimal Wajib

* Dashboard (dashboard.php): Menampilkan metrik ringkasan (Total Customer, Total Konsol Tersedia, Transaksi Aktif).
* CRUD Kategori (kategori.php): Kelola data master kategori.
* CRUD Konsol (konsol.php): Kelola data master unit konsol.
* CRUD Customer (customer.php): Kelola data master pelanggan.
* Transaksi Peminjaman (transaksi.php): Input sewa baru dan list riwayat transaksi.
* Pencarian Data: Kolom search berbasis SQL LIKE di setiap tabel data master dan transaksi.
* Validasi Form: Validasi sisi server (PHP) untuk memastikan form tidak kosong dan tipe data sesuai.

### B. Fitur Tambahan (Terimplementasi 4 Fitur)

* Upload Gambar: Unggah file foto KTP customer dengan validasi ekstensi (.jpg, .png).
* Status Transaksi: Pelacakan otomatis status perpindahan konsol ('Sedang Disewa' -> 'Selesai').
* Hitung Total Otomatis: Sistem menghitung denda secara otomatis jika waktu pengembalian melewati batas toleransi 3 jam.
* Cetak Laporan / Invoice: Menghasilkan halaman struk bukti penyewaan (saat transaksi selesai) yang siap dicetak menggunakan fungsi window.print().

---

## 6. Standar Kontrol Kode & Debugging (Wajib Penerapan)

Untuk memastikan penanganan error informatif selama fase pengujian,
Global Error Reporting: Wajib diletakkan pada berkas konfigurasi utama:

PHP

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

PDO Exception Handling: Seluruh operasi query wajib dibungkus blok try...catch untuk menangkap error SQL secara detail:

PHP

```php
try {
    // Kode Query PDO
} catch (PDOException $e) {
    die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
}
```

Fungsi Dump & Die (dd): Sediakan fungsi helper global untuk inspeksi variabel array ($_POST atau $_FILES):

PHP

```php
function dd($data) {
    echo "<pre style='background:#111; color:#0f0; padding:10px; border-radius:5px;'>";
    print_r($data);
    echo "</pre>";
    die();
}
```

---

## 7. Referensi Struktur Class OOP (Panduan AI Agent)

AI Agent wajib mengimplementasikan struktur berbasis Class berikut:

### A. Class Database (Database.php)

PHP

```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "rental_konsol_oop";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die("Koneksi database gagal: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>
```

### B. Class Transaksi (Transaksi.php) - Logika Waktu & Denda

PHP

```php
<?php
class Transaksi {
    private $conn;
    private $table_name = "transaksi";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Hitung Estimasi Kembali Berdasarkan Pilihan Durasi
    public function hitungEstimasiKembali($waktu_mulai, $durasi) {
        $estimasi = new DateTime($waktu_mulai);
        if ($durasi == '1 Hari') $estimasi->modify('+1 day');
        elseif ($durasi == '1 Minggu') $estimasi->modify('+7 days');
        elseif ($durasi == '1 Bulan') $estimasi->modify('+30 days');

        return $estimasi->format('Y-m-d H:i:s');
    }

    // Jalankan Logika Denda dengan Toleransi 3 Jam
    public function hitungDenda($waktu_seharusnya, $waktu_aktual) {
        $seharusnya = new DateTime($waktu_seharusnya);
        $aktual = new DateTime($waktu_aktual);

        if ($aktual <= $seharusnya) return 0;

        $interval = $seharusnya->diff($aktual);
        $total_jam_terlambat = ($interval->days * 24) + $interval->h;

        // Toleransi Keterlambatan 3 Jam
        if ($total_jam_terlambat <= 3) {
            return 0;
        }

        // Perhitungan Denda Harian (Pembulatan Ke Atas) jika lewat dari 3 jam
        $hari_terlambat = ceil($total_jam_terlambat / 24);
        $tarif_denda_per_hari = 50000;

        return $hari_terlambat * $tarif_denda_per_hari;
    }
}
?>
```

---

## 8. Struktur Direktori Proyek


```text
rental-konsol-oop/
│
├── classes/              # Wadah Berkas Berbasis OOP Class
│   ├── Database.php
│   ├── Kategori.php
│   ├── Konsol.php
│   ├── Customer.php
│   └── Transaksi.php
│
├── uploads/              # Penyimpanan Berkas KTP Gambar
│
├── dashboard.php         # Halaman Utama Ringkasan
├── kategori.php          # Interface CRUD Master 1
├── konsol.php            # Interface CRUD Master 2
├── customer.php          # Interface CRUD Master 3
├── transaksi.php         # Interface Transaksi & List Sewa
├── cetak_invoice.php     # Interface khusus print/cetak laporan struk
└── README.md             # File Spesifikasi ini
```
