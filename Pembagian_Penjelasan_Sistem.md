# Penjelasan Sistem Rental PS Berbasis Konsep MVC

Dokumen ini dibuat sebagai bahan pembagian penjelasan untuk kelompok berisi 4 orang. Sistem yang dijelaskan adalah aplikasi penyewaan konsol game berbasis PHP Native OOP dan MySQL.

Secara teknis, project ini belum memakai framework MVC seperti Laravel atau CodeIgniter. Namun alur programnya bisa dijelaskan menggunakan konsep MVC karena sudah ada pemisahan peran:

- Model: file class di folder `classes/`, misalnya `Customer.php`, `Konsol.php`, `Kategori.php`, dan `Transaksi.php`.
- View: tampilan HTML, Bootstrap, tabel, modal, form, dan elemen interface pada file di folder `pages/`, `index.php`, dan `cetak_invoice.php`.
- Controller: logika penerima request `GET` dan `POST` yang berada di bagian atas file halaman PHP, misalnya `pages/customer.php` dan `pages/transaksi.php`.

## Pembagian Materi Kelompok

| Anggota | Bagian Penjelasan | Fokus Utama |
| --- | --- | --- |
| Anggota 1 | Interface Sistem | Tampilan, menu, halaman, dan alur penggunaan |
| Anggota 2 | Fungsional dan Non-Fungsional | Fitur sistem dan kualitas sistem |
| Anggota 3 | Backend dan Koneksi Database | Class, proses server, query, validasi, PDO |
| Anggota 4 | Frontend, Root File, dan MVC Flow | Struktur file, asset, root file, alur frontend ke backend |

---

# 1. Bagian Anggota 1: Interface Sistem

## Gambaran Umum Interface

Interface sistem Rental PS digunakan oleh admin atau kasir rental untuk mengelola data penyewaan konsol. Tampilan dibuat berbasis Bootstrap, Bootstrap Icons, CSS custom, tabel data, tombol aksi, form modal, dan sidebar navigasi.

Tujuan utama interface adalah membuat admin bisa:

- Melihat ringkasan data pada dashboard.
- Mengelola data kategori konsol.
- Mengelola data konsol.
- Mengelola data customer.
- Membuat transaksi sewa baru.
- Memproses pengembalian konsol.
- Mencetak invoice atau struk transaksi.

## Struktur Menu Utama

Navigasi utama berada di `includes/sidebar.php`. Sidebar menyediakan akses ke:

- Dashboard: `index.php`
- Master Kategori: `pages/kategori.php`
- Master Konsol: `pages/konsol.php`
- Master Customer: `pages/customer.php`
- Data Sewa atau Transaksi: `pages/transaksi.php`

Setiap halaman mengatur variabel `$current_page` agar sidebar bisa menandai menu yang sedang aktif.

## Halaman Dashboard

File utama dashboard adalah `index.php`.

Dashboard berfungsi sebagai halaman ringkasan sistem. Admin dapat melihat gambaran cepat seperti:

- Total customer.
- Total kategori.
- Total konsol tersedia.
- Total transaksi aktif.

Dashboard juga menyediakan shortcut menuju halaman master data dan transaksi.

## Halaman Master Kategori

File: `pages/kategori.php`

Halaman ini digunakan untuk mengelola kategori konsol, misalnya PlayStation, Nintendo, atau kategori lain. Interface yang tersedia:

- Tabel daftar kategori.
- Form pencarian kategori.
- Tombol tambah kategori.
- Tombol edit kategori.
- Tombol hapus kategori.
- Modal form untuk tambah dan edit.
- Modal konfirmasi untuk hapus.

## Halaman Master Konsol

File: `pages/konsol.php`

Halaman ini digunakan untuk mengelola unit konsol yang disewakan. Data yang ditampilkan meliputi:

- Nama konsol.
- Kategori konsol.
- Harga sewa per hari.
- Status konsol, yaitu `Tersedia`, `Disewa`, atau `Maintenance`.

Interface halaman konsol menyediakan:

