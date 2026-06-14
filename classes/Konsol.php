<?php
// =====================================================
// Class Konsol - OOP CRUD untuk Tabel `konsol`
// =====================================================
class Konsol {
    private $conn;
    private $table = "konsol";

    /**
     * Constructor menerima koneksi PDO dari Database::getConnection()
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Ambil semua data konsol dengan JOIN ke tabel kategori.
     * Opsional filter pencarian by nama_konsol atau nama_kategori.
     * @param string $search
     * @return PDOStatement
     */
    public function getAll($search = '') {
        try {
            $base = "SELECT k.*, kt.nama_kategori
                     FROM {$this->table} k
                     LEFT JOIN kategori kt ON k.id_kategori = kt.id_kategori";

            if (!empty($search)) {
                $query = $base . " WHERE k.nama_konsol LIKE :search
                                    OR kt.nama_kategori LIKE :search2
                                   ORDER BY k.id_konsol ASC";
                $stmt = $this->conn->prepare($query);
                $searchParam = "%" . $search . "%";
                $stmt->bindParam(':search',  $searchParam);
                $stmt->bindParam(':search2', $searchParam);
            } else {
                $query = $base . " ORDER BY k.id_konsol ASC";
                $stmt = $this->conn->prepare($query);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil satu data konsol berdasarkan ID (dengan nama kategori).
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $query = "SELECT k.*, kt.nama_kategori
                      FROM {$this->table} k
                      LEFT JOIN kategori kt ON k.id_kategori = kt.id_kategori
                      WHERE k.id_konsol = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Tambah data konsol baru.
     * @param array $data ['id_kategori', 'nama_konsol', 'harga_per_hari', 'status']
     * @return bool
     */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table}
                        (id_kategori, nama_konsol, harga_per_hari, status)
                      VALUES
                        (:id_kategori, :nama_konsol, :harga_per_hari, :status)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_kategori',   $data['id_kategori'],   PDO::PARAM_INT);
            $stmt->bindParam(':nama_konsol',   $data['nama_konsol']);
            $stmt->bindParam(':harga_per_hari',$data['harga_per_hari'],PDO::PARAM_INT);
            $stmt->bindParam(':status',        $data['status']);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Update data konsol berdasarkan ID.
     * @param int   $id
     * @param array $data ['id_kategori', 'nama_konsol', 'harga_per_hari', 'status']
     * @return bool
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE {$this->table}
                      SET id_kategori    = :id_kategori,
                          nama_konsol    = :nama_konsol,
                          harga_per_hari = :harga_per_hari,
                          status         = :status
                      WHERE id_konsol = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_kategori',   $data['id_kategori'],   PDO::PARAM_INT);
            $stmt->bindParam(':nama_konsol',   $data['nama_konsol']);
            $stmt->bindParam(':harga_per_hari',$data['harga_per_hari'],PDO::PARAM_INT);
            $stmt->bindParam(':status',        $data['status']);
            $stmt->bindParam(':id',            $id,                    PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hapus data konsol berdasarkan ID.
     * Akan gagal jika masih ada transaksi aktif (FK RESTRICT).
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id_konsol = :id";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Konsol tidak bisa dihapus karena masih terkait dengan data Transaksi!");
            }
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Update status konsol (Tersedia / Disewa / Maintenance).
     * @param int    $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status) {
        try {
            $query = "UPDATE {$this->table} SET status = :status WHERE id_konsol = :id";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hitung total konsol tersedia (untuk dashboard).
     * @return int
     */
    public function countTersedia() {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'Tersedia'";
            $stmt  = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return (int) $row['total'];
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil semua kategori untuk dropdown form.
     * @return array
     */
    public function getAllKategori() {
        try {
            $query = "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC";
            $stmt  = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }
}
?>
