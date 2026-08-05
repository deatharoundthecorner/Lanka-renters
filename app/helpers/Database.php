<?php

/**
 * Lanka Renters - Database Connection Helper
 * Implements the Singleton pattern for managing a single PDO connection.
 */
class Database {
    // Hold the class instance
    private static $instance = null;
    
    // Hold the PDO connection
    private $connection;

    /**
     * Private constructor to prevent direct creation of the object.
     * Reads database configurations and establishes a PDO connection.
     */
    private function __construct() {
        // Resolve configuration file path relative to this file
        $configPath = dirname(__DIR__) . '/config/database.php';
        
        if (!file_exists($configPath)) {
            throw new Exception("Database configuration file not found at: " . $configPath);
        }
        
        $config = require $configPath;
        
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $db   = $config['db'] ?? 'lanka_renters';
        $user = $config['user'] ?? 'root';
        $pass = $config['pass'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        // Prepare Data Source Name (DSN)
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        
        // Define PDO options
        $options = [
            // Enable exception throwing for database query/connection errors
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Fetch associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disable emulated prepared statements for security and native data types
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Returns the single instance of this class.
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the established PDO database connection.
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prevent cloning of the instance to maintain the Singleton pattern.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance.
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton Database instance.");
    }
}