- Tabel daftar konsol.
- Form pencarian nama konsol atau kategori.
- Tombol tambah konsol.
- Tombol edit konsol.
- Tombol hapus konsol.
- Badge status agar kondisi konsol mudah dibaca.

## Halaman Master Customer

File: `pages/customer.php`

Halaman customer digunakan untuk menyimpan data pelanggan rental. Data yang dikelola:

- Nama lengkap.
- Nomor WhatsApp.
- Alamat.
- Foto KTP sebagai jaminan.

Interface halaman customer menyediakan:

- Tabel daftar customer.
- Link WhatsApp langsung dari nomor customer.
- Upload foto KTP.
- Preview foto KTP.
- Modal lihat foto KTP ukuran besar.
- Form tambah dan edit customer.
- Validasi nomor WhatsApp pada form.

## Halaman Data Sewa atau Transaksi

File: `pages/transaksi.php`

Halaman transaksi digunakan untuk mencatat penyewaan dan pengembalian konsol. Interface yang tersedia:

- Tabel riwayat transaksi.
- Tombol `Sewa Baru`.
- Modal form sewa baru.
- Dropdown customer.
- Dropdown konsol yang tersedia.
- Pilihan durasi sewa.
- Perhitungan harga sewa.
- Waktu mulai sewa.
- Modal proses pengembalian.
- Preview denda keterlambatan.
- Tombol cetak invoice.

## Halaman Invoice

File: `cetak_invoice.php`

Halaman invoice digunakan untuk menampilkan bukti transaksi. Informasi yang ditampilkan:

- Data customer.
- Nomor WhatsApp.
- Konsol yang disewa.
- Durasi sewa.
- Waktu mulai.
- Waktu seharusnya kembali.
- Waktu kembali aktual jika transaksi selesai.
- Harga sewa.
- Total denda.
- Total pembayaran.

Invoice dapat dicetak melalui fitur print browser.

---

# 2. Bagian Anggota 2: Fungsional dan Non-Fungsional Sistem

## Kebutuhan Fungsional

Kebutuhan fungsional adalah fitur yang secara langsung bisa digunakan oleh admin.

## F-01: Dashboard Ringkasan

Sistem dapat menampilkan data ringkasan untuk membantu admin melihat kondisi rental secara cepat.

Contoh data:

- Jumlah customer.
- Jumlah kategori.
- Jumlah konsol tersedia.
- Jumlah transaksi aktif.

## F-02: CRUD Kategori

Sistem dapat mengelola data kategori konsol.

Fitur:

- Tambah kategori.
- Tampilkan kategori.
- Cari kategori.
- Edit kategori.
- Hapus kategori.

## F-03: CRUD Konsol

Sistem dapat mengelola data konsol.

Fitur:

- Tambah konsol.
- Tampilkan konsol.
- Cari konsol berdasarkan nama atau kategori.
- Edit konsol.
- Hapus konsol.
- Mengatur status konsol.
- Menentukan harga sewa per hari.

## F-04: CRUD Customer

Sistem dapat mengelola data customer.

Fitur:

- Tambah customer.
- Tampilkan customer.
- Cari customer berdasarkan nama atau nomor WhatsApp.
- Edit customer.
- Hapus customer.
- Upload foto KTP.
- Preview foto KTP.
- Validasi nomor WhatsApp.

## F-05: Transaksi Sewa

Sistem dapat mencatat transaksi penyewaan konsol.

Alur utama:

1. Admin memilih customer.
2. Admin memilih konsol yang masih tersedia.
3. Admin memilih durasi sewa.
4. Sistem menghitung harga sewa.
5. Sistem menghitung waktu seharusnya kembali.
6. Sistem menyimpan transaksi.
7. Status konsol berubah menjadi `Disewa`.

## F-06: Pengembalian Konsol

Sistem dapat memproses pengembalian konsol.

Alur utama:

1. Admin memilih transaksi yang masih aktif.
2. Admin mengisi waktu kembali aktual.
3. Sistem menghitung denda jika terlambat.
4. Status transaksi berubah menjadi `Selesai`.
5. Status konsol berubah kembali menjadi `Tersedia`.

## F-07: Hitung Denda Otomatis

Sistem menghitung denda berdasarkan waktu pengembalian aktual.

