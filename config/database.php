<?php
/**
 * Webkit24 Database Connection Handler
 * Uses PDO with Prepared Statements for complete SQL Injection protection.
 * Includes automated Hostinger/cPanel socket-TCP fallback and schema auto-initialization.
 */

if (!defined('WEBKIT24_APP')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    private static $db_driver = 'mysql';
    private static $db_host   = 'localhost';
    private static $db_port   = '3306';
    private static $db_name   = 'webkit24_certs';
    private static $db_user   = 'root';
    private static $db_pass   = '';
    private static $db_sqlite = __DIR__ . '/../database.sqlite';

    private static $connection = null;
    private static $schema_checked = false;

    /**
     * Get active database connection singleton
     * @return PDO
     * @throws Exception
     */
    public static function getConnection() {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $driver = getenv('DB_DRIVER') ?: (defined('DB_DRIVER') ? DB_DRIVER : self::$db_driver);
        $host   = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : self::$db_host);
        $port   = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : self::$db_port);
        $name   = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : self::$db_name);
        $user   = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : self::$db_user);
        $pass   = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : self::$db_pass);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ];

        // 1. SQLite Handler
        if ($driver === 'sqlite' || (!extension_loaded('pdo_mysql') && extension_loaded('pdo_sqlite'))) {
            try {
                $dsn = 'sqlite:' . self::$db_sqlite;
                self::$connection = new PDO($dsn, null, null, $options);
                self::$connection->exec('PRAGMA foreign_keys = ON;');
                self::ensureSchema(self::$connection, 'sqlite');
                return self::$connection;
            } catch (Exception $e) {
                error_log('SQLite Connection Error: ' . $e->getMessage());
                throw new Exception('Database Connection Error: ' . $e->getMessage());
            }
        }

        // 2. MySQL Handler with Hostinger Host Fallback (localhost <-> 127.0.0.1)
        $hostsToTry = [$host];
        if ($host === 'localhost') {
            $hostsToTry[] = '127.0.0.1';
        } elseif ($host === '127.0.0.1') {
            $hostsToTry[] = 'localhost';
        }

        $lastError = null;
        foreach ($hostsToTry as $currentHost) {
            try {
                $dsn = "mysql:host={$currentHost};port={$port};dbname={$name};charset=utf8mb4";
                self::$connection = new PDO($dsn, $user, $pass, $options);
                self::ensureSchema(self::$connection, 'mysql');
                return self::$connection;
            } catch (PDOException $e) {
                $lastError = $e;
            }
        }

        // 3. Fallback to SQLite if allowed in local development
        if (extension_loaded('pdo_sqlite') && !defined('FORCE_MYSQL') && (!defined('IS_PRODUCTION') || !IS_PRODUCTION)) {
            try {
                $dsn = 'sqlite:' . self::$db_sqlite;
                self::$connection = new PDO($dsn, null, null, $options);
                self::ensureSchema(self::$connection, 'sqlite');
                return self::$connection;
            } catch (Exception $fallbackErr) {
                // Ignore fallback error
            }
        }

        $errorMsg = $lastError ? $lastError->getMessage() : 'Unable to connect to MySQL database.';
        error_log('Database Connection Error: ' . $errorMsg);
        throw new Exception('Database Connection Error: ' . $errorMsg);
    }

    /**
     * Auto-heals missing tables and seeds initial administrative account
     */
    public static function ensureSchema(PDO $pdo, $driverType = 'mysql') {
        if (self::$schema_checked) {
            return;
        }

        try {
            // Check if admins table exists
            $tableExists = false;
            try {
                $check = $pdo->query("SELECT 1 FROM admins LIMIT 1");
                $tableExists = ($check !== false);
            } catch (Exception $e) {
                $tableExists = false;
            }

            if (!$tableExists) {
                if ($driverType === 'mysql') {
                    $schemaFile = __DIR__ . '/../schema.sql';
                    if (file_exists($schemaFile)) {
                        $sql = file_get_contents($schemaFile);
                        // Strip comments and split by semicolon
                        $statements = array_filter(
                            array_map('trim', explode(';', $sql)),
                            function($stmt) { return !empty($stmt); }
                        );
                        foreach ($statements as $statement) {
                            try {
                                $pdo->exec($statement);
                            } catch (Exception $stmtErr) {
                                // continue executing remaining tables
                            }
                        }
                    }
                } else {
                    // SQLite table definition
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS admins (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            username TEXT NOT NULL UNIQUE,
                            email TEXT NOT NULL UNIQUE,
                            password_hash TEXT NOT NULL,
                            last_login DATETIME NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );
                        CREATE TABLE IF NOT EXISTS certificates (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            student_name TEXT NOT NULL,
                            certificate_id TEXT NOT NULL UNIQUE,
                            certificate_type TEXT NOT NULL,
                            position TEXT NOT NULL,
                            start_date TEXT NOT NULL,
                            end_date TEXT NOT NULL,
                            issue_date TEXT NOT NULL,
                            status TEXT NOT NULL DEFAULT 'Active',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );
                        CREATE TABLE IF NOT EXISTS rate_limits (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            ip_address TEXT NOT NULL,
                            action_type TEXT NOT NULL,
                            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );
                    ");
                }
            }

            // Verify or Seed Default Admin Account: admin / Webkit24@Admin2026
            $adminCount = 0;
            try {
                $cntStmt = $pdo->query("SELECT COUNT(*) as cnt FROM admins");
                $res = $cntStmt->fetch();
                $adminCount = (int)($res['cnt'] ?? 0);
            } catch (Exception $e) {
                $adminCount = 0;
            }

            $adminBcryptHash = '$2y$10$vCdQv7NFdza4uf96xcdshuqThnTR0Y8/VYwJ0AKnWH8fZ3oiM2uIW';

            if ($adminCount === 0) {
                $insert = $pdo->prepare("INSERT INTO admins (username, email, password_hash, created_at) VALUES (:u, :e, :p, NOW())");
                $insert->execute([
                    ':u' => 'admin',
                    ':e' => 'admin@webkit24.com',
                    ':p' => $adminBcryptHash
                ]);
            } else {
                // Ensure default admin password hash is updated to valid 60-character bcrypt for Webkit24@Admin2026
                $upStmt = $pdo->prepare("UPDATE admins SET password_hash = :p WHERE username = 'admin' AND (LENGTH(password_hash) < 60 OR password_hash LIKE '$2y$10$89W5%')");
                $upStmt->execute([':p' => $adminBcryptHash]);
            }

            self::$schema_checked = true;
        } catch (Exception $e) {
            error_log('Database Schema Verification Error: ' . $e->getMessage());
        }
    }
}
