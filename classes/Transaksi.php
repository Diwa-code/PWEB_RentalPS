<?php
// =====================================================
// Class Transaksi - OOP untuk Tabel `transaksi`
// =====================================================
class Transaksi {
    private $conn;
    private $table_name = "transaksi";

    /**
     * Constructor menerima koneksi PDO dari Database::getConnection()
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ─────────────────────────────────────────────
    // LOGIKA WAKTU & DENDA
    // ─────────────────────────────────────────────

    /**
     * Hitung estimasi waktu kembali berdasarkan durasi (dalam hari).
     * @param string $waktu_mulai  Format: 'Y-m-d H:i:s'
     * @param int|string $durasi   Jumlah hari (misal: 12 atau "12 Hari")
     * @return string              Format: 'Y-m-d H:i:s'
     */
    public function hitungEstimasiKembali($waktu_mulai, $durasi) {
        $estimasi = new DateTime($waktu_mulai);
        
        // Ekstrak hanya angka dari input durasi (menghapus spasi atau kata "Hari")
        $jumlah_hari = (int) preg_replace('/[^0-9]/', '', $durasi);
        
        if ($jumlah_hari > 0) {
            $estimasi->modify("+$jumlah_hari days");
        }
        
        return $estimasi->format('Y-m-d H:i:s');
    }

    /**
     * Hitung harga sewa berdasarkan harga_per_hari dan durasi (dalam hari).
     * @param int        $harga_per_hari
     * @param int|string $durasi   Jumlah hari (misal: 12 atau "12 Hari")
     * @return int
     */
    public function hitungHargaSewa($harga_per_hari, $durasi) {
        // Ekstrak hanya angka dari input durasi
        $jumlah_hari = (int) preg_replace('/[^0-9]/', '', $durasi);
        
        return $harga_per_hari * $jumlah_hari;
    }

    /**
     * Jalankan logika denda dengan toleransi 3 jam.
     * Jika keterlambatan <= 3 jam => denda = 0
     * Jika > 3 jam => denda = ceil(total_jam / 24) * Rp50.000
     *
     * @param string $waktu_seharusnya  Format: 'Y-m-d H:i:s'
     * @param string $waktu_aktual      Format: 'Y-m-d H:i:s'
     * @return int   Total denda dalam rupiah
     */
    public function hitungDenda($waktu_seharusnya, $waktu_aktual) {
        $seharusnya = new DateTime($waktu_seharusnya);
        $aktual     = new DateTime($waktu_aktual);

        if ($aktual <= $seharusnya) return 0;

        $interval = $seharusnya->diff($aktual);
        $total_jam_terlambat = ($interval->days * 24) + $interval->h;

        // Toleransi keterlambatan 3 jam
        if ($total_jam_terlambat <= 3) {
            return 0;
        }

        // Perhitungan denda harian (pembulatan ke atas) jika lewat dari 3 jam
        $hari_terlambat    = ceil($total_jam_terlambat / 24);
        $tarif_denda_per_hari = 50000;

        return $hari_terlambat * $tarif_denda_per_hari;
    }

    // ─────────────────────────────────────────────
    // CRUD & QUERY
    // ─────────────────────────────────────────────

