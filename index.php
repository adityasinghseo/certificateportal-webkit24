<?php
/**
 * Webkit24 Certificate Verification Portal
 * Homepage: https://certificate.webkit24.com/
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Check if an ID query param was supplied (e.g. from a QR Code scan)
$prefillId = clean_certificate_id($_GET['id'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification — Webkit24</title>
    <meta name="description" content="Verify the authenticity of internship, training, and experience certificates issued by Webkit24 using a unique Certificate ID.">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Typography: Plus Jakarta Sans for headings, Inter for clean body copy -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css?v=2.1.0">
    
    <!-- Standalone Client-side QR Code Generator -->
    <script src="assets/js/qrcode.min.js?v=2.1.0"></script>
</head>
<body class="site-body">
    <!-- Top Announcement / Trust Banner -->
    <div class="top-announcement">
        <div class="container announcement-inner">
            <span class="announcement-pill">Official Portal</span>
            <span class="announcement-text">Official credential verification portal of Webkit24</span>
            <a href="https://webkit24.com" target="_blank" rel="noopener" class="announcement-link">
                Visit webkit24.com &rarr;
            </a>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="brand-link" title="Webkit24 Certificate Portal">
                <img src="assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="brand-logo-img">
                <span class="brand-badge-pill">Verification Portal</span>
            </a>

            <nav class="site-nav">
                <ul class="nav-list">
                    <li><a href="/" class="nav-link active">Home</a></li>
                    <li><a href="https://webkit24.com/#about" target="_blank" rel="noopener" class="nav-link">About Webkit24</a></li>
                    <li><a href="https://webkit24.com/#contact" target="_blank" rel="noopener" class="nav-link">Contact</a></li>
                </ul>
            </nav>

            <div class="header-action">
                <a href="admin/login.php" class="btn btn-outline-navy btn-sm" title="Authorized Administrator Portal">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="btn-icon-left">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    <span>Admin Portal</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section" id="verifySection">
            <div class="container hero-container">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    <span>Secure Credential Validation</span>
                </div>

                <h1 class="hero-heading">Certificate Verification</h1>
                
                <p class="hero-subheading">
                    Verify the authenticity of certificates issued by Webkit24. Enter your unique Certificate ID below to verify your certificate.
                </p>

                <!-- Search Form Box -->
                <div class="verification-card">
                    <form id="verifyForm" class="verification-form" autocomplete="off">
                        <div class="search-input-group">
                            <div class="input-icon-prefix">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="certIdInput" 
                                name="id" 
                                class="cert-input" 
                                placeholder="WEBKIT24/INT/2026/0035" 
                                value="<?= e($prefillId) ?>"
                                spellcheck="false"
                                required
                            >
                            <button type="button" id="btnClearInput" class="btn-clear-id" title="Clear input" style="display: none;">
                                &times;
                            </button>
                            <button type="submit" id="btnVerify" class="btn btn-primary btn-verify">
                                <span class="btn-label">VERIFY CERTIFICATE</span>
                                <div class="btn-spinner" style="display: none;"></div>
                            </button>
                        </div>
                    </form>

                    <div class="form-helper">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="helper-icon">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Enter the Certificate ID printed on your official Webkit24 certificate.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Result Section (Hidden until verified) -->
        <section class="result-section" id="resultContainer" style="display: none;">
            <div class="container">
                <!-- RESULT STATE 1: VALID & VERIFIED -->
                <div class="result-card result-card-valid" id="validCard" style="display: none;">
                    <div class="result-card-header">
                        <div class="verification-badge-large">
                            <div class="badge-icon-circle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <div class="badge-header-text">
                            <span class="status-pill status-pill-success">
                                <span class="pulse-dot"></span> Valid Certificate
                            </span>
                            <h2 class="result-title">Certificate Verified Successfully</h2>
                            <p class="result-sub">Official credential issued and validated in Webkit24 official records</p>
                        </div>
                    </div>

                    <div class="result-card-body">
                        <!-- Key Certificate Attributes Grid -->
                        <div class="cert-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Student Name</span>
                                <span class="detail-value text-navy font-bold text-lg" id="resStudentName">—</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Certificate ID</span>
                                <span class="detail-value font-mono code-pill" id="resCertId">—</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Certificate Type</span>
                                <span class="detail-value" id="resCertType">—</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Internship / Training Role</span>
                                <span class="detail-value text-navy font-semibold" id="resPosition">—</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Tenure Duration</span>
                                <span class="detail-value"><span id="resStartDate">—</span> – <span id="resEndDate">—</span></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Issue Date</span>
                                <span class="detail-value" id="resIssueDate">—</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Issuing Organization</span>
                                <span class="detail-value font-semibold">Webkit24</span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Certificate Status</span>
                                <span class="detail-value">
                                    <span class="badge-valid-status">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span id="resStatus">Valid</span>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Official Confirmation Box -->
                        <div class="confirmation-box">
                            <div class="confirmation-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="confirmation-content">
                                <h4 class="confirmation-title">Official Verification Guarantee</h4>
                                <p class="confirmation-message" id="resConfirmation">
                                    This certificate has been officially issued and verified by Webkit24.
                                </p>
                            </div>
                        </div>

                        <!-- QR Code & Direct Link Box -->
                        <div class="qr-share-box">
                            <div class="qr-left">
                                <div id="publicQrCode" class="qr-code-canvas"></div>
                            </div>
                            <div class="qr-right">
                                <h5 class="qr-title">Instant QR Verification</h5>
                                <p class="qr-desc">Scan this QR code with any mobile device to directly inspect this official record anytime.</p>
                                <div class="copy-url-group">
                                    <input type="text" id="shareUrlInput" class="copy-url-input" readonly>
                                    <button type="button" id="btnCopyShareUrl" class="btn btn-secondary btn-sm">
                                        <span id="copyShareBtnText">Copy Link</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Result Card Footer -->
                    <div class="result-card-footer">
                        <button type="button" class="btn btn-secondary btn-reset-verification">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                            </svg>
                            <span>Verify Another Certificate</span>
                        </button>

                        <button type="button" class="btn btn-outline-navy" id="btnPrintResult">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Print / Save Record</span>
                        </button>
                    </div>
                </div>

                <!-- RESULT STATE 2: NOT FOUND -->
                <div class="result-card result-card-invalid" id="notFoundCard" style="display: none;">
                    <div class="invalid-icon-wrapper invalid-icon-notfound">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h2 class="invalid-title">Certificate Not Found</h2>
                    <p class="invalid-description">
                        The entered Certificate ID could not be found in our records. Please check the ID and try again.
                    </p>

                    <div class="search-tip-card">
                        <span class="tip-title">Verification Tips:</span>
                        <ul>
                            <li>Verify you typed the exact ID format: <code>WEBKIT24/INT/2026/0035</code></li>
                            <li>Ensure there are no leading or trailing spaces</li>
                            <li>Check the bottom or top header of your printed Webkit24 certificate</li>
                        </ul>
                    </div>

                    <div class="invalid-actions">
                        <button type="button" class="btn btn-primary btn-reset-verification">
                            <span>Try Another Certificate ID</span>
                        </button>
                        <a href="https://webkit24.com/#contact" target="_blank" rel="noopener" class="btn btn-secondary">
                            <span>Contact Support</span>
                        </a>
                    </div>
                </div>

                <!-- RESULT STATE 3: REVOKED -->
                <div class="result-card result-card-revoked" id="revokedCard" style="display: none;">
                    <div class="invalid-icon-wrapper invalid-icon-revoked">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>

                    <span class="status-pill status-pill-revoked">Status: Revoked</span>
                    <h2 class="invalid-title text-red">Certificate Revoked</h2>
                    <p class="invalid-description">
                        This certificate was officially issued by Webkit24 but has been revoked. It is no longer valid or recognized for employment, academic, or professional verification.
                    </p>

                    <div class="revoked-info-box">
                        <div><strong>Revoked ID:</strong> <span id="revokedCertId" class="font-mono">—</span></div>
                        <div><strong>Recipient:</strong> <span id="revokedStudentName">—</span></div>
                    </div>

                    <div class="invalid-actions">
                        <button type="button" class="btn btn-secondary btn-reset-verification">
                            <span>Verify Another Certificate</span>
                        </button>
                        <a href="https://webkit24.com/#contact" target="_blank" rel="noopener" class="btn btn-outline-navy">
                            <span>Inquire With Webkit24</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Informational Section: How It Works / Trust -->
        <section class="features-section">
            <div class="container">
                <div class="section-header text-center">
                    <span class="section-tag">Authenticity System</span>
                    <h2 class="section-title">Trusted Credential Verification</h2>
                    <p class="section-subtitle">Webkit24 maintains tamper-proof records for all verified graduates, interns, and employees.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Instant Authenticity Check</h3>
                        <p class="feature-text">Employers and institutions can immediately confirm certificate validity without manual email confirmation delays.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <h3 class="feature-title">QR Code Ready</h3>
                        <p class="feature-text">Every official certificate features a cryptographic QR code that opens directly to this verification page.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="feature-title">Centralized Registry</h3>
                        <p class="feature-text">Strict database records prevent credential forgery, duplicate IDs, and unauthorized alterations.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="assets/images/webkit24-logo-white.png" alt="Webkit24 Logo" class="footer-logo">
                    <p class="footer-desc">
                        Webkit24 is a digital marketing, web design, and technology software agency delivering end-to-end digital solutions.
                    </p>
                </div>

                <div class="footer-links-group">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-nav">
                        <li><a href="https://webkit24.com" target="_blank" rel="noopener">Main Website</a></li>
                        <li><a href="https://webkit24.com/#about" target="_blank" rel="noopener">About Us</a></li>
                        <li><a href="https://webkit24.com/#services" target="_blank" rel="noopener">Services</a></li>
                        <li><a href="https://webkit24.com/#contact" target="_blank" rel="noopener">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-links-group">
                    <h4 class="footer-heading">Security & Trust</h4>
                    <ul class="footer-nav">
                        <li><a href="/">Certificate Verification</a></li>
                        <li><a href="https://webkit24.com/privacy-policy" target="_blank" rel="noopener">Privacy Policy</a></li>
                        <li><a href="admin/login.php">Admin Portal</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-copy">© 2026 Webkit24. All Rights Reserved.</p>
                <div class="footer-sub-links">
                    <a href="https://webkit24.com" target="_blank" rel="noopener">Main Website</a>
                    <span class="sep">•</span>
                    <a href="https://webkit24.com/#contact" target="_blank" rel="noopener">Contact Us</a>
                    <span class="sep">•</span>
                    <a href="https://webkit24.com/privacy-policy" target="_blank" rel="noopener">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- App JavaScript -->
    <script src="assets/js/app.js?v=2.1.0"></script>
</body>
</html>

