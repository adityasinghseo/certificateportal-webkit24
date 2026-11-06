<?php
/**
 * CSRF Protection Utility
 */

if (!defined('WEBKIT24_APP')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Generate or get current session CSRF token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output HTML hidden input for CSRF
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST or headers
 */
function validate_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Require valid CSRF token or stop execution
 */
function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf_token()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                json_response(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.'], 403);
            } else {
                http_response_code(403);
                die('Invalid or expired security token. Please refresh the page and try again.');
            }
        }
    }
}

