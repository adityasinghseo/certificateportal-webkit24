/**
 * Webkit24 Certificate Verification Portal
 * Frontend Application Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    const verifyForm = document.getElementById('verifyForm');
    const certInput = document.getElementById('certIdInput');
    const btnClear = document.getElementById('btnClearInput');
    const btnVerify = document.getElementById('btnVerify');
    const btnSpinner = btnVerify ? btnVerify.querySelector('.btn-spinner') : null;
    const btnLabel = btnVerify ? btnVerify.querySelector('.btn-label') : null;
    
    // Result cards container
    const resultContainer = document.getElementById('resultContainer');
    const validCard = document.getElementById('validCard');
    const notFoundCard = document.getElementById('notFoundCard');
    const revokedCard = document.getElementById('revokedCard');
    
    // Valid card fields
    const resStudentName = document.getElementById('resStudentName');
    const resCertId = document.getElementById('resCertId');
    const resCertType = document.getElementById('resCertType');
    const resPosition = document.getElementById('resPosition');
    const resStartDate = document.getElementById('resStartDate');
    const resEndDate = document.getElementById('resEndDate');
    const resIssueDate = document.getElementById('resIssueDate');
    const resStatus = document.getElementById('resStatus');
    const resConfirmation = document.getElementById('resConfirmation');
    const publicQrCodeContainer = document.getElementById('publicQrCode');
    const shareUrlInput = document.getElementById('shareUrlInput');
    const btnCopyShareUrl = document.getElementById('btnCopyShareUrl');
    const copyShareBtnText = document.getElementById('copyShareBtnText');
    const btnPrintResult = document.getElementById('btnPrintResult');
    
    // Revoked card fields
    const revokedCertId = document.getElementById('revokedCertId');
    const revokedStudentName = document.getElementById('revokedStudentName');
    
    // Reset buttons
    const resetButtons = document.querySelectorAll('.btn-reset-verification');

    let currentQr = null;

    // Helper: Toggle clear button visibility
    function updateClearButton() {
        if (!btnClear || !certInput) return;
        btnClear.style.display = certInput.value.trim() ? 'block' : 'none';
    }

    if (certInput) {
        certInput.addEventListener('input', () => {
            // Keep input uppercase
            certInput.value = certInput.value.toUpperCase();
            updateClearButton();
        });

        certInput.addEventListener('focus', () => {
            updateClearButton();
        });
    }

    if (btnClear) {
        btnClear.addEventListener('click', () => {
            certInput.value = '';
            updateClearButton();
            certInput.focus();
        });
    }

    // Helper: Set loading state
    function setLoading(isLoading) {
        if (!btnVerify) return;
        btnVerify.disabled = isLoading;
        if (btnSpinner) btnSpinner.style.display = isLoading ? 'block' : 'none';
        if (btnLabel) btnLabel.style.display = isLoading ? 'none' : 'block';
    }

    // Helper: Hide all result cards
    function hideAllResults() {
        if (resultContainer) resultContainer.style.display = 'none';
        if (validCard) validCard.style.display = 'none';
        if (notFoundCard) notFoundCard.style.display = 'none';
        if (revokedCard) revokedCard.style.display = 'none';
    }

    // Helper: Scroll to element smoothly
    function smoothScrollTo(element) {
        if (!element) return;
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Verification Logic
    async function performVerification(certId) {
        const id = (certId || (certInput ? certInput.value : '')).trim().toUpperCase();

        if (!id) {
            alert('Please enter a Certificate ID.');
            if (certInput) certInput.focus();
            return;
        }

        setLoading(true);
        hideAllResults();

        try {
            const response = await fetch(`api/verify.php?id=${encodeURIComponent(id)}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            resultContainer.style.display = 'block';

            if (response.ok && data.success && data.status === 'valid') {
                // Status 1: Valid & Verified
                const cert = data.certificate;
                if (resStudentName) resStudentName.textContent = cert.student_name;
                if (resCertId) resCertId.textContent = cert.certificate_id;
                if (resCertType) resCertType.textContent = cert.certificate_type + ' Certificate';
                if (resPosition) resPosition.textContent = cert.position;
                if (resStartDate) resStartDate.textContent = cert.start_date_formatted || cert.start_date;
                if (resEndDate) resEndDate.textContent = cert.end_date_formatted || cert.end_date;
                if (resIssueDate) resIssueDate.textContent = cert.issue_date_formatted || cert.issue_date;
                if (resStatus) resStatus.textContent = cert.status;
                if (resConfirmation && data.confirmation) resConfirmation.textContent = data.confirmation;

                // Set verification URL
                const verifyUrl = cert.verification_url || `${window.location.origin}/?id=${encodeURIComponent(cert.certificate_id)}`;
                if (shareUrlInput) shareUrlInput.value = verifyUrl;

                // Render QR code
                if (publicQrCodeContainer && typeof QRCode !== 'undefined') {
                    publicQrCodeContainer.innerHTML = '';
                    currentQr = new QRCode(publicQrCodeContainer, {
                        text: verifyUrl,
                        width: 90,
                        height: 90,
                        colorDark: '#080D25',
                        colorLight: '#FFFFFF',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }

                validCard.style.display = 'block';
                smoothScrollTo(validCard);

                // Update browser URL without reloading
                if (window.history.pushState) {
                    const newUrl = window.location.pathname + '?id=' + encodeURIComponent(cert.certificate_id);
                    window.history.replaceState({ path: newUrl }, '', newUrl);
                }

            } else if (data.status === 'revoked') {
                // Status 2: Revoked
                if (revokedCertId) revokedCertId.textContent = (data.certificate && data.certificate.certificate_id) || id;
                if (revokedStudentName) revokedStudentName.textContent = (data.certificate && data.certificate.student_name) || 'Issued Recipient';

                revokedCard.style.display = 'block';
                smoothScrollTo(revokedCard);

            } else {
                // Status 3: Not Found / Invalid
                notFoundCard.style.display = 'block';
                smoothScrollTo(notFoundCard);
            }

        } catch (error) {
            console.error('Verification error:', error);
            // Default to not found with notification
            notFoundCard.style.display = 'block';
            smoothScrollTo(notFoundCard);
        } finally {
            setLoading(false);
        }
    }

    // Form Submit Event
    if (verifyForm) {
        verifyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            performVerification();
        });
    }

    // Reset Verification Handlers
    resetButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            hideAllResults();
            if (certInput) {
                certInput.value = '';
                updateClearButton();
                certInput.focus();
            }
            const heroSection = document.getElementById('verifySection');
            if (heroSection) smoothScrollTo(heroSection);
        });
    });

    // Copy Verification URL
    if (btnCopyShareUrl && shareUrlInput) {
        btnCopyShareUrl.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(shareUrlInput.value);
                const originalText = copyShareBtnText.textContent;
                copyShareBtnText.textContent = 'Copied!';
                btnCopyShareUrl.classList.add('btn-primary');
                btnCopyShareUrl.classList.remove('btn-secondary');

                setTimeout(() => {
                    copyShareBtnText.textContent = originalText;
                    btnCopyShareUrl.classList.remove('btn-primary');
                    btnCopyShareUrl.classList.add('btn-secondary');
                }, 2000);
            } catch (err) {
                shareUrlInput.select();
                document.execCommand('copy');
                alert('Verification link copied to clipboard!');
            }
        });
    }

    // Print Result
    if (btnPrintResult) {
        btnPrintResult.addEventListener('click', () => {
            window.print();
        });
    }

    // Check pre-filled query param (?id=...) on page load
    const urlParams = new URLSearchParams(window.location.search);
    const queryId = urlParams.get('id');
    if (queryId && queryId.trim()) {
        if (certInput) certInput.value = queryId.trim().toUpperCase();
        updateClearButton();
        // Automatically perform verification on load
        performVerification(queryId.trim());
    } else {
        updateClearButton();
    }
});

