<?php
/**
 * Webkit24 Admin Dashboard
 * Certificate Management & Issuance Portal
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin_auth();

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';
$csrfToken = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Webkit24 Certificate Authority</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/pdf-templates.css">
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="../assets/js/html2pdf.bundle.min.js"></script>
</head>
<body class="admin-body">
    <!-- CSRF Token Meta -->
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">

    <div class="admin-layout">
        <!-- Top Navigation Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <a href="index.php" class="brand-link" title="Webkit24 Admin Portal">
                    <img src="../assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="brand-logo-img">
                    <span class="brand-badge-pill">Admin Panel</span>
                </a>
            </div>

            <div class="topbar-center">
                <a href="generator.php" class="nav-btn-link" title="Open PDF Studio">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                    <span>PDF Document Studio</span>
                </a>
                <a href="../" target="_blank" class="nav-btn-link" title="View Public Verification Portal">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                    </svg>
                    <span>Public Verification</span>
                </a>
            </div>

            <div class="topbar-right">
                <div class="user-profile-badge">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($adminUsername, 0, 1)) ?>
                    </div>
                    <div class="user-meta">
                        <span class="user-name"><?= e($adminUsername) ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>

                <a href="logout.php" class="btn-logout" title="Sign Out">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Certificate Management</h1>
                    <p class="page-subtitle">Issue, track, verify, and manage Webkit24 student credentials</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <a href="generator.php" class="btn btn-secondary">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width: 18px; height: 18px; color: var(--primary);">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                        </svg>
                        <span>PDF Document Studio</span>
                    </a>
                    <button type="button" class="btn btn-primary btn-add-cert" id="btnOpenAddModal">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Issue New Certificate</span>
                    </button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon-wrap stat-icon-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Total Certificates Issued</span>
                        <h3 class="stat-value" id="statTotal">—</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon-wrap stat-icon-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Active Certificates</span>
                        <h3 class="stat-value text-green" id="statActive">—</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon-wrap stat-icon-red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Revoked Certificates</span>
                        <h3 class="stat-value text-red" id="statRevoked">—</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon-wrap stat-icon-orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Certificate Types</span>
                        <h3 class="stat-value text-orange" id="statTypes">3 Types</h3>
                    </div>
                </div>
            </section>

            <!-- Table & Filters Section -->
            <section class="card table-card">
                <!-- Action / Filter Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-search">
                        <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        <input 
                            type="text" 
                            id="tableSearch" 
                            class="form-control" 
                            placeholder="Search by student name, ID, or role..."
                        >
                        <button type="button" class="btn-clear-search" id="btnClearSearch" title="Clear search" style="display: none;">&times;</button>
                    </div>

                    <div class="toolbar-filters">
                        <div class="select-wrapper">
                            <select id="filterType" class="form-control form-select">
                                <option value="">All Types</option>
                                <option value="Internship">Internship</option>
                                <option value="Training">Training</option>
                                <option value="Experience">Experience</option>
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <select id="filterStatus" class="form-control form-select">
                                <option value="">All Statuses</option>
                                <option value="Active">Active</option>
                                <option value="Revoked">Revoked</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-icon-only" id="btnRefresh" title="Refresh Table">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Certificates Table -->
                <div class="table-responsive">
                    <table class="data-table" id="certsTable">
                        <thead>
                            <tr>
                                <th>Certificate ID</th>
                                <th>Student Name</th>
                                <th>Type</th>
                                <th>Role / Position</th>
                                <th>Issue Date</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="loading-spinner"></div>
                                    <p class="text-muted mt-2">Loading certificates database...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Pagination -->
                <div class="pagination-bar" id="paginationBar">
                    <span class="pagination-info" id="paginationInfo">Showing 0 of 0 certificates</span>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Dynamic buttons -->
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ==================== MODAL: ADD / EDIT CERTIFICATE ==================== -->
    <div class="modal-backdrop" id="certModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modalTitle">Issue New Certificate</h3>
                    <p class="modal-subtitle">Fill in student credential details</p>
                </div>
                <button type="button" class="btn-close" id="btnCloseCertModal">&times;</button>
            </div>

            <form id="certForm" autocomplete="off">
                <input type="hidden" id="editCertId" name="id" value="">
                
                <div class="modal-body">
                    <div class="alert-box alert-error" id="modalAlert" style="display: none;"></div>

                    <div class="form-row">
                        <div class="form-group col-12">
                            <label for="studentName" class="form-label required">Student Full Name</label>
                            <input 
                                type="text" 
                                id="studentName" 
                                name="student_name" 
                                class="form-control" 
                                placeholder="e.g. Hariom Pandey" 
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="certType" class="form-label required">Certificate Type</label>
                            <select id="certType" name="certificate_type" class="form-control form-select" required>
                                <option value="Internship">Internship Certificate</option>
                                <option value="Training">Training Certificate</option>
                                <option value="Experience">Experience Certificate</option>
                            </select>
                        </div>

                        <div class="form-group col-6">
                            <label for="certStatus" class="form-label required">Initial Status</label>
                            <select id="certStatus" name="status" class="form-control form-select" required>
                                <option value="Active">Active (Valid)</option>
                                <option value="Revoked">Revoked</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12">
                            <div class="d-flex justify-between align-center mb-1">
                                <label for="certIdInput" class="form-label required">Certificate ID</label>
                                <button type="button" class="btn-link-action" id="btnAutoGenerateId">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Auto-Generate Sequential ID</span>
                                </button>
                            </div>
                            <input 
                                type="text" 
                                id="certIdInput" 
                                name="certificate_id" 
                                class="form-control font-mono" 
                                placeholder="WEBKIT24/INT/2026/0001" 
                                required
                            >
                            <span class="form-hint">Format: WEBKIT24/{INT|TRN|EXP}/{YEAR}/{0001}. Unique per certificate.</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12">
                            <label for="position" class="form-label required">Internship / Job Role</label>
                            <input 
                                type="text" 
                                id="position" 
                                name="position" 
                                class="form-control" 
                                placeholder="e.g. Frontend Developer Intern" 
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="startDate" class="form-label required">Start Date</label>
                            <input type="date" id="startDate" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group col-4">
                            <label for="endDate" class="form-label required">End Date</label>
                            <input type="date" id="endDate" name="end_date" class="form-control" required>
                        </div>
                        <div class="form-group col-4">
                            <label for="issueDate" class="form-label required">Issue Date</label>
                            <input type="date" id="issueDate" name="issue_date" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelCertModal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveCert">
                        <span id="saveBtnText">Save Certificate</span>
                        <div class="btn-spinner" id="saveSpinner" style="display: none;"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL: VIEW DETAILS & QR CODE ==================== -->
    <div class="modal-backdrop" id="viewModal">
        <div class="modal-dialog modal-dialog-lg">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Certificate Details & QR Code</h3>
                    <p class="modal-subtitle">Official verifiable record</p>
                </div>
                <button type="button" class="btn-close" id="btnCloseViewModal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="cert-detail-grid">
                    <div class="cert-qr-container">
                        <div id="modalQrCode" class="qr-canvas-box"></div>
                        <div class="qr-label">Scan to Verify</div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 w-100" id="btnCopyVerifyUrl">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                            </svg>
                            <span id="copyUrlText">Copy Verification Link</span>
                        </button>
                        <a href="#" target="_blank" class="btn btn-primary btn-sm mt-1 w-100" id="btnOpenPublicVerify">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                            </svg>
                            <span>Open Public Verification</span>
                        </a>
                        <a href="#" class="btn btn-outline-navy btn-sm mt-1 w-100" id="btnOpenPdfStudio" style="border-color: var(--primary); color: var(--primary);">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                            </svg>
                            <span>Generate Offer Letter / Certificate PDF</span>
                        </a>
                    </div>

                    <div class="cert-info-table-wrap">
                        <table class="cert-spec-table">
                            <tr>
                                <th>Student Name</th>
                                <td id="vStudentName" class="font-bold">—</td>
                            </tr>
                            <tr>
                                <th>Certificate ID</th>
                                <td><span id="vCertId" class="code-badge font-mono">—</span></td>
                            </tr>
                            <tr>
                                <th>Certificate Type</th>
                                <td id="vCertType">—</td>
                            </tr>
                            <tr>
                                <th>Role / Position</th>
                                <td id="vPosition">—</td>
                            </tr>
                            <tr>
                                <th>Tenure</th>
                                <td><span id="vStartDate">—</span> to <span id="vEndDate">—</span></td>
                            </tr>
                            <tr>
                                <th>Issue Date</th>
                                <td id="vIssueDate">—</td>
                            </tr>
                            <tr>
                                <th>Issued By</th>
                                <td><strong>Webkit24</strong></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span id="vStatusBadge" class="badge-status">—</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCloseViewModalBtn">Close</button>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL: DELETE CONFIRMATION ==================== -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal-dialog modal-dialog-sm">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title text-red">Confirm Certificate Deletion</h3>
                    <p class="modal-subtitle">Permanent database removal</p>
                </div>
                <button type="button" class="btn-close" id="btnCloseDeleteModal">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Are you sure you want to delete this certificate record? Once deleted, any public verification will return <strong>"Certificate Not Found"</strong>.</p>
                <div class="delete-cert-preview">
                    <span id="delCertId" class="font-mono font-bold">—</span>
                    <span id="delStudentName" class="text-muted">—</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelDelete">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <span>Delete Permanently</span>
                    <div class="btn-spinner" id="delSpinner" style="display: none;"></div>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification"></div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

