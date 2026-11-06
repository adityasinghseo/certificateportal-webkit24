<?php
/**
 * Webkit24 Helper Functions
 */

if (!defined('WEBKIT24_APP')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Return JSON response and terminate script
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Sanitize clean string for HTML rendering
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Normalize and clean Certificate ID
 */
function clean_certificate_id($id) {
    if (!$id) return '';
    // Trim, convert to uppercase, remove invisible characters
    $id = trim($id);
    $id = preg_replace('/[\x00-\x1F\x7F]/u', '', $id);
    return strtoupper($id);
}

/**
 * Format Date to readable string (e.g., "10 July 2026")
 */
function format_display_date($dateStr) {
    if (!$dateStr) return '';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return $dateStr;
    return date('j F Y', $timestamp);
}

/**
 * Get Client IP Address (respecting proxies/Cloudflare if configured)
 */
function get_client_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',        // Nginx proxy
        'HTTP_X_FORWARDED_FOR',  // Standard proxy
        'REMOTE_ADDR'            // Default
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipList = explode(',', $_SERVER[$header]);
            $ip = trim($ipList[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Check and enforce rate limiting for public verification requests
 * Returns true if allowed, false if limit exceeded
 */
function check_rate_limit($pdo, $endpoint = 'verify', $maxRequests = 30, $windowSeconds = 60) {
    $ip = get_client_ip();
    
    try {
        // Purge old expired limits
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $pdo->prepare("DELETE FROM rate_limits WHERE last_request < :cutoff")
            ->execute([':cutoff' => $cutoff]);

        // Fetch current IP record
        $stmt = $pdo->prepare("SELECT id, request_count, first_request FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint LIMIT 1");
        $stmt->execute([':ip' => $ip, ':endpoint' => $endpoint]);
        $record = $stmt->fetch();

        if ($record) {
            $firstTime = strtotime($record['first_request']);
            if (time() - $firstTime > $windowSeconds) {
                // Window expired, reset
                $resetStmt = $pdo->prepare("UPDATE rate_limits SET request_count = 1, first_request = CURRENT_TIMESTAMP WHERE id = :id");
                $resetStmt->execute([':id' => $record['id']]);
                return true;
            }

            if ($record['request_count'] >= $maxRequests) {
                return false; // Rate limit reached
            }

            // Increment
            $incStmt = $pdo->prepare("UPDATE rate_limits SET request_count = request_count + 1 WHERE id = :id");
            $incStmt->execute([':id' => $record['id']]);
            return true;
        } else {
            // New record
            $insStmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, request_count, first_request) VALUES (:ip, :endpoint, 1, CURRENT_TIMESTAMP)");
            $insStmt->execute([':ip' => $ip, ':endpoint' => $endpoint]);
            return true;
        }
    } catch (Exception $e) {
        // On rate-limit table failure, allow request but log error
        error_log("Rate limiting error: " . $e->getMessage());
        return true;
    }
}

/**
 * Generate sequential Certificate ID based on type and year
 * Formats:
 * WEBKIT24/INT/2026/0001
 * WEBKIT24/TRN/2026/0002
 * WEBKIT24/EXP/2026/0003
 */
function generate_next_certificate_id($pdo, $type = 'Internship', $year = null) {
    if (!$year) {
        $year = date('Y');
    }

    $typeCode = 'INT';
    if ($type === 'Training') {
        $typeCode = 'TRN';
    } elseif ($type === 'Experience') {
        $typeCode = 'EXP';
    }

    $prefix = "WEBKIT24/{$typeCode}/{$year}/";

    try {
        // Query for certificates with this prefix to get the maximum number
        $stmt = $pdo->prepare("SELECT certificate_id FROM certificates WHERE certificate_id LIKE :prefix ORDER BY id DESC LIMIT 50");
        $stmt->execute([':prefix' => $prefix . '%']);
        $rows = $stmt->fetchAll();

        $maxSeq = 0;
        foreach ($rows as $row) {
            $id = $row['certificate_id'];
            $parts = explode('/', $id);
            if (count($parts) >= 4) {
                $num = intval(end($parts));
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $nextSeq = $maxSeq + 1;
        return sprintf("%s%04d", $prefix, $nextSeq);
    } catch (Exception $e) {
        // Fallback random/sequential
        return sprintf("%s%04d", $prefix, 1);
    }
}

/**
 * Log certificate verification attempt for auditing
 */
function log_verification_attempt($pdo, $certificateId, $resultStatus) {
    try {
        $ip = get_client_ip();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $stmt = $pdo->prepare("INSERT INTO audit_logs (certificate_id, result_status, ip_address, user_agent) VALUES (:cert_id, :status, :ip, :ua)");
        $stmt->execute([
            ':cert_id' => $certificateId,
            ':status'  => $resultStatus,
            ':ip'      => $ip,
            ':ua'      => $ua
        ]);
    } catch (Exception $e) {
        // Silent fail for logs
    }
}

