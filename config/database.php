<?php
/**
 * Webkit24 Database Connection Handler
 * Uses PDO with Prepared Statements for complete SQL Injection protection.
 */

if (!defined('WEBKIT24_APP')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    // MySQL Database Configuration
    // On cPanel/shared hosting, update these values or define them in .env / config
    private static $db_driver = 'mysql'; // 'mysql' or 'sqlite'
    private static $db_host   = '127.0.0.1';
    private static $db_port   = '3306';
    private static $db_name   = 'webkit24_certs';
    private static $db_user   = 'root';
    private static $db_pass   = '';
    private static $db_sqlite = __DIR__ . '/../database.sqlite';

    private static $connection = null;

    /**
     * Get active database connection singleton
     * @return PDO
     * @throws Exception
     */
    public static function getConnection() {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Allow overriding via environment or external constants
        $driver = getenv('DB_DRIVER') ?: (defined('DB_DRIVER') ? DB_DRIVER : self::$db_driver);
        $host   = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : self::$db_host);
        $port   = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : self::$db_port);
        $name   = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : self::$db_name);
        $user   = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : self::$db_user);
        $pass   = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : self::$db_pass);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if ($driver === 'sqlite' || (!extension_loaded('pdo_mysql') && extension_loaded('pdo_sqlite'))) {
                $dsn = 'sqlite:' . self::$db_sqlite;
                self::$connection = new PDO($dsn, null, null, $options);
                self::$connection->exec('PRAGMA foreign_keys = ON;');
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                self::$connection = new PDO($dsn, $user, $pass, $options);
            }
            return self::$connection;
        } catch (PDOException $e) {
            // Check if sqlite fallback is possible when mysql fails in local environments
            if ($driver === 'mysql' && extension_loaded('pdo_sqlite') && !defined('FORCE_MYSQL')) {
                try {
                    $dsn = 'sqlite:' . self::$db_sqlite;
                    self::$connection = new PDO($dsn, null, null, $options);
                    return self::$connection;
                } catch (Exception $fallbackErr) {
                    // ignore and throw original
                }
            }
            
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check database configuration.');
        }
    }
}

