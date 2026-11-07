<?php
/**
 * Webkit24 PDF Document Studio
 * Generate & Download Official Internship & Job/Employee Documents:
 * 
 * a- Internship Documents:
 *   1. Internship Offer Letter
 *   2. Internship Joining Letter
 *   3. Internship Completion Letter
 *   4. Internship Certificate
 *   5. Internship Relieving Letter
 * 
 * b- Job / Employee Documents:
 *   1. Job Offer Letter
 *   2. Job Appointment Letter
 *   3. Job Joining Letter
 *   4. Job Relieving Letter
 *   5. Job Experience Certificate
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

// Check if prefill ID was passed
$prefillId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$prefillCertId = clean_certificate_id($_GET['cert_id'] ?? '');

$initialCert = null;

try {
    $pdo = Database::getConnection();
    
    // Fetch all certificates for the quick selector
    $allCertsStmt = $pdo->query("SELECT id, student_name, certificate_id, position, start_date, end_date, issue_date FROM certificates ORDER BY id DESC LIMIT 100");
    $allCerts = $allCertsStmt->fetchAll();

    if ($prefillId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $prefillId]);
        $initialCert = $stmt->fetch();
    } elseif (!empty($prefillCertId)) {
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE certificate_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $prefillCertId]);
        $initialCert = $stmt->fetch();
    }
} catch (Exception $e) {
    $allCerts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Document Studio — Webkit24</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    
    <!-- Google Fonts for High-Fidelity Certificate & Letter Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/pdf-templates.css">
    
    <!-- QR Code Generator & Standalone PDF Exporter -->
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="../assets/js/html2pdf.bundle.min.js"></script>
    <style>
      .studio-layout {
        display: grid;
        grid-template-columns: 380px 1fr;
        min-height: calc(100vh - 72px);
        max-width: 100vw;
        overflow-x: hidden;
        transition: grid-template-columns 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      }
      .studio-layout.sidebar-collapsed {
        grid-template-columns: 0px 1fr;
      }
      .studio-sidebar {
        background: #FFFFFF;
        border-right: 1px solid var(--border-color);
        padding: 22px 20px;
        overflow-y: auto;
        height: calc(100vh - 72px);
        box-sizing: border-box;
        transition: opacity 0.2s ease;
      }
      .studio-layout.sidebar-collapsed .studio-sidebar {
        opacity: 0;
        pointer-events: none;
        overflow: hidden;
        padding: 0;
      }
      .studio-main {
        display: flex;
        flex-direction: column;
        background: #2D3136;
        height: calc(100vh - 72px);
        min-width: 0;
        overflow: hidden;
      }
      .studio-topbar {
        background: #202428;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #FFFFFF;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        z-index: 10;
        gap: 14px;
        flex-wrap: wrap;
      }
      .category-toggle-group {
        display: inline-flex;
        background: rgba(0, 0, 0, 0.4);
        border-radius: var(--radius-sm);
        padding: 3px;
        border: 1px solid rgba(255, 255, 255, 0.15);
      }
      .category-btn {
        background: transparent;
        border: none;
        color: #CBD5E1;
        font-family: var(--font-heading);
        font-size: 0.82rem;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
      .category-btn:hover {
        color: #FFFFFF;
        background: rgba(255, 255, 255, 0.08);
      }
      .category-btn.active {
        background: #FF4938;
        color: #FFFFFF;
        box-shadow: 0 2px 8px rgba(255, 73, 56, 0.4);
      }
      .doc-subtabs-row {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
      }
      .doc-tab-btn {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #E2E8F0;
        font-family: var(--font-heading);
        font-size: 0.78rem;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
      }
      .doc-tab-btn:hover {
        background: rgba(255, 255, 255, 0.14);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.35);
      }
      .doc-tab-btn.active {
        background: #FFFFFF;
        border-color: #FFFFFF;
        color: #080D25;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
      }
      .preview-scroll-area {
        flex: 1;
        overflow: auto;
        padding: 24px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        box-sizing: border-box;
      }
      .preview-scale-wrapper {
        transform-origin: top center;
        transition: transform 0.15s cubic-bezier(0.2, 0, 0, 1);
        display: inline-block;
        box-sizing: border-box;
      }
      .zoom-controls-group {
        display: inline-flex;
        align-items: center;
        background: rgba(0, 0, 0, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: var(--radius-sm);
        padding: 2px 4px;
        gap: 3px;
      }
      .zoom-btn {
        background: transparent;
        border: none;
        color: #CBD5E1;
        padding: 4px 8px;
        font-size: 0.74rem;
        font-weight: 600;
        border-radius: 3px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
      }
      .zoom-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #FFFFFF;
      }
      .zoom-btn.active {
        background: rgba(255, 255, 255, 0.25);
        color: #FFFFFF;
        font-weight: 700;
      }
      .zoom-level-badge {
        font-size: 0.74rem;
        font-weight: 700;
        font-family: var(--font-heading);
        color: #F8FAFC;
        min-width: 44px;
        text-align: center;
        padding: 0 4px;
      }
      .sidebar-toggle-btn {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #E2E8F0;
        padding: 6px 11px;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: var(--radius-sm);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
      }
      .sidebar-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #FFFFFF;
      }
      .form-section-title {
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--navy-muted);
        margin: 18px 0 10px;
        padding-bottom: 4px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 6px;
      }
      @media (max-width: 1024px) {
        .studio-layout {
          grid-template-columns: 1fr;
          height: auto;
        }
        .studio-sidebar, .studio-main {
          height: auto;
        }
      }
    </style>
</head>
<body class="admin-body">
    <!-- Top Navigation Bar -->
    <header class="admin-topbar">
        <div class="topbar-left">
            <a href="index.php" class="brand-link" title="Back to Admin Dashboard">
                <img src="../assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="brand-logo-img">
                <span class="brand-badge-pill">PDF Studio</span>
            </a>
        </div>

        <div class="topbar-center">
            <a href="index.php" class="nav-btn-link">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Back to Certificates Dashboard</span>
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

    <!-- Studio Split View -->
    <div class="studio-layout">
        <!-- Sidebar Controls -->
        <aside class="studio-sidebar">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--navy); margin-bottom: 4px;">Document Studio</h2>
            <p style="font-size: 0.85rem; color: var(--navy-muted); margin-bottom: 18px;">Fill details to generate official internship and job documents simultaneously</p>

            <!-- Quick Loader Dropdown -->
            <?php if (!empty($allCerts)): ?>
                <div class="form-group" style="margin-bottom: 20px; background: var(--bg-light); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">⚡ Quick-Load From Database</label>
                    <select id="quickLoadSelect" class="form-control form-select" style="font-size: 0.88rem;">
                        <option value="">-- Select an Existing Candidate --</option>
                        <?php foreach ($allCerts as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-json='<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>'
                                <?= ($initialCert && $initialCert['id'] == $c['id']) ? 'selected' : '' ?>
                            >
                                <?= e($c['student_name']) ?> (<?= e($c['certificate_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <form id="generatorForm" autocomplete="off">
                <div class="form-section-title">Candidate & Reference Information</div>

                <div class="form-group">
                    <label for="genStudentName" class="form-label required">Student / Employee Full Name</label>
                    <input type="text" id="genStudentName" class="form-control" value="<?= e($initialCert['student_name'] ?? 'Hariom Pandey') ?>" required>
                </div>

                <div class="form-group">
                    <label for="genCertId" class="form-label required">Certificate / Document Ref ID</label>
                    <input type="text" id="genCertId" class="form-control font-mono" value="<?= e($initialCert['certificate_id'] ?? 'WEBKIT24/INT/2026/0224') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genRole" class="form-label required">Role / Designation</label>
                        <input type="text" id="genRole" class="form-control" value="<?= e($initialCert['position'] ?? 'Frontend Developer (Intern)') ?>" placeholder="Frontend Developer (Intern)" required>
                    </div>
                    <div class="form-group col-6">
                        <label for="genDomain" class="form-label required">Domain / Track</label>
                        <input type="text" id="genDomain" class="form-control" value="Frontend Web Development" placeholder="Frontend Web Development" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genStartDate" class="form-label required">Start / Joining Date</label>
                        <input type="date" id="genStartDate" class="form-control" value="<?= e($initialCert['start_date'] ?? '2026-06-15') ?>" required>
                    </div>
                    <div class="form-group col-6">
                        <label for="genEndDate" class="form-label required">End / Relieving Date</label>
                        <input type="date" id="genEndDate" class="form-control" value="<?= e($initialCert['end_date'] ?? '2026-07-30') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genIssueDate" class="form-label required">Issue / Offer Date</label>
                        <input type="date" id="genIssueDate" class="form-control" value="<?= e($initialCert['issue_date'] ?? '2026-08-10') ?>" required>
                    </div>
                    <div class="form-group col-6">
                        <label for="genJobType" class="form-label required">Work Mode / Type</label>
                        <select id="genJobType" class="form-control form-select">
                            <option value="Part-time (Remote)">Part-time (Remote)</option>
                            <option value="Full-time (Remote)">Full-time (Remote)</option>
                            <option value="Full-time (On-site)">Full-time (On-site)</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-title">Employment & Corporate Specifics</div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genSalaryCtc" class="form-label">Annual CTC / Salary</label>
                        <input type="text" id="genSalaryCtc" class="form-control" value="₹ 6,50,000 Per Annum" placeholder="₹ 6,50,000 Per Annum">
                    </div>
                    <div class="form-group col-6">
                        <label for="genReportingManager" class="form-label">Reporting Manager / Lead</label>
                        <input type="text" id="genReportingManager" class="form-control" value="Aditya Singh (Lead Engineer)" placeholder="Aditya Singh (Lead Engineer)">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genWorkLocation" class="form-label">Office Location</label>
                        <input type="text" id="genWorkLocation" class="form-control" value="Lucknow HQ / Remote" placeholder="Lucknow HQ / Remote">
                    </div>
                    <div class="form-group col-6">
                        <label for="genProbation" class="form-label">Probation Period</label>
                        <input type="text" id="genProbation" class="form-control" value="3 Months" placeholder="3 Months">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="genNoticePeriod" class="form-label">Notice Period</label>
                        <input type="text" id="genNoticePeriod" class="form-control" value="30 Days" placeholder="30 Days">
                    </div>
                    <div class="form-group col-6">
                        <label for="genPronoun" class="form-label">Pronoun in Text</label>
                        <select id="genPronoun" class="form-control form-select">
                            <option value="he">He / Him / His</option>
                            <option value="she">She / Her / Her</option>
                            <option value="they">They / Them / Their</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="genBullets" class="form-label">Responsibilities / Performance Notes (One per line)</label>
                    <textarea id="genBullets" class="form-control" rows="5" style="font-size: 0.85rem; line-height: 1.4;">Gain hands-on experience in frontend web development.
Work with HTML, CSS, JavaScript, and modern frontend technologies.
Develop responsive and user-friendly web interfaces.
Collaborate with experienced developers on real-world projects.
Learn to integrate frontend designs with backend APIs.
Identify and fix UI bugs to improve website performance and user experience.
Participate in team meetings, code reviews, and brainstorming sessions.</textarea>
                </div>
            </form>
        </aside>

        <!-- Main Preview Area -->
        <main class="studio-main">
            <!-- Studio Toolbar -->
            <div class="studio-topbar">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Category Tabs & Active Orientation Badge -->
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div class="category-toggle-group">
                            <button type="button" class="category-btn active" id="catBtnInternship" data-category="internship">
                                <span>🎓 Internship Documents (5)</span>
                            </button>
                            <button type="button" class="category-btn" id="catBtnJob" data-category="job">
                                <span>💼 Job / Employee Documents (5)</span>
                            </button>
                        </div>
                        <span id="activeDocBadge" style="font-size: 0.76rem; color: #94A3B8; background: rgba(255,255,255,0.08); padding: 4px 10px; border-radius: var(--radius-full); font-weight: 600;">
                            A4 Landscape (297 × 210 mm)
                        </span>
                    </div>

                    <!-- Dynamic Sub-tabs for the Active Category -->
                    <div class="doc-subtabs-row" id="docSubtabsContainer">
                        <!-- Populated dynamically by pdf-generator.js -->
                    </div>
                </div>

                <!-- Toolbar Actions: Sidebar Toggle, Zoom Controls, Print & Download -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <!-- Sidebar Toggle Button -->
                    <button type="button" class="sidebar-toggle-btn" id="btnToggleSidebar" title="Toggle Sidebar for Full-Screen Document View">
                        <span id="sidebarToggleText">◀ Hide Form</span>
                    </button>

                    <!-- Interactive Zoom Controls -->
                    <div class="zoom-controls-group">
                        <button type="button" class="zoom-btn" id="btnZoomOut" title="Zoom Out">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px;"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                        </button>
                        <span class="zoom-level-badge" id="zoomLevelBadge">Fit</span>
                        <button type="button" class="zoom-btn" id="btnZoomIn" title="Zoom In">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px;"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                        </button>
                        <button type="button" class="zoom-btn active" id="btnZoomFit" title="Fit Entire Document in Screen">
                            Fit
                        </button>
                        <button type="button" class="zoom-btn" id="btnZoom100" title="Actual Size (100%)">
                            100%
                        </button>
                    </div>

                    <button type="button" class="btn btn-secondary btn-sm" id="btnPrintActiveDoc">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print</span>
                    </button>

                    <button type="button" class="btn btn-primary" id="btnDownloadActivePdf">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width: 18px; height: 18px;">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span id="downloadBtnText">Download PDF</span>
                    </button>
                </div>
            </div>

            <!-- Preview Canvas Scroll Area -->
            <div class="preview-scroll-area" id="previewScrollArea">
                <!-- Scaled responsive wrapper so the document fits 100% of the screen -->
                <div id="previewScaleWrapper" class="preview-scale-wrapper">
                    <!-- Active Document Container Rendered Dynamically -->
                    <div id="activeDocRenderArea">
                        <!-- Rendered dynamically by pdf-generator.js -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/pdf-generator.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Webkit24PDF.init();

            // Quick load dropdown listener
            const quickSelect = document.getElementById('quickLoadSelect');
            if (quickSelect) {
                quickSelect.addEventListener('change', () => {
                    const selected = quickSelect.options[quickSelect.selectedIndex];
                    const dataJson = selected.getAttribute('data-json');
                    if (dataJson) {
                        try {
                            const cert = JSON.parse(dataJson);
                            window.Webkit24PDF.loadCertificateRecord(cert);
                        } catch (e) {
                            console.error(e);
                        }
                    }
                });
            }

            // Check if initial cert was injected
            <?php if ($initialCert): ?>
                window.Webkit24PDF.loadCertificateRecord(<?= json_encode($initialCert) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>
