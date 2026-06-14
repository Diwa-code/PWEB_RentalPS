<?php
// =====================================================
// Konfigurasi Global Error Reporting (Development Mode)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================
// Fungsi Helper: Dump & Die (untuk debugging $_POST / $_FILES)
// =====================================================
if (!function_exists('dd')) {
    function dd($data) {
        echo "<pre style='background:#111; color:#0f0; padding:10px; border-radius:5px; font-family:monospace; font-size:13px;'>";
        print_r($data);
        echo "</pre>";
        die();
    }
}

// =====================================================
// Class Database - Koneksi PDO ke db_rental_ps
// =====================================================
class Database {
    private $host     = "localhost";
    private $db_name  = "db_rental_ps";
    private $username = "root";
    private $password = "";
    public  $conn;

    /**
     * Membuat dan mengembalikan koneksi PDO ke database.
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            die("Koneksi database gagal: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>
