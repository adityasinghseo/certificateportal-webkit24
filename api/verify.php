<?php
/**
 * Webkit24 Certificate Verification API
 * Endpoint: /api/verify.php?id=WEBKIT24/INT/2026/0035
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Accept GET or POST
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $method !== 'POST') {
    json_response(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

// Extract and normalize Certificate ID
$certId = clean_certificate_id($_GET['id'] ?? $_POST['id'] ?? '');

if (empty($certId)) {
    json_response([
        'success' => false,
        'status'  => 'empty_id',
        'message' => 'Please enter a valid Certificate ID to verify.'
    ], 400);
}

// Length and character validation
if (strlen($certId) < 5 || strlen($certId) > 100) {
    json_response([
        'success' => false,
        'status'  => 'invalid_format',
        'message' => 'The provided Certificate ID is improperly formatted.'
    ], 400);
}

try {
    $pdo = Database::getConnection();

    // Check rate limit (30 requests/minute per IP)
    if (!check_rate_limit($pdo, 'verify', MAX_VERIFY_REQUESTS_PER_MINUTE, 60)) {
        json_response([
            'success' => false,
            'status'  => 'rate_limited',
            'message' => 'Too many verification requests. Please wait a minute and try again.'
        ], 429);
    }

    // Search exact Certificate ID using prepared statement
    $stmt = $pdo->prepare("
        SELECT 
            id,
            student_name,
            certificate_id,
            certificate_type,
            position,
            start_date,
            end_date,
            issue_date,
            status,
            created_at
        FROM certificates 
        WHERE certificate_id = :cert_id 
        LIMIT 1
    ");
    $stmt->execute([':cert_id' => $certId]);
    $certificate = $stmt->fetch();

    if (!$certificate) {
        // Log attempt
        log_verification_attempt($pdo, $certId, 'NOT_FOUND');

        json_response([
            'success' => false,
            'status'  => 'not_found',
            'message' => 'Certificate Not Found',
            'details' => 'The entered Certificate ID could not be found in our records. Please check the ID and try again.'
        ], 404);
    }

    // Check status
    if (strcasecmp($certificate['status'], 'Revoked') === 0) {
        log_verification_attempt($pdo, $certId, 'REVOKED');

        json_response([
            'success' => false,
            'status'  => 'revoked',
            'message' => 'Certificate Revoked',
            'details' => 'This certificate was officially issued by Webkit24 but has been revoked. It is no longer valid or recognized.',
            'certificate' => [
                'certificate_id'   => $certificate['certificate_id'],
                'student_name'     => $certificate['student_name'],
                'certificate_type' => $certificate['certificate_type'],
                'status'           => 'Revoked'
            ]
        ], 200);
    }

    // Active & Valid Certificate
    log_verification_attempt($pdo, $certId, 'VERIFIED');

    // Build verification URL for QR code
    $verificationUrl = (defined('PORTAL_URL') ? PORTAL_URL : 'https://certificate.webkit24.com') . '/?id=' . urlencode($certificate['certificate_id']);

    json_response([
        'success' => true,
        'status'  => 'valid',
        'message' => 'Certificate Verified Successfully',
        'confirmation' => 'This certificate has been officially issued and verified by Webkit24.',
        'certificate' => [
            'id'                   => (int)$certificate['id'],
            'student_name'         => $certificate['student_name'],
            'certificate_id'       => $certificate['certificate_id'],
            'certificate_type'     => $certificate['certificate_type'],
            'position'             => $certificate['position'],
            'start_date'           => $certificate['start_date'],
            'start_date_formatted' => format_display_date($certificate['start_date']),
            'end_date'             => $certificate['end_date'],
            'end_date_formatted'   => format_display_date($certificate['end_date']),
            'issue_date'           => $certificate['issue_date'],
            'issue_date_formatted' => format_display_date($certificate['issue_date']),
            'issuing_organization' => COMPANY_NAME,
            'status'               => 'Valid',
            'verification_url'     => $verificationUrl
        ]
    ], 200);

} catch (Exception $e) {
    error_log('Verification Error: ' . $e->getMessage());
    json_response([
        'success' => false,
        'status'  => 'server_error',
        'message' => 'An error occurred while verifying the certificate. Please try again later.'
    ], 500);
}

