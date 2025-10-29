<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'pt_saranasentra_db';
    private $username = 'root';
    private $password = '';
    private $conn = null;
    
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $e) {
                echo "Connection error: " . $e->getMessage();
                exit;
            }
        }
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}

// Helper function to get database connection
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Helper function to get site settings
function getSiteSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Check if it's JSON data
        $decoded = json_decode($result['setting_value'], true);
        return $decoded !== null ? $decoded : $result['setting_value'];
    }
    return null;
}

// Helper function to format Indonesian date
function formatTanggal($date) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari = date('d', $timestamp);
    $bln = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $hari . ' ' . $bulan[$bln] . ' ' . $tahun;
}

// Helper function to truncate text
function truncateText($text, $limit = 150) {
    $text = strip_tags($text);
    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }
    return $text;
}
?>