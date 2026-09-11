<?php
// ============================================
// ENVIRONMENT LOADER & CONFIGURATION
// ============================================
(function() {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1], " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$val}");
            }
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $val;
            }
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $val;
            }
        }
    }
})();

function get_db_credentials() {
    $host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: '';
    $db   = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'seleksi_da';
    $port = (int)(getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306);

    return [
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'db'   => $db,
        'port' => $port,
    ];
}

// ============================================
// CONNECTION POOL CLASS
// ============================================
class DatabaseConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $currentIndex = 0;
    private $maxConnections = 10;
    
    private function __construct() {
        for ($i = 0; $i < 2; $i++) {
            $this->connections[] = $this->createConnection();
        }
    }
    
    private function createConnection() {
        $cfg = get_db_credentials();
        $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db'], $cfg['port']);
        
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
            return null;
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        for ($i = 0; $i < count($this->connections); $i++) {
            $index = ($this->currentIndex + $i) % count($this->connections);
            $conn = $this->connections[$index];
            
            if ($conn && $conn->ping()) {
                $this->currentIndex = ($index + 1) % count($this->connections);
                return $conn;
            }
        }
        
        if (count($this->connections) < $this->maxConnections) {
            $newConn = $this->createConnection();
            if ($newConn) {
                $this->connections[] = $newConn;
                return $newConn;
            }
        }
        
        return $this->connections[0] ?? null;
    }
    
    public function releaseConnection($conn) {
        if ($conn) {
            while ($conn->more_results()) {
                $conn->next_result();
            }
        }
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function getDbConnection() {
    return DatabaseConnectionPool::getInstance()->getConnection();
}

function releaseDbConnection($conn) {
    DatabaseConnectionPool::getInstance()->releaseConnection($conn);
}

// ============================================
// DATABASE CONNECTION (GLOBAL)
// ============================================
$cfg = get_db_credentials();
$conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db'], $cfg['port']);
if ($conn->connect_error) {
    die("❌ Koneksi Database Gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function ensure_app_settings_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        die("❌ Gagal menyiapkan tabel pengaturan: " . $conn->error);
    }
}

function get_app_setting($conn, $settingKey, $defaultValue = null)
{
    ensure_app_settings_table($conn);

    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) {
        return $defaultValue;
    }

    $stmt->bind_param("s", $settingKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }

    return $defaultValue;
}

function set_app_setting($conn, $settingKey, $settingValue)
{
    ensure_app_settings_table($conn);

    $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $settingKey, $settingValue);
    return $stmt->execute();
}

function get_kkm($conn, $defaultValue = 70)
{
    $settingValue = get_app_setting($conn, 'kkm', (string)$defaultValue);
    return is_numeric($settingValue) ? (float)$settingValue : (float)$defaultValue;
}

function set_kkm($conn, $kkm)
{
    return set_app_setting($conn, 'kkm', number_format((float)$kkm, 2, '.', ''));
}
?>