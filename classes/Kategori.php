<?php
// =====================================================
// Class Kategori - OOP CRUD untuk Tabel `kategori`
// =====================================================
class Kategori {
    private $conn;
    private $table = "kategori";

    /**
     * Constructor menerima koneksi PDO dari Database::getConnection()
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Ambil semua data kategori, dengan opsional filter pencarian.
     * @param string $search Kata kunci pencarian (nama_kategori)
     * @return PDOStatement
     */
    public function getAll($search = '') {
        try {
            if (!empty($search)) {
                $query = "SELECT * FROM {$this->table}
                          WHERE nama_kategori LIKE :search
                          ORDER BY id_kategori ASC";
                $stmt = $this->conn->prepare($query);
                $searchParam = "%" . $search . "%";
                $stmt->bindParam(':search', $searchParam);
            } else {
                $query = "SELECT * FROM {$this->table} ORDER BY id_kategori ASC";
                $stmt = $this->conn->prepare($query);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil satu data kategori berdasarkan ID.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id_kategori = :id LIMIT 1";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Tambah data kategori baru.
     * @param string $nama_kategori
     * @return bool
     */
    public function create($nama_kategori) {
        try {
            $query = "INSERT INTO {$this->table} (nama_kategori) VALUES (:nama_kategori)";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':nama_kategori', $nama_kategori);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Update data kategori berdasarkan ID.
     * @param int    $id
     * @param string $nama_kategori
     * @return bool
     */
    public function update($id, $nama_kategori) {
        try {
            $query = "UPDATE {$this->table}
                      SET nama_kategori = :nama_kategori
                      WHERE id_kategori = :id";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':nama_kategori', $nama_kategori);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hapus data kategori berdasarkan ID.
     * Akan gagal jika masih ada konsol yang memakai kategori ini (FK RESTRICT).
     * @param int $id
     * @return bool
     * @throws PDOException jika ada FK constraint violation
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id_kategori = :id";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Error code 23000 = FK constraint violation
            if ($e->getCode() == 23000) {
                throw new Exception("Kategori tidak bisa dihapus karena masih digunakan oleh data Konsol!");
            }
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hitung total jumlah kategori (untuk dashboard).
     * @return int
     */
    public function countAll() {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt  = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch();
            return (int) $row['total'];
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }
}
?>