Aturan denda:

- Jika kembali tepat waktu atau lebih awal, denda Rp 0.
- Jika terlambat maksimal 3 jam, denda Rp 0.
- Jika terlambat lebih dari 3 jam, denda dihitung Rp 50.000 per hari.

Logika utama berada di method `hitungDenda()` pada `classes/Transaksi.php`.

## F-08: Cetak Invoice

Sistem dapat menampilkan dan mencetak invoice transaksi. Invoice menjadi bukti penyewaan dan pembayaran.

## Kebutuhan Non-Fungsional

Kebutuhan non-fungsional menjelaskan kualitas sistem, bukan fitur utama.

## NF-01: Keamanan Input

Sistem menggunakan validasi input pada sisi server dan sisi frontend.

Contoh:

- Field wajib tidak boleh kosong.
- Harga sewa harus angka lebih dari 0.
- Status konsol harus sesuai pilihan yang valid.
- Nomor WhatsApp harus menggunakan format yang valid.
- Upload KTP hanya menerima file `.jpg`, `.jpeg`, atau `.png`.
- Ukuran upload KTP dibatasi maksimal 2 MB.

## NF-02: Keamanan Database

Query database menggunakan PDO prepared statement. Hal ini membantu mencegah SQL Injection karena input user tidak langsung digabungkan ke query SQL.

Contoh penerapan ada pada:

- `classes/Customer.php`
- `classes/Kategori.php`
- `classes/Konsol.php`
- `classes/Transaksi.php`

## NF-03: Kemudahan Penggunaan

Interface dibuat agar mudah digunakan oleh admin.

Contoh:

- Sidebar untuk navigasi.
- Tabel untuk melihat data.
- Search bar untuk mencari data.
- Modal agar input tidak perlu pindah halaman.
- Badge status untuk membedakan status konsol dan transaksi.
- Flash message untuk menampilkan hasil aksi.

## NF-04: Responsif

Tampilan menggunakan Bootstrap dan CSS custom sehingga halaman lebih mudah menyesuaikan layar desktop maupun layar yang lebih kecil.

## NF-05: Konsistensi

Setiap halaman master data memiliki pola yang mirip:

- Ambil data.
- Tampilkan tabel.
- Tambah data melalui modal.
- Edit data melalui modal.
- Hapus data melalui modal konfirmasi.
- Tampilkan flash message setelah aksi.

## NF-06: Maintainability

Kode lebih mudah dirawat karena logika database dipisahkan ke dalam class.

Contoh:

- Logika customer berada di `classes/Customer.php`.
- Logika konsol berada di `classes/Konsol.php`.
- Logika kategori berada di `classes/Kategori.php`.
- Logika transaksi berada di `classes/Transaksi.php`.
- Koneksi database berada di `classes/Database.php`.

## NF-07: Error Handling

Sistem mengaktifkan error reporting pada tahap development melalui `classes/Database.php`. Operasi database juga dibungkus dengan `try-catch` pada banyak method class.

---

# 3. Bagian Anggota 3: Backend dan Koneksi Database

## Gambaran Backend

Backend sistem dibuat menggunakan PHP Native dengan konsep Object-Oriented Programming. Backend bertugas untuk:

- Menerima request dari form.
- Memvalidasi input.
- Menghubungkan aplikasi dengan database.
- Menjalankan query.
- Mengembalikan data ke halaman.
- Menjalankan proses bisnis seperti hitung harga, hitung estimasi kembali, dan hitung denda.

## Folder Backend

Folder utama backend adalah `classes/`.

Isi folder:

- `Database.php`: membuat koneksi database.
- `Kategori.php`: operasi CRUD tabel kategori.
- `Konsol.php`: operasi CRUD tabel konsol.
- `Customer.php`: operasi CRUD tabel customer dan upload KTP.
- `Transaksi.php`: operasi transaksi, hitung harga, hitung denda, dan update status konsol.

## Koneksi Database

Koneksi database berada di file `classes/Database.php`.

Konfigurasi koneksi:

```php
private $host     = "127.0.0.1";
private $db_name  = "db_rental_ps";
private $username = "root";
private $password = "";
```

