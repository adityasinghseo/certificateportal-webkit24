<?php
/**
 * Admin API: Toggle Certificate Status (Active <-> Revoked)
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

$id = (int)($input['id'] ?? 0);
$explicitStatus = isset($input['status']) ? trim($input['status']) : null;

if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid certificate ID.'], 400);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id, status, certificate_id FROM certificates WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $cert = $stmt->fetch();

    if (!$cert) {
        json_response(['success' => false, 'message' => 'Certificate not found.'], 404);
    }

    if ($explicitStatus && in_array($explicitStatus, ['Active', 'Revoked'])) {
        $newStatus = $explicitStatus;
    } else {
        $newStatus = ($cert['status'] === 'Active') ? 'Revoked' : 'Active';
    }

    $updateStmt = $pdo->prepare("UPDATE certificates SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $updateStmt->execute([':status' => $newStatus, ':id' => $id]);

    json_response([
        'success'    => true,
        'id'         => $id,
        'new_status' => $newStatus,
        'message'    => "Certificate status successfully set to {$newStatus}."
    ]);
} catch (Exception $e) {
    error_log('Toggle Status Error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to update certificate status: ' . $e->getMessage()], 500);
}

