# 📖 Panduan Method — Dijelaskan dengan Bahasa Sehari-hari

> Dokumen ini menjelaskan semua "cara kerja" yang digunakan dalam sistem Rental PS.  
> Dibuat sesederhana mungkin — tidak perlu background teknis mendalam untuk memahaminya.

---

## 🤔 Dulu, Sebelum Mulai — Apa itu "Method"?

Bayangkan kamu sedang di kasir minimarket.  
Kamu bisa melakukan beberapa hal:

- 📋 **Lihat** daftar harga barang
- 🛒 **Tambah** barang baru ke keranjang
- ✏️ **Ubah** jumlah barang
- 🗑️ **Hapus** barang dari keranjang

Dalam pemrograman web, cara-cara melakukan tindakan itu disebut **method**.  
Ada dua "jalan utama" yang digunakan: **GET** (untuk melihat) dan **POST** (untuk mengubah data).

---

## 📋 Daftar Isi

1. [GET — "Saya Mau Lihat"](#1-get--saya-mau-lihat)
2. [POST — "Saya Mau Kirim Data"](#2-post--saya-mau-kirim-data)
3. [require_once — "Ambilkan File Ini"](#3-require_once--ambilkan-file-ini)
4. [new ClassName() — "Buat Alat Baru"](#4-new-classname--buat-alat-baru)
5. [getConnection() — "Sambungkan ke Database"](#5-getconnection--sambungkan-ke-database)
6. [getAll() — "Ambil Semua Data"](#6-getall--ambil-semua-data)
7. [getById() — "Ambil Satu Data Berdasarkan Nomor"](#7-getbyid--ambil-satu-data-berdasarkan-nomor)
8. [create() — "Simpan Data Baru"](#8-create--simpan-data-baru)
9. [update() — "Ubah Data yang Sudah Ada"](#9-update--ubah-data-yang-sudah-ada)
10. [delete() — "Hapus Data"](#10-delete--hapus-data)
11. [uploadKTP() — "Simpan File Foto"](#11-uploadktp--simpan-file-foto)
12. [hitungEstimasiKembali() — "Hitung Kapan Harus Kembali"](#12-hitungestimasikembali--hitung-kapan-harus-kembali)
13. [hitungHargaSewa() — "Hitung Total Biaya"](#13-hitunghargasewa--hitung-total-biaya)
14. [hitungDenda() — "Hitung Denda Keterlambatan"](#14-hitungdenda--hitung-denda-keterlambatan)
15. [prosesKembali() — "Catat Pengembalian Konsol"](#15-proseskembali--catat-pengembalian-konsol)
16. [countAll() / countTersedia() / countAktif() — "Hitung Jumlah"](#16-countall--counttserdia--countaktif--hitung-jumlah)
17. [header('Location: ...') — "Pindahkan Halaman"](#17-headerlocation---pindahkan-halaman)
18. [$_SESSION — "Ingat-ingatan Sementara"](#18-_session--ingat-ingatan-sementara)
19. [htmlspecialchars() — "Amankan Teks Sebelum Ditampilkan"](#19-htmlspecialchars--amankan-teks-sebelum-ditampilkan)
20. [try...catch — "Coba, Kalau Gagal Tangkap Errornya"](#20-trycatch--coba-kalau-gagal-tangkap-errornya)
21. [dd() — "Lihat Isi Variabel, Lalu Berhenti"](#21-dd--lihat-isi-variabel-lalu-berhenti)
22. [include — "Tempel File Lain di Sini"](#22-include--tempel-file-lain-di-sini)

---

## 1. GET — "Saya Mau Lihat"

**Analogi:** Seperti membuka buku menu di restoran. Kamu cuma **melihat**, tidak mengubah apapun.

**Kapan digunakan di sistem ini?**
- Saat kamu buka `transaksi.php` → sistem GET data semua transaksi dari database dan tampilkan di tabel
- Saat kamu ketik di kotak pencarian → sistem GET data yang cocok dengan kata kunci
- Saat kamu buka `cetak_invoice.php?id=5` → sistem GET data transaksi nomor 5

**Di mana ada di kode?**
```
URL: transaksi.php?search=budi
```
Kata `?search=budi` itu adalah informasi yang dikirim via GET.  
Di PHP dibaca seperti ini:
```php
$search = $_GET['search']; // Ambil kata "budi" dari URL
```

**Aturan penting GET:**
- ✅ Boleh untuk melihat/mencari data
- ❌ Jangan untuk mengirim data sensitif (karena muncul di URL dan bisa dilihat siapa saja)

---

## 2. POST — "Saya Mau Kirim Data"

**Analogi:** Seperti mengisi formulir dan menyerahkannya ke kasir. Data dikirim "di dalam amplop", tidak terlihat di URL.

**Kapan digunakan di sistem ini?**
- Saat kamu klik **"Simpan"** pada form tambah kategori baru
- Saat kamu klik **"Simpan Transaksi"** sewa baru
- Saat kamu klik **"Konfirmasi Kembali"** proses pengembalian
- Saat kamu klik **"Hapus"** data customer

**Di mana ada di kode?**
```html
<!-- Di HTML: form dengan method="POST" -->
<form method="POST" action="transaksi.php">
    <input type="text" name="nama_customer">
    <button type="submit">Simpan</button>
</form>
```
Di PHP, data dari form dibaca seperti ini:
```php
$nama = $_POST['nama_customer']; // Ambil isi kolom "nama_customer" dari form
```

**Aturan penting POST:**
- ✅ Untuk mengirim data yang akan disimpan/diubah/dihapus
- ✅ Lebih aman dari GET karena data tidak muncul di URL
- ✅ Bisa mengirim file (foto KTP)

---

## 3. `require_once` — "Ambilkan File Ini"

**Analogi:** Seperti kamu menyuruh seseorang "tolong ambilkan buku resep dari rak, saya mau pakai isinya di sini."

**Fungsi:** Memuat (memasukkan) isi file PHP lain ke dalam file yang sedang berjalan. `once` artinya hanya dimuat **satu kali** meskipun ditulis berkali-kali — mencegah error duplikat.

**Contoh di sistem:**
```php
// Di dashboard.php:
require_once 'classes/Database.php';   // Muat class koneksi database
require_once 'classes/Kategori.php';   // Muat class untuk kelola kategori
require_once 'classes/Konsol.php';     // Muat class untuk kelola konsol
require_once 'classes/Customer.php';   // Muat class untuk kelola customer
```

Setelah baris-baris itu, kode di `dashboard.php` bisa memakai semua fitur yang ada di keempat file tersebut.

**Kenapa tidak ditulis langsung di satu file?**  
Supaya kode lebih rapi dan tidak berulang. Kalau ada bug di logika koneksi database, cukup perbaiki di satu tempat (`Database.php`), dan semua halaman otomatis ikut terbenahi.

---

## 4. `new ClassName()` — "Buat Alat Baru"

**Analogi:** Seperti mengambil sebuah mesin dari gudang untuk dipakai. `new Transaksi()` = "keluarkan mesin bernama Transaksi dari gudang, siap dipakai."

**Fungsi:** Membuat **objek** (instance) dari sebuah class. Class adalah "cetakan" atau "blueprint", sedangkan objek adalah hasil cetakannya yang sudah bisa dipakai.

**Contoh di sistem:**
```php
$database     = new Database();      // Buat objek koneksi database
$db           = $database->getConnection(); // Aktifkan koneksi-nya

$transaksiObj = new Transaksi($db);  // Buat objek transaksi, kasih tahu
                                     // dia pakai koneksi yang mana
```

**Kenapa ada tanda `->` ?**  
Tanda `->` artinya "akses fitur/kemampuan milik objek ini."  
`$transaksiObj->create(...)` = "Suruh objek transaksiObj untuk menjalankan kemampuan create."

---

## 5. `getConnection()` — "Sambungkan ke Database"

**Analogi:** Seperti memasang selang antara keran air (database) dan ember (aplikasi PHP kamu). Setelah selang terpasang, air (data) bisa mengalir bolak-balik.

**Di mana ada di kode:** `classes/Database.php`

```php
public function getConnection() {
    // Coba sambungkan ke database MySQL di localhost
    // dengan nama database "db_rental_ps", user "root", password kosong
    $this->conn = new PDO("mysql:host=localhost;dbname=db_rental_ps", "root", "");
    return $this->conn; // Kembalikan koneksinya supaya bisa dipakai
}
```

**Apa itu PDO?**  
PDO adalah "penerjemah" antara PHP dan database. Bayangkan PHP berbicara bahasa Indonesia dan MySQL berbicara bahasa Inggris — PDO yang menerjemahkan di tengah, plus menjamin keamanannya.

**Kapan dipanggil?**  
Di awal setiap halaman PHP, sebelum ada operasi apapun ke database:
```php
$database = new Database();
$db = $database->getConnection(); // ← Ini yang membuka "selang"
```

---

## 6. `getAll()` — "Ambil Semua Data"

**Analogi:** Seperti meminta pegawai gudang untuk "tolong keluarkan daftar semua barang yang ada."

**Ada di:** `Kategori.php`, `Konsol.php`, `Customer.php`, `Transaksi.php`

**Cara kerjanya:**
1. Kirim perintah SQL `SELECT * FROM nama_tabel` ke database
2. Terima hasilnya (bisa berupa banyak baris data)
3. Kembalikan datanya ke halaman PHP yang memanggil

**Contoh penggunaan di `kategori.php`:**
```php
$search = $_GET['search'] ?? ''; // Ambil kata pencarian (kalau ada)
$stmt   = $kategoriObj->getAll($search); // Minta semua kategori
// Kalau ada kata pencarian, hanya kategori yang cocok yang dikembalikan

while ($row = $stmt->fetch()) {
    echo $row['nama_kategori']; // Tampilkan nama satu per satu
}
```

**Fitur pencarian:**  
Kalau ada kata pencarian, query berubah menjadi:
```sql
SELECT * FROM kategori WHERE nama_kategori LIKE '%kata_kunci%'
```
`LIKE '%kata%'` artinya "cari semua yang mengandung kata ini, di mana saja posisinya."

---

## 7. `getById()` — "Ambil Satu Data Berdasarkan Nomor"

**Analogi:** Seperti meminta pegawai "tolong ambilkan data pelanggan nomor 42 saja."

**Kapan dipakai?**
- Saat kamu klik tombol **Edit** → sistem perlu tahu data mana yang mau diedit
- Di halaman cetak invoice → sistem perlu tahu transaksi mana yang mau dicetak

**Contoh di `cetak_invoice.php`:**
```php
$id = $_GET['id']; // Ambil angka dari URL: cetak_invoice.php?id=42

$t = $transaksiObj->getById($id); // Minta data transaksi nomor 42
// $t sekarang berisi semua detail transaksi itu (nama customer, konsol, dll)

echo $t['nama_lengkap']; // Tampilkan nama customer-nya
```

---

## 8. `create()` — "Simpan Data Baru"

**Analogi:** Seperti mengisi formulir pendaftaran dan menyerahkannya ke admin. Admin kemudian memasukkan data kamu ke dalam buku besar.

**Ada di:** Semua class (`Kategori`, `Konsol`, `Customer`, `Transaksi`)

**Alur kerja create() di `Transaksi.php`** (yang paling kompleks):
```
Terima data dari form:
  - Siapa customer-nya? (id_customer)
  - Konsol apa yang disewa? (id_konsol)
  - Berapa lama? (pilihan_durasi)
  - Kapan mulai? (waktu_mulai_sewa)
        ↓
Hitung otomatis:
  - Kapan harus kembali? (hitungEstimasiKembali)
  - Berapa total harganya? (hitungHargaSewa)
        ↓
Simpan ke tabel "transaksi"
        ↓
Update status konsol → "Disewa"
        ↓
Selesai ✓
```

**Kenapa `create()` di `Customer` berbeda?**  
Karena ada proses tambahan: upload foto KTP. Jadi urutannya:
```
Upload foto KTP dulu → kalau berhasil → baru simpan data customer ke database
Kalau database-nya malah gagal → hapus foto yang sudah terupload (biar tidak ada file nyangkut)
```

---

## 9. `update()` — "Ubah Data yang Sudah Ada"

**Analogi:** Seperti mengambil formulir lama seseorang, mencoret datanya yang salah, dan menulis ulang yang benar.

**Kapan dipakai?**
- Kamu klik Edit pada kategori, ubah namanya, klik Simpan
- Kamu edit data konsol (harga, status, dll)
- Kamu perbarui data customer (nama, nomor WA, dll)

**Contoh untuk update Customer:**
```
Cek apakah ada foto KTP baru yang diupload?
  │
  ├─ ADA foto baru → Upload foto baru dulu
  │                → Hapus foto lama dari server
  │                → Simpan nama foto baru ke database
  │
  └─ TIDAK ADA → Pakai nama foto yang lama saja
        ↓
Update semua data di database dengan perintah SQL UPDATE
```

**Perbedaan `create()` vs `update()`:**
- `create()` → Pakai `INSERT INTO` (tulis data baru)
- `update()` → Pakai `UPDATE ... WHERE id = X` (ubah data yang sudah ada, targetkan berdasarkan ID)

---

## 10. `delete()` — "Hapus Data"

**Analogi:** Seperti mengambil halaman dari buku besar dan merobek/membuangnya.

**Hal penting yang perlu diketahui:**  
Tidak semua data bisa langsung dihapus! Ada aturan "siapa boleh dihapus duluan" karena data saling berkaitan.

```
Urutan yang benar untuk hapus:
  
  transaksi (harus dihapus/selesaikan dulu)
      ↑ bergantung pada
  customer ← bisa dihapus jika tidak ada transaksi aktif
  konsol   ← bisa dihapus jika tidak ada transaksi
      ↑ bergantung pada
  kategori ← bisa dihapus jika tidak ada konsol yang memakainya
```

**Contoh error yang muncul saat aturan dilanggar:**
```
"Kategori tidak bisa dihapus karena masih digunakan oleh data Konsol!"
"Customer tidak bisa dihapus karena masih memiliki riwayat Transaksi!"
```

**Penghapusan Customer juga menghapus file KTP:**
```php
// Setelah DELETE berhasil dari database...
@unlink($upload_dir . $old['foto_ktp']); // Hapus juga file fotonya dari server
```
`@unlink` = perintah PHP untuk menghapus file. Tanda `@` berarti "kalau gagal, jangan tampilkan error."

---

## 11. `uploadKTP()` — "Simpan File Foto"

**Analogi:** Seperti bagian admin yang bertugas menerima fotokopi KTP, mengeceknya, lalu menyimpannya di folder arsip.

**Ada di:** `Customer.php`

**Apa saja yang dicek sebelum foto disimpan?**

```
File foto diterima dari form
        ↓
Cek ekstensi file:
  ✅ .jpg, .jpeg, .png → lanjut
  ❌ .exe, .php, dll  → TOLAK (bahaya!)
        ↓
Cek ukuran file:
  ✅ ≤ 2 MB → lanjut
  ❌ > 2 MB → TOLAK (terlalu besar)
        ↓
Buat nama file baru yang unik:
  "ktp_1718123456_abc123def.jpg"
  (pakai timestamp + kode acak agar tidak ada nama yang sama)
        ↓
Pindahkan dari folder sementara ke folder uploads/
        ↓
Kembalikan nama file baru → disimpan ke database
```

**Kenapa nama file dibuat unik?**  
Kalau dua orang upload file dengan nama sama (`foto.jpg`), yang satu akan menimpa yang lain. Dengan nama unik seperti `ktp_1718123456_abc123.jpg`, tidak ada yang tertimpa.

---

## 12. `hitungEstimasiKembali()` — "Hitung Kapan Harus Kembali"

**Analogi:** Seperti staf rental yang melihat jam sekarang, lalu menghitung "kalau sewa 1 minggu, berarti harus kembali tanggal sekian jam sekian."

**Ada di:** `Transaksi.php`

**Cara kerjanya:**
```
Waktu mulai sewa: Senin, 10 Juni 2026, Pukul 14:00
        +
Durasi yang dipilih:
  "1 Hari"   → tambah 1 hari   → Selasa, 11 Juni 2026, 14:00
  "1 Minggu" → tambah 7 hari   → Senin,  17 Juni 2026, 14:00
  "1 Bulan"  → tambah 30 hari  → Kamis,  10 Juli 2026, 14:00
        =
Waktu Jatuh Tempo (disimpan ke database)
```

**Hasilnya dipakai untuk apa?**
- Ditampilkan di kolom "Jatuh Tempo" pada tabel transaksi
- Dipakai sebagai acuan apakah pengembalian terlambat atau tidak
- Dicetak di invoice

---

## 13. `hitungHargaSewa()` — "Hitung Total Biaya"

**Analogi:** Seperti kasir yang mengalikan harga per hari dengan jumlah hari sewa.

**Ada di:** `Transaksi.php`

**Cara kerjanya:**
```
Harga Per Hari × Jumlah Hari Sesuai Durasi

Contoh: PS5 → Rp 100.000/hari

  "1 Hari"   → 100.000 × 1  = Rp   100.000
  "1 Minggu" → 100.000 × 7  = Rp   700.000
  "1 Bulan"  → 100.000 × 30 = Rp 3.000.000
```

**Di mana hasilnya dipakai?**  
Disimpan ke kolom `harga_sewa` di tabel transaksi, dan ditampilkan di invoice.

---

## 14. `hitungDenda()` — "Hitung Denda Keterlambatan"

**Analogi:** Seperti aturan: "Telat balikin buku perpustakaan? Ada denda per hari. Tapi kami kasih toleransi 3 jam dulu."

**Ada di:** `Transaksi.php`

**Aturan lengkapnya:**
```
Waktu pengembalian aktual vs waktu seharusnya:
        ↓
Apakah sudah lewat jatuh tempo?
  BELUM → Denda = Rp 0 ✓ (tepat waktu)
        ↓
Sudah lewat. Hitung berapa jam terlambatnya...
        ↓
≤ 3 jam terlambat → Denda = Rp 0 ✓ (masih dalam toleransi)
        ↓
> 3 jam terlambat → Hitung denda:
  - Konversi jam ke hari (dibulatkan ke ATAS)
    Contoh: 25 jam → dianggap 2 hari (bukan 1 hari)
    Contoh: 48 jam → dianggap 2 hari
    Contoh: 49 jam → dianggap 3 hari
  - Denda = jumlah_hari × Rp 50.000
```

**Contoh nyata:**
```
Jatuh tempo:  Senin 10:00
Dikembalikan: Selasa 11:00 (terlambat 25 jam)

25 jam / 24 = 1,04 → dibulatkan ke atas → 2 hari
Denda = 2 × Rp 50.000 = Rp 100.000
```

**Ada dua versi hitungDenda — PHP dan JavaScript. Kenapa?**

| | PHP | JavaScript |
|---|---|---|
| **Kapan jalan** | Saat tombol "Konfirmasi" diklik | Saat kamu ubah jam di kolom waktu kembali |
| **Fungsi** | Hitung dan simpan ke database (FINAL) | Tampilkan estimasi real-time (PREVIEW) |
| **Yang terlihat pengguna** | Hasil di halaman setelah redirect | Angka di bawah form yang berubah-ubah |

Keduanya pakai logika yang **persis sama**, hanya ditulis dalam bahasa yang berbeda.

---

## 15. `prosesKembali()` — "Catat Pengembalian Konsol"

**Analogi:** Seperti staf rental yang menerima konsol yang dikembalikan, menghitung dendanya, mencatat di buku besar, dan menaruh konsol kembali ke rak.

**Ada di:** `Transaksi.php`

**Apa saja yang dilakukan dalam satu pemanggilan ini?**

```
Menerima:
  - ID transaksi mana yang selesai
  - Kapan konsol dikembalikan (waktu aktual)
  - Kapan harusnya kembali (untuk hitung denda)
  - Konsol mana (ID-nya)

Langkah 1: Hitung denda
  → panggil hitungDenda() secara otomatis

Langkah 2: Update tabel transaksi
  → Isi kolom "waktu_kembali_aktual" dengan waktu pengembalian
  → Isi kolom "total_denda" dengan hasil hitung denda
  → Ubah status_transaksi dari "Sedang Disewa" → "Selesai"

Langkah 3: Update tabel konsol
  → Ubah status konsol dari "Disewa" → "Tersedia"
  → (Konsol siap disewa lagi oleh customer berikutnya)

Kembalikan: total denda (untuk ditampilkan di notifikasi)
```

---

## 16. `countAll()` / `countTersedia()` / `countAktif()` — "Hitung Jumlah"

**Analogi:** Seperti manajer toko yang tiap pagi minta laporan singkat: "Berapa total pelanggan? Berapa stok tersisa? Berapa yang masih keluar?"

**Ada di:** Semua class, dipakai di `dashboard.php`

| Method | Di mana | Yang dihitung |
|--------|---------|---------------|
| `Customer::countAll()` | `Customer.php` | Total semua data customer |
| `Konsol::countTersedia()` | `Konsol.php` | Konsol yang statusnya "Tersedia" |
| `Kategori::countAll()` | `Kategori.php` | Total semua kategori |
| `Transaksi::countAktif()` | `Transaksi.php` | Transaksi yang masih "Sedang Disewa" |

**Semua hasilnya ditampilkan di Dashboard** sebagai kartu statistik:
```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  12 Customer │  │  8 Tersedia  │  │  4 Aktif     │  │  5 Kategori  │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
```

---

## 17. `header('Location: ...')` — "Pindahkan Halaman"

**Analogi:** Seperti resepsionis yang bilang "Anda sudah selesai di sini, silakan pindah ke ruangan sebelah."

**Kapan dipakai?**  
Selalu setelah operasi POST (simpan/ubah/hapus) selesai. Ini disebut pola **PRG (Post-Redirect-Get)**.

**Kenapa harus ada redirect setelah POST?**  
Tanpa redirect, kalau kamu me-*refresh* halaman setelah simpan data, browser akan tanya:  
*"Mau kirim ulang formulirnya?"* — dan data akan tersimpan dua kali!

**Dengan redirect:**
```
User klik Simpan
    ↓
PHP simpan data ke database [POST]
    ↓
PHP kirim perintah: "Pergi ke transaksi.php" ← header('Location:')
    ↓
Browser pindah ke transaksi.php [GET]
    ↓
User refresh halaman? → Hanya GET yang diulang → Aman, tidak ada data ganda
```

**Contoh di kode:**
```php
// Setelah simpan data berhasil:
header('Location: transaksi.php');
exit; // ← WAJIB! Hentikan semua kode PHP setelah redirect
```

> ⚠️ Kenapa ada `exit` setelah header? Karena `header()` tidak langsung menghentikan kode. Tanpa `exit`, PHP akan terus menjalankan kode di bawahnya yang tidak seharusnya jalan.

---

## 18. `$_SESSION` — "Ingat-ingatan Sementara"

**Analogi:** Seperti catatan Post-it yang ditempel di dahi kamu. Catatan itu tetap ada selama kamu berpindah dari satu ruangan ke ruangan lain, tapi bisa dicabut kapan saja setelah dibaca.

**Masalah yang dipecahkan:**  
Setelah redirect (`header('Location:...')`), halaman baru dibuka dari nol. Semua variabel PHP hilang. Lalu bagaimana cara menampilkan notifikasi "Data berhasil disimpan!" di halaman baru?

**Jawabannya: `$_SESSION`.**

```
[Di transaksi.php setelah simpan berhasil]
$_SESSION['flash'] = 'Transaksi berhasil dicatat!';
$_SESSION['flash_type'] = 'success';
header('Location: transaksi.php'); ← Pindah halaman
exit;

[Di transaksi.php yang baru dimuat]
// Baca catatan Post-it:
$flash = $_SESSION['flash'];
// Hapus catatan agar tidak muncul lagi saat refresh:
unset($_SESSION['flash'], $_SESSION['flash_type']);
// Tampilkan notifikasinya:
echo "<div class='alert alert-success'>$flash</div>";
```

**Syarat pakai `$_SESSION`:**  
Harus ada `session_start()` di baris paling atas file PHP, sebelum ada output apapun.

---

## 19. `htmlspecialchars()` — "Amankan Teks Sebelum Ditampilkan"

**Analogi:** Seperti pakaian hazmat yang kamu pakai sebelum memegang zat berbahaya — mengamankan agar tidak terkontaminasi.

**Masalah yang dipecahkan:**  
Bayangkan ada customer yang mendaftarkan nama: `<script>alert('Hacked!')</script>`.  
Kalau langsung ditampilkan ke HTML tanpa pengamanan, script itu akan **benar-benar dijalankan** oleh browser!

**Solusinya:**
```php
// Tanpa pengamanan (BERBAHAYA):
echo $nama_customer; // Bisa jalan kalau isinya script jahat

// Dengan pengamanan (AMAN):
echo htmlspecialchars($nama_customer);
// Karakter < diubah jadi &lt;, > jadi &gt;
// Browser menampilkan teksnya, bukan menjalankan sebagai kode
```

**Kamu akan lihat ini di mana-mana di sistem:**
```php
echo htmlspecialchars($row['nama_lengkap']);
echo htmlspecialchars($row['nama_konsol']);
// Semua data yang datang dari database dan ditampilkan ke HTML
// wajib diproteksi dengan htmlspecialchars()
```

---

## 20. `try...catch` — "Coba, Kalau Gagal Tangkap Errornya"

**Analogi:** Seperti mencoba membuka pintu. Kalau pintunya terbuka, masuk. Kalau pintunya terkunci, jangan panik — catat di buku bahwa "pintu nomor X terkunci" dan lanjutkan.

**Tanpa try...catch:**  
Kalau ada error database, PHP menampilkan halaman error putih yang menakutkan dan berhenti total.

**Dengan try...catch:**  
Error "ditangkap" dan ditampilkan dengan pesan yang lebih jelas dan terkontrol.

```php
try {
    // Coba jalankan query ke database
    $stmt = $this->conn->prepare("SELECT * FROM transaksi");
    $stmt->execute();

} catch (PDOException $e) {
    // Kalau gagal, jalankan ini:
    die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
    // Pesan error muncul: misalnya "Table 'transaksi' doesn't exist"
}
```

**Ada juga try...catch khusus untuk FK (Foreign Key):**
```php
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        // Kode 23000 = error "tidak bisa hapus karena data ini masih dipakai"
        throw new Exception("Kategori tidak bisa dihapus karena masih digunakan!");
    }
}
```

---

## 21. `dd()` — "Lihat Isi Variabel, Lalu Berhenti"

**Analogi:** Seperti menekan tombol pause pada video, lalu melihat frame-nya dengan teliti. Berguna banget saat debugging.

**Ada di:** `classes/Database.php` (tersedia di semua halaman secara otomatis)

**Cara pakai:**
```php
// Tambahkan sementara di kode saat debugging:
dd($_POST);   // Lihat semua data yang dikirim via form

// Hasilnya tampil seperti ini di browser:
// Array
// (
//   [action] => create
//   [id_customer] => 5
//   [id_konsol] => 3
//   [pilihan_durasi] => 1 Hari
//   [waktu_mulai_sewa] => 2026-06-19T14:00
// )
// -- lalu halaman berhenti --
```

**Kapan berguna?**
- Kamu submit form tapi data tidak tersimpan → `dd($_POST)` untuk cek apakah datanya benar terkirim
- Ada upload file yang gagal → `dd($_FILES)` untuk cek apakah filenya benar-benar terkirim

> ⚠️ **Jangan lupa hapus `dd()`** setelah selesai debugging! Karena dia menghentikan halaman dan tidak boleh ada di kode yang sudah jadi.

---

## 22. `include` — "Tempel File Lain di Sini"

**Analogi:** Seperti mencopy-paste isi satu dokumen Word ke dalam dokumen lain — isi file yang di-include langsung "muncul" di posisi baris itu.

**Dipakai untuk:** Sidebar navigasi yang sama muncul di semua halaman.

```php
// Di dashboard.php, kategori.php, transaksi.php, dll:
<?php include 'includes/sidebar.php'; ?>
```

Sama seperti `require_once`, tapi:
- `include` → kalau file tidak ketemu, hanya warning (halaman tetap jalan)
- `require_once` → kalau file tidak ketemu, halaman langsung mati (error fatal)

Untuk sidebar dipakai `include` karena walau sidebar error, konten utama halaman tetap bisa tampil.

---

## 🗺️ Ringkasan Visual — Semua Method dalam Satu Alur

```
Pengguna buka halaman (misal: transaksi.php)
              │
              ▼
         [GET Request]
              │
     require_once file-file ──────────────────────┐
              │                                    │
     new Database()                          File class:
     getConnection() ─── Sambung ke DB      Database.php
              │                              Transaksi.php
     new Transaksi($db)                      Konsol.php
              │                              Customer.php
     getAll($search) ─── Ambil data         Kategori.php
     getKonsolTersedia()
              │
     Render HTML + data ──────────── Tampilkan ke pengguna
              │
              │
Pengguna isi form dan klik Simpan
              │
              ▼
         [POST Request]
              │
     Baca $_POST['action'] ──── Tentukan mau ngapain
              │
     ┌────────┼──────────┐
     │        │          │
   create   update    delete
     │        │          │
     ▼        ▼          ▼
  INSERT   UPDATE     DELETE
  ke DB    di DB      dari DB
     │        │          │
     └────────┴──────────┘
              │
     $_SESSION['flash'] = 'Berhasil!'
              │
     header('Location: transaksi.php') ← Redirect
              │
              ▼
      [Kembali ke GET] ── Baca flash ── Tampilkan notifikasi
```

---

*Dibuat untuk membantu memahami kode sistem Rental PS — Planet Station.*  
*Jika ada yang masih membingungkan, tanyakan saja!* 🎮
