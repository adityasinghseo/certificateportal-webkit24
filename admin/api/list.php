<?php
/**
 * Admin API: List & Search Certificates
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_auth();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type   = trim($_GET['type'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(100, max(5, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

try {
    $pdo = Database::getConnection();

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(student_name LIKE :search1 OR certificate_id LIKE :search2 OR position LIKE :search3)";
        $searchParam = "%{$search}%";
        $params[':search1'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
    }

    if ($status !== '' && in_array($status, ['Active', 'Revoked'])) {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }

    if ($type !== '' && in_array($type, ['Internship', 'Training', 'Experience'])) {
        $where[] = "certificate_type = :type";
        $params[':type'] = $type;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total filtered records
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM certificates {$whereSql}");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Fetch records
    $sql = "SELECT id, student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status, created_at 
            FROM certificates 
            {$whereSql} 
            ORDER BY id DESC 
            LIMIT {$limit} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format dates for display
    $certificates = array_map(function($row) {
        $row['start_date_formatted'] = format_display_date($row['start_date']);
        $row['end_date_formatted']   = format_display_date($row['end_date']);
        $row['issue_date_formatted'] = format_display_date($row['issue_date']);
        $row['verification_url']     = (defined('PORTAL_URL') ? PORTAL_URL : 'https://certificate.webkit24.com') . '/?id=' . urlencode($row['certificate_id']);
        return $row;
    }, $rows);

    json_response([
        'success'      => true,
        'certificates' => $certificates,
        'pagination'   => [
            'total'        => $totalRecords,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => ceil($totalRecords / $limit)
        ]
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to retrieve certificates: ' . $e->getMessage()], 500);
}

