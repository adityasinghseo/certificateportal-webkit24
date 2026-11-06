<?php
/**
 * Admin API: Update Certificate
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check CSRF
require_csrf();

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$id              = (int)($input['id'] ?? 0);
$studentName     = trim($input['student_name'] ?? '');
$certificateType = trim($input['certificate_type'] ?? 'Internship');
$certificateId   = clean_certificate_id($input['certificate_id'] ?? '');
$position        = trim($input['position'] ?? '');
$startDate       = trim($input['start_date'] ?? '');
$endDate         = trim($input['end_date'] ?? '');
$issueDate       = trim($input['issue_date'] ?? '');
$status          = trim($input['status'] ?? 'Active');

if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid Certificate record ID.'], 400);
}

// Validations
$errors = [];
if (empty($studentName)) {
    $errors[] = 'Student Full Name is required.';
}
if (empty($certificateId)) {
    $errors[] = 'Certificate ID is required.';
}
if (!in_array($certificateType, ['Internship', 'Training', 'Experience'])) {
    $errors[] = 'Invalid Certificate Type.';
}
if (empty($position)) {
    $errors[] = 'Internship / Job Role is required.';
}
if (empty($startDate) || !strtotime($startDate)) {
    $errors[] = 'Valid Start Date is required.';
}
if (empty($endDate) || !strtotime($endDate)) {
    $errors[] = 'Valid End Date is required.';
}
if (empty($issueDate) || !strtotime($issueDate)) {
    $errors[] = 'Valid Issue Date is required.';
}
if (!in_array($status, ['Active', 'Revoked'])) {
    $status = 'Active';
}

if (!empty($errors)) {
    json_response(['success' => false, 'message' => implode(' ', $errors)], 400);
}

try {
    $pdo = Database::getConnection();

    // Check certificate existence
    $checkStmt = $pdo->prepare("SELECT id FROM certificates WHERE id = :id LIMIT 1");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        json_response(['success' => false, 'message' => 'Certificate record not found.'], 404);
    }

    // Check uniqueness of certificate_id against OTHER records
    $dupStmt = $pdo->prepare("SELECT id FROM certificates WHERE certificate_id = :cert_id AND id != :id LIMIT 1");
    $dupStmt->execute([':cert_id' => $certificateId, ':id' => $id]);
    if ($dupStmt->fetch()) {
        json_response(['success' => false, 'message' => "Another certificate already uses ID '{$certificateId}'."], 409);
    }

    // Update record
    $stmt = $pdo->prepare("
        UPDATE certificates 
        SET 
            student_name     = :name,
            certificate_id   = :cert_id,
            certificate_type = :type,
            position         = :pos,
            start_date       = :start,
            end_date         = :end,
            issue_date       = :issue,
            status           = :status,
            updated_at       = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $stmt->execute([
        ':name'    => $studentName,
        ':cert_id' => $certificateId,
        ':type'    => $certificateType,
        ':pos'     => $position,
        ':start'   => $startDate,
        ':end'     => $endDate,
        ':issue'   => $issueDate,
        ':status'  => $status,
        ':id'      => $id
    ]);

    json_response([
        'success'        => true,
        'message'        => 'Certificate updated successfully.',
        'certificate_id' => $certificateId
    ]);

} catch (Exception $e) {
    error_log('Certificate Update Error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to update certificate: ' . $e->getMessage()], 500);
}

