<?php
/**
 * Webkit24 Certificate Verification Portal
 * Application Configuration
 */

// Prevent direct script access
if (!defined('WEBKIT24_APP')) {
    define('WEBKIT24_APP', true);
}

// Error reporting (set to 0 in production)
$is_production = isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'certificate.webkit24.com';
if ($is_production) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Company & Portal Metadata
define('APP_NAME', 'Webkit24 Certificate Verification');
define('COMPANY_NAME', 'Webkit24');
define('MAIN_WEBSITE_URL', 'https://webkit24.com');
define('PORTAL_URL', 'https://certificate.webkit24.com');
define('SUPPORT_EMAIL', 'support@webkit24.com');
define('CONTACT_URL', 'https://webkit24.com/#contact');
define('ABOUT_URL', 'https://webkit24.com/#about');

// Base URLs
define('BASE_URL', 'https://certificate.webkit24.com/');
define('MAIN_SITE_URL', 'https://webkit24.com/');

// Brand Colors
define('COLOR_PRIMARY', '#FF4938'); // Orange/Coral
define('COLOR_NAVY', '#080D25');    // Dark Navy
define('COLOR_LIGHT_BG', '#F8F9FC');// Light Background
define('COLOR_WHITE', '#FFFFFF');

// Security & Rate Limiting Settings
define('MAX_VERIFY_REQUESTS_PER_MINUTE', 30);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('SESSION_LIFETIME_HOURS', 6);

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    // PHP session configuration parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if ($secure_cookie) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// MySQL Production Database Settings (Hostinger)
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u908716340_certificate');
define('DB_USER', 'u908716340_certificate');
define('DB_PASS', '*faNkpufAV3');
define('DB_CHARSET', 'utf8mb4');
define('FORCE_MYSQL', true);
