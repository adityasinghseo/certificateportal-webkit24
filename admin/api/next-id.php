<?php
/**
 * Admin API: Generate Next Sequential Certificate ID
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_auth();

$type = trim($_GET['type'] ?? 'Internship');
if (!in_array($type, ['Internship', 'Training', 'Experience'])) {
    $type = 'Internship';
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2020 || $year > 2099) {
    $year = (int)date('Y');
}

try {
    $pdo = Database::getConnection();
    $nextId = generate_next_certificate_id($pdo, $type, $year);

    json_response([
        'success' => true,
        'next_id' => $nextId,
        'type'    => $type,
        'year'    => $year
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to generate ID'], 500);
}

