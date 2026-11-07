<?php
/**
 * Webkit24 Certificate Verification Portal
 * Interactive Setup & Installer Script
 */

define('WEBKIT24_APP', true);

$lockFile = __DIR__ . '/install.lock';
$alreadyInstalled = file_exists($lockFile);

$message = '';
$messageType = '';
$step = $alreadyInstalled ? 'installed' : 'form';

// Requirements Check
$requirements = [
    'PHP Version (>= 7.4)'  => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension'         => extension_loaded('pdo'),
    'MySQL or SQLite Driver'=> extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
    'JSON Extension'        => extension_loaded('json'),
    'Session Support'       => extension_loaded('session'),
    'OpenSSL Support'       => extension_loaded('openssl'),
];

$allReqsMet = !in_array(false, $requirements, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled && $allReqsMet) {
    $dbDriver = trim($_POST['db_driver'] ?? 'mysql');
    $dbHost   = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort   = trim($_POST['db_port'] ?? '3306');
    $dbName   = trim($_POST['db_name'] ?? 'webkit24_certs');
    $dbUser   = trim($_POST['db_user'] ?? 'root');
    $dbPass   = (string)($_POST['db_pass'] ?? '');

    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail= trim($_POST['admin_email'] ?? 'admin@webkit24.com');
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $seedDemo  = !empty($_POST['seed_demo']);

    if (empty($adminUser) || empty($adminEmail) || strlen($adminPass) < 8) {
        $message = 'Admin username, email, and a password of at least 8 characters are required.';
        $messageType = 'error';
    } else {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            if ($dbDriver === 'sqlite') {
                $sqlitePath = __DIR__ . '/database.sqlite';
                $pdo = new PDO('sqlite:' . $sqlitePath, null, null, $options);
                $pdo->exec("
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
                    CREATE TABLE IF NOT EXISTS admins (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        username TEXT NOT NULL UNIQUE,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        last_login DATETIME NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE TABLE IF NOT EXISTS rate_limits (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ip_address TEXT NOT NULL,
                        endpoint TEXT NOT NULL,
                        request_count INTEGER NOT NULL DEFAULT 1,
                        first_request DATETIME DEFAULT CURRENT_TIMESTAMP,
                        last_request DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(ip_address, endpoint)
                    );
                    CREATE TABLE IF NOT EXISTS audit_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        certificate_id TEXT NOT NULL,
                        result_status TEXT NOT NULL,
                        ip_address TEXT NOT NULL,
                        user_agent TEXT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            } else {
                // MySQL
                $dsnWithoutDb = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                $initPdo = new PDO($dsnWithoutDb, $dbUser, $dbPass, $options);
                $initPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                $dsnWithDb = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsnWithDb, $dbUser, $dbPass, $options);

                // Read and execute schema.sql
                $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
                $pdo->exec($schemaSql);
            }

            // Create initial Administrator
            $passHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO admins (id, username, email, password_hash, created_at) VALUES (1, :user, :email, :pass, CURRENT_TIMESTAMP)");
            if ($dbDriver !== 'sqlite') {
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash, created_at) VALUES (:user, :email, :pass, NOW()) ON DUPLICATE KEY UPDATE password_hash = :pass_up");
                $stmt->execute([
                    ':user'     => $adminUser,
                    ':email'    => $adminEmail,
                    ':pass'     => $passHash,
                    ':pass_up'  => $passHash
                ]);
            } else {
                $stmt->execute([
                    ':user'  => $adminUser,
                    ':email' => $adminEmail,
                    ':pass'  => $passHash
                ]);
            }

            // Optional Demo Certificate
            if ($seedDemo) {
                $insDemo = $pdo->prepare("
                    INSERT OR IGNORE INTO certificates (student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status)
                    VALUES ('Hariom Pandey', 'WEBKIT24/INT/2026/0035', 'Internship', 'Frontend Developer Intern', '2026-07-10', '2026-10-10', '2026-10-15', 'Active')
                ");
                if ($dbDriver !== 'sqlite') {
                    $insDemo = $pdo->prepare("
                        INSERT IGNORE INTO certificates (student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status)
                        VALUES ('Hariom Pandey', 'WEBKIT24/INT/2026/0035', 'Internship', 'Frontend Developer Intern', '2026-07-10', '2026-10-10', '2026-10-15', 'Active')
                    ");
                }
                $insDemo->execute();
            }

            // Write lock file
            file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\nDriver: " . $dbDriver);

            $step = 'success';
            $message = 'Installation completed successfully!';
            $messageType = 'success';

        } catch (Exception $e) {
            $message = 'Database Setup Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webkit24 Portal Setup & Installer</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-auth-body">
    <div class="auth-wrapper" style="max-width: 580px;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-link" style="justify-content: center; margin-bottom: 12px;">
                    <img src="assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="brand-logo-img">
                    <span class="brand-badge-pill">Setup Wizard</span>
                </div>
                <h1 class="auth-title">Certificate Portal Installer</h1>
                <p class="auth-subtitle">Initialize database and create primary administrator</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>" style="<?= $messageType === 'success' ? 'background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46;' : '' ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 'installed'): ?>
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">🔒</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 8px;">Portal Already Initialized</h3>
                    <p style="color: var(--navy-muted); margin-bottom: 24px;">The installation lock file <code>install.lock</code> is active. To re-run setup, remove this file from your server.</p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <a href="admin/login.php" class="btn btn-primary">Go to Admin Login</a>
                        <a href="index.php" class="btn btn-secondary">Go to Homepage</a>
                    </div>
                </div>

            <?php elseif ($step === 'success'): ?>
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">🎉</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 8px;">Installation Complete!</h3>
                    <p style="color: var(--navy-muted); margin-bottom: 24px;">Your database tables have been set up and your administrator account is ready.</p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <a href="admin/login.php" class="btn btn-primary">Login to Admin Dashboard</a>
                        <a href="index.php" class="btn btn-secondary">View Verification Portal</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Environment Checks -->
                <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px;">
                    <div style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 8px; color: var(--navy);">Server Compatibility Checks</div>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 6px; font-size: 0.88rem;">
                        <?php foreach ($requirements as $name => $passed): ?>
                            <li style="display: flex; justify-content: space-between; align-items: center;">
                                <span><?= htmlspecialchars($name) ?></span>
                                <span style="font-weight: 700; color: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $passed ? '✓ Passed' : '✗ Failed' ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <form action="install.php" method="POST" class="auth-form" autocomplete="off">
                    <h4 style="font-size: 0.95rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; margin-top: 8px;">1. Database Configuration</h4>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label required">Database Driver</label>
                            <select name="db_driver" class="form-control form-select">
                                <option value="mysql" <?= extension_loaded('pdo_mysql') ? 'selected' : '' ?>>MySQL (Shared Hosting / cPanel)</option>
                                <option value="sqlite" <?= !extension_loaded('pdo_mysql') ? 'selected' : '' ?>>SQLite (Standalone)</option>
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Database Host</label>
                            <input type="text" name="db_host" class="form-control" value="127.0.0.1">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-4">
                            <label class="form-label">Port</label>
                            <input type="text" name="db_port" class="form-control" value="3306">
                        </div>
                        <div class="form-group col-8">
                            <label class="form-label">Database Name</label>
                            <input type="text" name="db_name" class="form-control" value="webkit24_certs">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">User</label>
                            <input type="text" name="db_user" class="form-control" value="root">
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="db_pass" class="form-control" placeholder="Database password">
                        </div>
                    </div>

                    <h4 style="font-size: 0.95rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; margin-top: 12px;">2. Admin Account Setup</h4>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label required">Admin Username</label>
                            <input type="text" name="admin_user" class="form-control" value="admin" required>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label required">Admin Email</label>
                            <input type="email" name="admin_email" class="form-control" value="admin@webkit24.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Admin Password (min. 8 characters)</label>
                        <input type="password" name="admin_pass" class="form-control" placeholder="Enter strong admin password" minlength="8" required>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; margin: 12px 0;">
                        <input type="checkbox" id="seed_demo" name="seed_demo" value="1" checked style="width: 16px; height: 16px;">
                        <label for="seed_demo" style="font-size: 0.88rem; color: var(--navy); cursor: pointer;">
                            Seed sample certificate for Hariom Pandey (<code>WEBKIT24/INT/2026/0035</code>)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" <?= !$allReqsMet ? 'disabled' : '' ?>>
                        <span>Run Setup & Initialize Portal</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

