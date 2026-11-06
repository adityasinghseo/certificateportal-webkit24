<?php
/**
 * Admin API: Delete Certificate
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
if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid certificate ID.'], 400);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("DELETE FROM certificates WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        json_response([
            'success' => true,
            'message' => 'Certificate deleted successfully.'
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Certificate not found or already deleted.'], 404);
    }
} catch (Exception $e) {
    error_log('Delete Error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to delete certificate: ' . $e->getMessage()], 500);
}

