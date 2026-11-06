<?php
/**
 * Admin Authentication & Session Management
 */

if (!defined('WEBKIT24_APP')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Check if admin is currently authenticated
 */
function is_admin_logged_in() {
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
        return false;
    }

    // Check session timeout (default 6 hours)
    if (!empty($_SESSION['admin_last_activity'])) {
        $maxLifetime = SESSION_LIFETIME_HOURS * 3600;
        if (time() - $_SESSION['admin_last_activity'] > $maxLifetime) {
            admin_logout();
            return false;
        }
    }

    $_SESSION['admin_last_activity'] = time();
    return true;
}

/**
 * Require admin authentication for protected pages
 */
function require_admin_auth() {
    if (!is_admin_logged_in()) {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin session required.']);
            exit;
        }
        
        $loginUrl = '/admin/login.php';
        header("Location: {$loginUrl}");
        exit;
    }
}

/**
 * Log in admin and regenerate session ID to prevent fixation
 */
function admin_login($adminData) {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminData['id'];
    $_SESSION['admin_username'] = $adminData['username'];
    $_SESSION['admin_email'] = $adminData['email'];
    $_SESSION['admin_last_activity'] = time();
}

/**
 * Log out admin and destroy session
 */
function admin_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

