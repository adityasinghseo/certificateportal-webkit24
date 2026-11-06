<?php
/**
 * Admin API: Create Certificate
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

// Extract input (support json body or form post)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$studentName     = trim($input['student_name'] ?? '');
$certificateType = trim($input['certificate_type'] ?? 'Internship');
$certificateId   = clean_certificate_id($input['certificate_id'] ?? '');
$position        = trim($input['position'] ?? '');
$startDate       = trim($input['start_date'] ?? '');
$endDate         = trim($input['end_date'] ?? '');
$issueDate       = trim($input['issue_date'] ?? date('Y-m-d'));
$status          = trim($input['status'] ?? 'Active');

// Validations
$errors = [];
if (empty($studentName)) {
    $errors[] = 'Student Full Name is required.';
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

    // If ID is empty, auto-generate sequential unique ID
    if (empty($certificateId)) {
        $certificateId = generate_next_certificate_id($pdo, $certificateType, date('Y', strtotime($issueDate)));
    }

    // Check uniqueness
    $checkStmt = $pdo->prepare("SELECT id FROM certificates WHERE certificate_id = :id LIMIT 1");
    $checkStmt->execute([':id' => $certificateId]);
    if ($checkStmt->fetch()) {
        json_response([
            'success' => false,
            'message' => "Certificate ID '{$certificateId}' is already registered in the database. Please provide or generate a unique ID."
        ], 409);
    }

    // Insert record using prepared statement
    $stmt = $pdo->prepare("
        INSERT INTO certificates 
        (student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status, created_at)
        VALUES 
        (:name, :cert_id, :type, :pos, :start, :end, :issue, :status, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        ':name'    => $studentName,
        ':cert_id' => $certificateId,
        ':type'    => $certificateType,
        ':pos'     => $position,
        ':start'   => $startDate,
        ':end'     => $endDate,
        ':issue'   => $issueDate,
        ':status'  => $status
    ]);

    $newId = $pdo->lastInsertId();

    json_response([
        'success'        => true,
        'message'        => 'Certificate saved successfully.',
        'certificate_id' => $certificateId,
        'id'             => $newId
    ], 201);

} catch (Exception $e) {
    error_log('Certificate Create Error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to save certificate: ' . $e->getMessage()], 500);
}