    /**
     * Ambil semua data transaksi dengan JOIN ke customer dan konsol.
     * Opsional filter pencarian by nama customer atau nama konsol.
     * @param string $search
     * @return array
     */
    public function getAll($search = '') {
        try {
            $base = "SELECT t.*,
                            c.nama_lengkap, c.no_wa, c.foto_ktp,
                            k.nama_konsol,  k.harga_per_hari
                     FROM {$this->table_name} t
                     LEFT JOIN customer c ON t.id_customer = c.id_customer
                     LEFT JOIN konsol   k ON t.id_konsol   = k.id_konsol";

            if (!empty($search)) {
                $query = $base . " WHERE c.nama_lengkap LIKE :search
                                       OR k.nama_konsol   LIKE :search2
                                   ORDER BY t.id_transaksi DESC";
                $stmt = $this->conn->prepare($query);
                $searchParam = "%" . $search . "%";
                $stmt->bindParam(':search',  $searchParam);
                $stmt->bindParam(':search2', $searchParam);
            } else {
                $query = $base . " ORDER BY t.id_transaksi DESC";
                $stmt  = $this->conn->prepare($query);
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil satu transaksi berdasarkan ID (dengan JOIN).
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $query = "SELECT t.*,
                             c.nama_lengkap, c.no_wa, c.alamat, c.foto_ktp,
                             k.nama_konsol,  k.harga_per_hari, k.status as status_konsol
                      FROM {$this->table_name} t
                      LEFT JOIN customer c ON t.id_customer = c.id_customer
                      LEFT JOIN konsol   k ON t.id_konsol   = k.id_konsol
                      WHERE t.id_transaksi = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Catat transaksi sewa baru.
     * Secara otomatis: menghitung waktu_seharusnya_kembali & harga_sewa,
     * lalu mengubah status konsol menjadi 'Disewa'.
     * @param array $data ['id_customer', 'id_konsol', 'pilihan_durasi', 'waktu_mulai_sewa', 'harga_per_hari']
     * @return bool
     */
    public function create($data) {
        try {
            $waktu_seharusnya = $this->hitungEstimasiKembali(
                $data['waktu_mulai_sewa'],
                $data['pilihan_durasi']
            );
            $harga_sewa = $this->hitungHargaSewa(
                $data['harga_per_hari'],
                $data['pilihan_durasi']
            );

            // Insert transaksi
            $query = "INSERT INTO {$this->table_name}
                        (id_customer, id_konsol, pilihan_durasi, harga_sewa,
                         waktu_mulai_sewa, waktu_seharusnya_kembali, status_transaksi)
                      VALUES
                        (:id_customer, :id_konsol, :pilihan_durasi, :harga_sewa,
                         :waktu_mulai_sewa, :waktu_seharusnya_kembali, 'Sedang Disewa')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_customer',            $data['id_customer'],    PDO::PARAM_INT);
            $stmt->bindParam(':id_konsol',              $data['id_konsol'],      PDO::PARAM_INT);
            $stmt->bindParam(':pilihan_durasi',         $data['pilihan_durasi']);
            $stmt->bindParam(':harga_sewa',             $harga_sewa,             PDO::PARAM_INT);
            $stmt->bindParam(':waktu_mulai_sewa',       $data['waktu_mulai_sewa']);
            $stmt->bindParam(':waktu_seharusnya_kembali', $waktu_seharusnya);
            $stmt->execute();

            // Update status konsol → Disewa
            $upd = $this->conn->prepare(
                "UPDATE konsol SET status = 'Disewa' WHERE id_konsol = :id"
            );
            $upd->bindParam(':id', $data['id_konsol'], PDO::PARAM_INT);
            $upd->execute();

            return true;
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Proses pengembalian konsol.
     * Menghitung denda otomatis, memperbarui status transaksi → Selesai,
     * dan mengubah status konsol kembali → Tersedia.
     * @param int    $id_transaksi
     * @param string $waktu_kembali_aktual  Format: 'Y-m-d H:i:s'
     * @param string $waktu_seharusnya
     * @param int    $id_konsol
     * @return int   Total denda
     */
    public function prosesKembali($id_transaksi, $waktu_kembali_aktual, $waktu_seharusnya, $id_konsol) {
        try {
            $total_denda = $this->hitungDenda($waktu_seharusnya, $waktu_kembali_aktual);

            // Update transaksi
            $query = "UPDATE {$this->table_name}
                      SET waktu_kembali_aktual = :waktu_aktual,
                          total_denda          = :total_denda,
                          status_transaksi     = 'Selesai'
                      WHERE id_transaksi = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':waktu_aktual', $waktu_kembali_aktual);
            $stmt->bindParam(':total_denda',  $total_denda, PDO::PARAM_INT);
            $stmt->bindParam(':id',           $id_transaksi, PDO::PARAM_INT);
            $stmt->execute();

            // Kembalikan status konsol → Tersedia
            $upd = $this->conn->prepare(
                "UPDATE konsol SET status = 'Tersedia' WHERE id_konsol = :id"
            );
            $upd->bindParam(':id', $id_konsol, PDO::PARAM_INT);
            $upd->execute();

            return $total_denda;
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hitung total transaksi aktif (untuk dashboard).
     * @return int
     */
    public function countAktif() {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE status_transaksi = 'Sedang Disewa'";
            $stmt  = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return (int) $row['total'];
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil semua customer untuk dropdown form sewa.
     * @return array
     */
    public function getAllCustomer() {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id_customer, nama_lengkap, no_wa, alamat, foto_ktp
                 FROM customer ORDER BY nama_lengkap ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil semua konsol yang berstatus Tersedia untuk dropdown form sewa.
     * @return array
     */
    public function getKonsolTersedia() {
        try {
            $stmt = $this->conn->prepare(
                "SELECT k.id_konsol, k.nama_konsol, k.harga_per_hari, kt.nama_kategori
                 FROM konsol k
                 LEFT JOIN kategori kt ON k.id_kategori = kt.id_kategori
                 WHERE k.status = 'Tersedia'
                 ORDER BY k.nama_konsol ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }
}
?>