Method utama:

```php
public function getConnection()
```

Method tersebut membuat object PDO:

```php
new PDO(
    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
    $this->username,
    $this->password
);
```

PDO diberi konfigurasi:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`

Artinya jika query gagal, PDO akan menghasilkan exception. Data hasil query juga dikembalikan dalam bentuk array associative.

## Struktur Database

Database utama bernama `db_rental_ps`.

Tabel penting:

## Tabel `kategori`

Menyimpan kategori konsol.

Kolom utama:

- `id_kategori`
- `nama_kategori`

## Tabel `konsol`

Menyimpan data unit konsol.

Kolom utama:

- `id_konsol`
- `id_kategori`
- `nama_konsol`
- `harga_per_hari`
- `status`

Status konsol:

- `Tersedia`
- `Disewa`
- `Maintenance`

## Tabel `customer`

Menyimpan data pelanggan.

Kolom utama:

- `id_customer`
- `nama_lengkap`
- `no_wa`
- `alamat`
- `foto_ktp`

## Tabel `transaksi`

Menyimpan data penyewaan.

Kolom utama:

- `id_transaksi`
- `id_customer`
- `id_konsol`
- `pilihan_durasi`
- `harga_sewa`
- `waktu_mulai_sewa`
- `waktu_seharusnya_kembali`
- `waktu_kembali_aktual`
- `total_denda`
- `status_transaksi`

## Relasi Database

Relasi utama:

- Satu kategori dapat memiliki banyak konsol.
- Satu customer dapat memiliki banyak transaksi.
- Satu konsol dapat muncul di banyak transaksi dalam waktu berbeda.
- Transaksi menyimpan relasi ke customer dan konsol.

Secara sederhana:

```text
kategori 1 ---- * konsol
customer 1 ---- * transaksi
konsol   1 ---- * transaksi
```

## Proses Backend Pada CRUD

Contoh alur CRUD pada halaman customer:

1. User mengisi form tambah customer.
2. Form dikirim dengan method `POST` ke `pages/customer.php`.
3. Bagian controller membaca `$_POST['action']`.
4. Jika action adalah `create`, data divalidasi.
5. File KTP diperiksa.
6. Controller memanggil `$customerObj->create($data, $file)`.
7. Method `create()` pada `classes/Customer.php` menjalankan query `INSERT`.
8. Sistem menyimpan flash message ke session.
9. Sistem redirect kembali ke halaman customer.
10. Halaman menampilkan pesan berhasil atau gagal.

## Proses Backend Pada Transaksi Sewa

Alur backend transaksi sewa:

1. Admin membuka `pages/transaksi.php`.
2. Sistem mengambil data customer dan konsol tersedia.
3. Admin memilih customer, konsol, durasi, dan waktu mulai.
4. Form dikirim dengan `POST`.
5. Controller memvalidasi input.
6. Controller mengambil harga konsol.
7. Controller memanggil `Transaksi::create($data)`.
8. Backend menghitung estimasi kembali.
9. Backend menghitung harga sewa.
10. Backend menyimpan transaksi baru.
11. Backend mengubah status konsol menjadi `Disewa`.

## Proses Backend Pada Pengembalian

Alur backend pengembalian:

1. Admin klik proses kembali pada transaksi aktif.
2. Modal menampilkan detail transaksi.
3. Admin mengisi waktu kembali aktual.
4. Form dikirim ke `pages/transaksi.php`.
5. Controller memanggil `Transaksi::prosesKembali()`.
6. Backend menghitung denda melalui `hitungDenda()`.
7. Backend mengubah transaksi menjadi `Selesai`.
8. Backend mengubah status konsol menjadi `Tersedia`.

## Prepared Statement

Prepared statement digunakan agar query lebih aman.

Contoh konsep:

```php
$stmt = $this->conn->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
```

Input user tidak langsung ditempel ke query, tetapi dimasukkan melalui parameter.

## Session dan Flash Message

Setelah operasi `POST`, sistem menyimpan pesan ke `$_SESSION`.

Contoh:

```php
$_SESSION['flash'] = $flash;
$_SESSION['flash_type'] = $flash_type;
```

Setelah itu sistem redirect kembali ke halaman yang sama. Pola ini disebut PRG atau Post-Redirect-Get. Tujuannya agar form tidak terkirim ulang saat browser di-refresh.

---

# 4. Bagian Anggota 4: Frontend, Root File, dan MVC Flow

## Struktur Frontend

Frontend sistem dibangun menggunakan:

- HTML untuk struktur halaman.
- CSS custom pada `assets/style.css`.
- Bootstrap untuk layout, modal, tombol, tabel, alert, dan komponen UI.
- Bootstrap Icons untuk ikon.
- JavaScript untuk interaksi modal, preview file, kalkulasi live, debounce search, dan pengisian data form edit.

## File Frontend Penting

## `assets/style.css`

File ini menyimpan style custom sistem, seperti:

- Warna tema.
- Layout sidebar.
- Layout konten utama.
- Style tabel.
- Badge status.
- Button action.
- Empty state.
- Tampilan responsif.

## `includes/sidebar.php`

File ini adalah komponen navigasi yang dipakai ulang di banyak halaman.

Kegunaannya:

- Menampilkan daftar menu.
- Menentukan menu aktif berdasarkan `$current_page`.
- Mengurangi pengulangan kode navigasi pada setiap halaman.

## JavaScript Pada Halaman

JavaScript digunakan langsung pada file halaman PHP.

Contoh fungsi frontend:

- Membuka modal edit dan mengisi input berdasarkan data baris.
- Menampilkan preview foto KTP sebelum upload.
- Menampilkan foto KTP ukuran besar.
- Mengisi waktu mulai sewa secara otomatis.
- Menghitung preview denda pengembalian secara live.
- Menjalankan debounce search.

## Root File Project

Root file adalah file yang berada di folder utama project, bukan di folder `pages/` atau `classes/`.

Root file penting:

- `index.php`: dashboard utama.
- `cetak_invoice.php`: halaman cetak struk atau invoice.
- `README.md`: dokumentasi spesifikasi awal.
- `SYSTEM_DOC.md`: dokumentasi teknis sistem.
- `PANDUAN_METHOD.md`: panduan penjelasan method.

Folder penting:

- `classes/`: model dan koneksi database.
- `pages/`: halaman CRUD dan transaksi.
- `includes/`: komponen yang dipakai ulang, seperti sidebar.
- `assets/`: file CSS.
- `uploads/`: tempat penyimpanan foto KTP.

## Konsep MVC Pada Sistem

Walaupun sistem ini PHP Native, penjelasan MVC dapat dibuat seperti berikut.

## Model

Model adalah bagian yang berhubungan dengan data dan database.

Pada sistem ini model berada di folder `classes/`.

Contoh:

- `Database.php`: koneksi database.
- `Customer.php`: data customer.
- `Kategori.php`: data kategori.
- `Konsol.php`: data konsol.
- `Transaksi.php`: data transaksi dan logika penyewaan.

Tugas model:

- Menjalankan query.
- Mengambil data.
- Menambah data.
- Mengubah data.
- Menghapus data.
- Menyimpan logika bisnis.

## View

View adalah bagian yang dilihat user.

Pada sistem ini view berupa HTML yang berada di:

- `index.php`
- `pages/kategori.php`
- `pages/konsol.php`
- `pages/customer.php`
- `pages/transaksi.php`
- `cetak_invoice.php`
- `includes/sidebar.php`

Tugas view:

- Menampilkan tabel.
- Menampilkan form.
- Menampilkan modal.
- Menampilkan tombol.
- Menampilkan alert.
- Menampilkan invoice.

## Controller

Controller adalah bagian yang mengatur request dari user.

Pada sistem ini controller belum dipisahkan ke file khusus. Controller berada di bagian atas setiap file halaman.

Contoh:

- Pada `pages/customer.php`, bagian atas file membaca `$_SERVER['REQUEST_METHOD']`.
- Jika method adalah `POST`, sistem membaca `$_POST['action']`.
- Controller menentukan apakah action adalah `create`, `update`, atau `delete`.
- Controller memanggil method pada model.
- Controller menyimpan flash message dan melakukan redirect.

## Alur Frontend ke Backend Dengan Konsep MVC

Contoh alur saat menambah customer:

```text
User mengisi form tambah customer
        |
        v
