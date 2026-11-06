<?php
/**
 * Admin API: Certificate Statistics
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_auth();

try {
    $pdo = Database::getConnection();

    // Total count
    $total = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

    // Active count
    $active = (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'Active'")->fetchColumn();

    // Revoked count
    $revoked = (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'Revoked'")->fetchColumn();

    // Types count
    $typesStmt = $pdo->query("SELECT certificate_type, COUNT(*) as count FROM certificates GROUP BY certificate_type");
    $types = [
        'Internship' => 0,
        'Training'   => 0,
        'Experience' => 0
    ];
    while ($row = $typesStmt->fetch()) {
        $types[$row['certificate_type']] = (int)$row['count'];
    }

    $uniqueTypesCount = count(array_filter($types, function($v) { return $v > 0; }));

    json_response([
        'success' => true,
        'stats' => [
            'total'          => $total,
            'active'         => $active,
            'revoked'        => $revoked,
            'types_count'    => $uniqueTypesCount ?: 3,
            'breakdown'      => $types
        ]
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to load statistics: ' . $e->getMessage()], 500);
}

