<?php
// =====================================================
// Class Customer - OOP CRUD untuk Tabel `customer`
// =====================================================
class Customer {
    private $conn;
    private $table      = "customer";
    private $upload_dir = __DIR__ . "/../uploads/";

    /**
     * Constructor menerima koneksi PDO dari Database::getConnection()
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Ambil semua data customer, dengan opsional filter pencarian.
     * @param string $search Kata kunci (nama_lengkap / no_wa)
     * @return PDOStatement
     */
    public function getAll($search = '') {
        try {
            if (!empty($search)) {
                $query = "SELECT * FROM {$this->table}
                          WHERE nama_lengkap LIKE :search
                             OR no_wa        LIKE :search2
                          ORDER BY id_customer ASC";
                $stmt = $this->conn->prepare($query);
                $searchParam = "%" . $search . "%";
                $stmt->bindParam(':search',  $searchParam);
                $stmt->bindParam(':search2', $searchParam);
            } else {
                $query = "SELECT * FROM {$this->table} ORDER BY id_customer ASC";
                $stmt  = $this->conn->prepare($query);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Ambil satu data customer berdasarkan ID.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id_customer = :id LIMIT 1";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Upload file KTP dengan validasi tipe dan ukuran.
     * @param array $file  Elemen dari $_FILES['foto_ktp']
     * @return string      Nama file yang tersimpan
     * @throws Exception   Jika validasi gagal
     */
    public function uploadKTP($file) {
        $allowed_ext  = ['jpg', 'jpeg', 'png'];
        $max_size     = 2 * 1024 * 1024; // 2 MB

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            throw new Exception("Format file KTP tidak valid! Hanya boleh .jpg, .jpeg, atau .png.");
        }
        if ($file['size'] > $max_size) {
            throw new Exception("Ukuran file KTP terlalu besar! Maksimal 2 MB.");
        }

        // Buat nama file unik agar tidak bentrok
        $new_name = 'ktp_' . time() . '_' . uniqid() . '.' . $ext;
        $dest     = $this->upload_dir . $new_name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Gagal menyimpan file KTP ke server. Periksa permission folder uploads/.");
        }

        return $new_name;
    }

    /**
     * Tambah data customer baru beserta upload foto KTP.
     * @param array $data  ['nama_lengkap', 'no_wa', 'alamat']
     * @param array $file  Elemen dari $_FILES['foto_ktp']
     * @return bool
     * @throws Exception
     */
    public function create($data, $file) {
        $foto_ktp = $this->uploadKTP($file);
        try {
            $query = "INSERT INTO {$this->table}
                        (nama_lengkap, no_wa, alamat, foto_ktp)
                      VALUES
                        (:nama_lengkap, :no_wa, :alamat, :foto_ktp)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_lengkap', $data['nama_lengkap']);
            $stmt->bindParam(':no_wa',        $data['no_wa']);
            $stmt->bindParam(':alamat',       $data['alamat']);
            $stmt->bindParam(':foto_ktp',     $foto_ktp);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Hapus file yang sudah terupload jika query gagal
            @unlink($this->upload_dir . $foto_ktp);
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Update data customer. Jika ada file baru, foto KTP lama diganti.
     * @param int   $id
     * @param array $data  ['nama_lengkap', 'no_wa', 'alamat']
     * @param array|null $file  Elemen $_FILES atau null jika tidak ada upload baru
     * @return bool
     * @throws Exception
     */
    public function update($id, $data, $file = null) {
        try {
            // Ambil data lama untuk mengetahui nama file KTP lama
            $old = $this->getById($id);

            if ($file && $file['size'] > 0 && $file['error'] === UPLOAD_ERR_OK) {
                // Ada upload baru: upload file baru dulu
                $foto_ktp = $this->uploadKTP($file);
                // Hapus file KTP lama
                $old_path = $this->upload_dir . $old['foto_ktp'];
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            } else {
                // Tidak ada upload baru, pakai nama file lama
                $foto_ktp = $old['foto_ktp'];
            }

            $query = "UPDATE {$this->table}
                      SET nama_lengkap = :nama_lengkap,
                          no_wa        = :no_wa,
                          alamat       = :alamat,
                          foto_ktp     = :foto_ktp
                      WHERE id_customer = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_lengkap', $data['nama_lengkap']);
            $stmt->bindParam(':no_wa',        $data['no_wa']);
            $stmt->bindParam(':alamat',       $data['alamat']);
            $stmt->bindParam(':foto_ktp',     $foto_ktp);
            $stmt->bindParam(':id',           $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hapus data customer beserta file KTP-nya.
     * @param int $id
     * @return bool
     * @throws Exception jika ada FK constraint
     */
    public function delete($id) {
        try {
            $old = $this->getById($id);

            $query = "DELETE FROM {$this->table} WHERE id_customer = :id";
            $stmt  = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();

            // Hapus file KTP dari server jika query berhasil
            if ($result && $old) {
                $old_path = $this->upload_dir . $old['foto_ktp'];
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }
            return $result;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Customer tidak bisa dihapus karena masih memiliki riwayat Transaksi!");
            }
            die("Gagal Mengeksekusi Operasi Database: " . $e->getMessage());
        }
    }

    /**
     * Hitung total jumlah customer (untuk dashboard).
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