View: form pada pages/customer.php
        |
        v
Controller: handler POST pada bagian atas pages/customer.php
        |
        v
Model: Customer::create($data, $file)
        |
        v
Database: INSERT INTO customer
        |
        v
Controller: simpan flash message dan redirect
        |
        v
View: halaman customer menampilkan data terbaru
```

Contoh alur saat membuat transaksi sewa:

```text
User klik Sewa Baru dan mengisi form
        |
        v
View: modal sewa baru pada pages/transaksi.php
        |
        v
Controller: handler POST action create
        |
        v
Model: Transaksi::create($data)
        |
        v
Database:
- INSERT transaksi
- UPDATE status konsol menjadi Disewa
        |
        v
Controller: flash message dan redirect
        |
        v
View: tabel transaksi menampilkan transaksi baru
```

Contoh alur saat proses pengembalian:

```text
User klik Proses Kembali
        |
        v
View: modal pengembalian pada pages/transaksi.php
        |
        v
Controller: handler POST action kembali
        |
        v
Model: Transaksi::prosesKembali()
        |
        v
Database:
- UPDATE transaksi menjadi Selesai
- UPDATE status konsol menjadi Tersedia
        |
        v
Controller: flash message dan redirect
        |
        v
View: tabel transaksi menampilkan status terbaru
```

## Hubungan Frontend dan Backend

Frontend mengirim data ke backend melalui form HTML.

Contoh:

- Form tambah customer mengirim `nama_lengkap`, `no_wa`, `alamat`, dan `foto_ktp`.
- Form tambah konsol mengirim `id_kategori`, `nama_konsol`, `harga_per_hari`, dan `status`.
- Form sewa baru mengirim `id_customer`, `id_konsol`, `pilihan_durasi`, dan `waktu_mulai_sewa`.

Backend menerima data tersebut melalui:

- `$_POST` untuk data teks.
- `$_FILES` untuk upload file.
- `$_GET` untuk pencarian dan parameter invoice.
- `$_SESSION` untuk flash message.

Setelah backend selesai memproses, hasilnya dikirim kembali ke frontend dalam bentuk:

- Tabel data.
- Alert sukses atau gagal.
- Badge status.
- Invoice.
- Nilai pada dropdown atau modal.

## Kesimpulan MVC

Sistem ini bisa dijelaskan sebagai PHP Native dengan pendekatan MVC sederhana:

- Model berada di `classes/`.
- View berada di halaman PHP dan komponen tampilan.
- Controller berada di handler request pada bagian atas halaman.

Keuntungan pendekatan ini:

- Alur program lebih mudah dipahami.
- Query database tidak bercampur terlalu banyak dengan tampilan.
- Fitur CRUD lebih mudah dikembangkan.
- Sistem lebih mudah dijelaskan saat presentasi.

---

# Ringkasan Presentasi Per Anggota

## Anggota 1

Menjelaskan tampilan sistem:

- Dashboard.
- Sidebar.
- Halaman kategori.
- Halaman konsol.
- Halaman customer.
- Halaman transaksi.
- Invoice.

## Anggota 2

Menjelaskan fitur dan kualitas sistem:

- CRUD data master.
- Transaksi sewa.
- Pengembalian.
- Hitung denda.
- Cetak invoice.
- Validasi input.
- Keamanan query.
- Responsif dan kemudahan penggunaan.

## Anggota 3

Menjelaskan backend:

- Folder `classes/`.
- OOP pada PHP.
- Koneksi PDO.
- Struktur tabel database.
- Prepared statement.
- Session flash.
- Proses transaksi dan pengembalian.

## Anggota 4

Menjelaskan frontend, root file, dan MVC:

- `assets/style.css`.
- `includes/sidebar.php`.
- `index.php`.
- `cetak_invoice.php`.
- Folder root project.
- Alur frontend ke backend.
- Pemetaan Model, View, dan Controller.

