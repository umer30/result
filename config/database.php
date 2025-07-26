<?php
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        // Load environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Use environment variables
            $serverName = $_ENV['DB_SERVER'] ?? '';
            $connectionOptions = array(
                "Database" => $_ENV['DB_NAME'] ?? '',
                "Uid" => $_ENV['DB_USERNAME'] ?? '',
                "PWD" => $_ENV['DB_PASSWORD'] ?? '',
                "TrustServerCertificate" => isset($_ENV['DB_TRUST_CERT']) ? filter_var($_ENV['DB_TRUST_CERT'], FILTER_VALIDATE_BOOLEAN) : true,
                "Encrypt" => isset($_ENV['DB_ENCRYPT']) ? filter_var($_ENV['DB_ENCRYPT'], FILTER_VALIDATE_BOOLEAN) : false,
                "ConnectionPooling" => false,
                "MultipleActiveResultSets" => false,
                "LoginTimeout" => 30,
                "ConnectRetryCount" => 3,
                "ConnectRetryInterval" => 10
            );
            
            $this->connection = sqlsrv_connect($serverName, $connectionOptions);
            
            if ($this->connection === false) {
                throw new Exception('Database connection failed: ' . print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConnection() {
        if ($this->connection === null || $this->connection === false) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function closeConnection() {
        if ($this->connection && $this->connection !== false) {
            sqlsrv_close($this->connection);
            $this->connection = null;
        }
    }
    
    public function __destruct() {
        $this->closeConnection();
    }
}
?>
