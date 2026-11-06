<?php
/**
 * Webkit24 Admin Login Portal
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validate_csrf_token()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both your username and password.';
        } else {
            try {
                $pdo = Database::getConnection();

                // Rate limiting on login attempts
                if (!check_rate_limit($pdo, 'admin_login', MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_MINUTES * 60)) {
                    $error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
                } else {
                    // Check username or email
                    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM admins WHERE username = :user OR email = :user LIMIT 1");
                    $stmt->execute([':user' => $username]);
                    $admin = $stmt->fetch();

                    if ($admin && password_verify($password, $admin['password_hash'])) {
                        // Successful login
                        admin_login($admin);

                        // Update last login
                        $upStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
                        $upStmt->execute([':id' => $admin['id']]);

                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Invalid credentials. Please verify your username and password.';
                    }
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                // Provide clear error message to assist host/database diagnostics
                $error = 'Unable to connect to database. ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Webkit24 Certificate Portal</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Brand Header -->
            <div class="auth-header">
                <a href="../" class="brand-link" title="Webkit24 Certificate Portal">
                    <img src="../assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="brand-logo-img">
                    <span class="brand-badge-pill">Admin Portal</span>
                </a>
                <h1 class="auth-title">Administrative Sign In</h1>
                <p class="auth-subtitle">Secure access to certificate management & issuance</p>
            </div>

            <!-- Error Notification -->
            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" class="auth-form" autocomplete="off">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-icon-wrap">
                        <svg class="field-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            placeholder="admin or admin@webkit24.com" 
                            value="<?= e($username) ?>" 
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label-row">
                        <label for="password" class="form-label">Password</label>
                    </div>
                    <div class="input-icon-wrap">
                        <svg class="field-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="••••••••••••" 
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <span>Sign In to Dashboard</span>
                    <svg class="btn-icon-right" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </form>

            <div class="auth-footer-nav">
                <a href="../" class="back-link">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    <span>Back to Verification Portal</span>
                </a>
            </div>
        </div>

        <div class="auth-page-footer">
            <p>© <?= date('Y') ?> Webkit24. Official Certificate Authority.</p>
        </div>
    </div>
</body>
</html>

