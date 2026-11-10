/**
 * Webkit24 Admin Dashboard Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // CSRF Token from meta
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // Stats elements
    const statTotal = document.getElementById('statTotal');
    const statActive = document.getElementById('statActive');
    const statRevoked = document.getElementById('statRevoked');
    const statTypes = document.getElementById('statTypes');

    // Table & Filter elements
    const tableBody = document.getElementById('tableBody');
    const tableSearch = document.getElementById('tableSearch');
    const btnClearSearch = document.getElementById('btnClearSearch');
    const filterType = document.getElementById('filterType');
    const filterStatus = document.getElementById('filterStatus');
    const btnRefresh = document.getElementById('btnRefresh');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    // Modals
    const certModal = document.getElementById('certModal');
    const modalTitle = document.getElementById('modalTitle');
    const certForm = document.getElementById('certForm');
    const editCertId = document.getElementById('editCertId');
    const modalAlert = document.getElementById('modalAlert');
    const btnOpenAddModal = document.getElementById('btnOpenAddModal');
    const btnCloseCertModal = document.getElementById('btnCloseCertModal');
    const btnCancelCertModal = document.getElementById('btnCancelCertModal');
    const btnAutoGenerateId = document.getElementById('btnAutoGenerateId');
    const saveBtnText = document.getElementById('saveBtnText');
    const saveSpinner = document.getElementById('saveSpinner');

    // Form inputs
    const studentNameInput = document.getElementById('studentName');
    const certTypeInput = document.getElementById('certType');
    const certStatusInput = document.getElementById('certStatus');
    const certIdInput = document.getElementById('certIdInput');
    const positionInput = document.getElementById('position');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const issueDateInput = document.getElementById('issueDate');

    // View Modal
    const viewModal = document.getElementById('viewModal');
    const btnCloseViewModal = document.getElementById('btnCloseViewModal');
    const btnCloseViewModalBtn = document.getElementById('btnCloseViewModalBtn');
    const modalQrCode = document.getElementById('modalQrCode');
    const btnCopyVerifyUrl = document.getElementById('btnCopyVerifyUrl');
    const copyUrlText = document.getElementById('copyUrlText');
    const btnOpenPublicVerify = document.getElementById('btnOpenPublicVerify');

    // Delete Modal
    const deleteModal = document.getElementById('deleteModal');
    const btnCloseDeleteModal = document.getElementById('btnCloseDeleteModal');
    const btnCancelDelete = document.getElementById('btnCancelDelete');
    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    const delCertId = document.getElementById('delCertId');
    const delStudentName = document.getElementById('delStudentName');
    const delSpinner = document.getElementById('delSpinner');

    // Toast
    const toast = document.getElementById('toast');

    // State
    let currentPage = 1;
    let searchTimeout = null;
    let certToDeleteId = null;
    let currentCertificates = [];
    let modalQrInstance = null;
    let currentViewingUrl = '';

    // ==================== TOAST NOTIFICATIONS ====================
    function showToast(message, type = 'success') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = `toast-notification toast-${type}`;
        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
        }, 3500);
    }

    // ==================== STATS ====================
    async function loadStats() {
        try {
            const res = await fetch('api/stats.php');
            const data = await res.json();
            if (data.success && data.stats) {
                if (statTotal) statTotal.textContent = data.stats.total;
                if (statActive) statActive.textContent = data.stats.active;
                if (statRevoked) statRevoked.textContent = data.stats.revoked;
                if (statTypes) statTypes.textContent = `${data.stats.types_count} Active Types`;
            }
        } catch (e) {
            console.error('Failed to load stats:', e);
        }
    }

    // ==================== TABLE DATA ====================
    async function loadCertificates(page = 1) {
        currentPage = page;
        const search = (tableSearch ? tableSearch.value : '').trim();
        const type = filterType ? filterType.value : '';
        const status = filterStatus ? filterStatus.value : '';

        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="loading-spinner"></div>
                        <p class="text-muted mt-2">Loading certificates...</p>
                    </td>
                </tr>
            `;
        }

        try {
            const url = `api/list.php?page=${page}&limit=12&search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}&status=${encodeURIComponent(status)}`;
            const res = await fetch(url);
            const data = await res.json();

            if (!data.success) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-red py-4">Failed to load certificates: ${escapeHtml(data.message)}</td></tr>`;
                return;
            }

            currentCertificates = data.certificates || [];
            renderTable(currentCertificates, data.pagination);

        } catch (e) {
            console.error('Load certificates error:', e);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-red py-4">Network or server error while loading certificates.</td></tr>`;
        }
    }

    function renderTable(certificates, pagination) {
        if (!certificates || certificates.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">No certificates found matching your criteria.</div>
                    </td>
                </tr>
            `;
            if (paginationInfo) paginationInfo.textContent = 'Showing 0 certificates';
            if (paginationControls) paginationControls.innerHTML = '';
            return;
        }

        let html = '';
        certificates.forEach(c => {
            const isActive = c.status === 'Active';
            const statusBadge = isActive 
                ? `<span class="badge-status badge-status-active">Active</span>`
                : `<span class="badge-status badge-status-revoked">Revoked</span>`;

            html += `
                <tr data-id="${c.id}">
                    <td>
                        <strong class="font-mono text-navy">${escapeHtml(c.certificate_id)}</strong>
                    </td>
                    <td>
                        <span class="font-bold">${escapeHtml(c.student_name)}</span>
                    </td>
                    <td>
                        <span class="badge-type">${escapeHtml(c.certificate_type)}</span>
                    </td>
                    <td>
                        <span>${escapeHtml(c.position)}</span>
                    </td>
                    <td>
                        <span class="text-muted">${escapeHtml(c.issue_date_formatted || c.issue_date)}</span>
                    </td>
                    <td>
                        ${statusBadge}
                    </td>
                    <td class="text-right">
                        <div class="table-actions">
                            <a href="generator.php?id=${c.id}" class="action-btn" title="Download Offer Letter & Certificate PDF" style="color: var(--primary);">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                </svg>
                            </a>

                            <button type="button" class="action-btn btn-view-cert" data-id="${c.id}" title="View Details & QR">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <button type="button" class="action-btn action-btn-toggle btn-toggle-status" data-id="${c.id}" data-status="${c.status}" title="${isActive ? 'Revoke Certificate' : 'Activate Certificate'}">
                                ${isActive ? `
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="text-red">
                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.366zm1.414-1.414L6.525 5.11a6 6 0 018.366 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                    </svg>
                                ` : `
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="text-green">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                `}
                            </button>

                            <button type="button" class="action-btn btn-edit-cert" data-id="${c.id}" title="Edit Certificate">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>

                            <button type="button" class="action-btn action-btn-delete btn-delete-cert" data-id="${c.id}" data-name="${escapeHtml(c.student_name)}" data-certid="${escapeHtml(c.certificate_id)}" title="Delete Certificate">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;

        // Pagination info
        const total = pagination.total || 0;
        const page = pagination.page || 1;
        const totalPages = pagination.total_pages || 1;
        const start = total === 0 ? 0 : (page - 1) * pagination.limit + 1;
        const end = Math.min(page * pagination.limit, total);

        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${start}–${end} of ${total} certificates`;
        }

        renderPaginationControls(page, totalPages);
        attachTableActionListeners();
    }

    function renderPaginationControls(page, totalPages) {
        if (!paginationControls) return;
        if (totalPages <= 1) {
            paginationControls.innerHTML = '';
            return;
        }

        let html = '';
        html += `<button type="button" class="page-btn" ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}">Prev</button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                html += `<button type="button" class="page-btn ${i === page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === page - 2 || i === page + 2) {
                html += `<span class="px-1 text-muted">...</span>`;
            }
        }

        html += `<button type="button" class="page-btn" ${page >= totalPages ? 'disabled' : ''} data-page="${page + 1}">Next</button>`;
        paginationControls.innerHTML = html;

        paginationControls.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetPage = parseInt(btn.getAttribute('data-page'), 10);
                if (targetPage && targetPage !== currentPage) {
                    loadCertificates(targetPage);
                }
            });
        });
    }

    // ==================== TABLE ROW ACTIONS ====================
    function attachTableActionListeners() {
        // View Details
        document.querySelectorAll('.btn-view-cert').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const cert = currentCertificates.find(c => c.id == id);
                if (cert) openViewModal(cert);
            });
        });

        // Edit Certificate
        document.querySelectorAll('.btn-edit-cert').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const cert = currentCertificates.find(c => c.id == id);
                if (cert) openEditModal(cert);
            });
        });

        // Toggle Status (Activate / Revoke)
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                await toggleCertStatus(id);
            });
        });

        // Delete Certificate
        document.querySelectorAll('.btn-delete-cert').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const name = btn.getAttribute('data-name');
                const certId = btn.getAttribute('data-certid');
                openDeleteModal(id, certId, name);
            });
        });
    }

    // ==================== AUTO-GENERATE CERTIFICATE ID ====================
    async function fetchNextSequentialId(type, year = null) {
        try {
            const queryYear = year || (issueDateInput && issueDateInput.value ? new Date(issueDateInput.value).getFullYear() : new Date().getFullYear());
            const res = await fetch(`api/next-id.php?type=${encodeURIComponent(type)}&year=${queryYear}`);
            const data = await res.json();
            if (data.success && data.next_id) {
                return data.next_id;
            }
        } catch (e) {
            console.error('Error fetching sequential ID:', e);
        }
        return '';
    }

    if (btnAutoGenerateId) {
        btnAutoGenerateId.addEventListener('click', async () => {
            const type = certTypeInput ? certTypeInput.value : 'Internship';
            const nextId = await fetchNextSequentialId(type);
            if (nextId && certIdInput) {
                certIdInput.value = nextId;
            }
        });
    }

    // When Certificate Type changes in modal, auto-update ID placeholder and value if adding new
    if (certTypeInput) {
        certTypeInput.addEventListener('change', async () => {
            if (!editCertId.value) { // only in add mode
                const nextId = await fetchNextSequentialId(certTypeInput.value);
                if (nextId && certIdInput) {
                    certIdInput.value = nextId;
                }
            }
        });
    }

    // ==================== ADD / EDIT MODAL ====================
    function openAddModal() {
        modalTitle.textContent = 'Issue New Certificate';
        saveBtnText.textContent = 'Save Certificate';
        editCertId.value = '';
        certForm.reset();
        modalAlert.style.display = 'none';

        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        if (issueDateInput) issueDateInput.value = today;

        // Auto-fetch sequential ID
        fetchNextSequentialId('Internship').then(nextId => {
            if (certIdInput && !editCertId.value) {
                certIdInput.value = nextId;
            }
        });

        certModal.classList.add('active');
        if (studentNameInput) studentNameInput.focus();
    }

    function openEditModal(cert) {
        modalTitle.textContent = 'Edit Certificate';
        saveBtnText.textContent = 'Update Certificate';
        editCertId.value = cert.id;
        modalAlert.style.display = 'none';

        if (studentNameInput) studentNameInput.value = cert.student_name;
        if (certTypeInput) certTypeInput.value = cert.certificate_type;
        if (certStatusInput) certStatusInput.value = cert.status;
        if (certIdInput) certIdInput.value = cert.certificate_id;
        if (positionInput) positionInput.value = cert.position;
        if (startDateInput) startDateInput.value = cert.start_date;
        if (endDateInput) endDateInput.value = cert.end_date;
        if (issueDateInput) issueDateInput.value = cert.issue_date;

        certModal.classList.add('active');
    }

    function closeCertModal() {
        certModal.classList.remove('active');
    }

    if (btnOpenAddModal) btnOpenAddModal.addEventListener('click', openAddModal);
    if (btnCloseCertModal) btnCloseCertModal.addEventListener('click', closeCertModal);
    if (btnCancelCertModal) btnCancelCertModal.addEventListener('click', closeCertModal);

    // Save Certificate Form Submit
    if (certForm) {
        certForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAlert.style.display = 'none';

            const isEdit = Boolean(editCertId.value);
            const endpoint = isEdit ? 'api/update.php' : 'api/create.php';

            const payload = {
                id: editCertId.value,
                student_name: studentNameInput.value.trim(),
                certificate_type: certTypeInput.value,
                status: certStatusInput.value,
                certificate_id: certIdInput.value.trim().toUpperCase(),
                position: positionInput.value.trim(),
                start_date: startDateInput.value,
                end_date: endDateInput.value,
                issue_date: issueDateInput.value,
                csrf_token: csrfToken
            };

            // Loading state
            if (saveSpinner) saveSpinner.style.display = 'block';
            if (saveBtnText) saveBtnText.textContent = isEdit ? 'Updating...' : 'Saving...';

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    showToast(isEdit ? 'Certificate updated successfully.' : 'Certificate issued successfully.');
                    closeCertModal();
                    loadStats();
                    loadCertificates(currentPage);
                } else {
                    modalAlert.textContent = data.message || 'An error occurred while saving the certificate.';
                    modalAlert.style.display = 'block';
                }
            } catch (err) {
                console.error('Save error:', err);
                modalAlert.textContent = 'Server communication error. Please try again.';
                modalAlert.style.display = 'block';
            } finally {
                if (saveSpinner) saveSpinner.style.display = 'none';
                if (saveBtnText) saveBtnText.textContent = isEdit ? 'Update Certificate' : 'Save Certificate';
            }
        });
    }

    // ==================== VIEW MODAL & QR CODE ====================
    function openViewModal(cert) {
        document.getElementById('vStudentName').textContent = cert.student_name;
        document.getElementById('vCertId').textContent = cert.certificate_id;
        document.getElementById('vCertType').textContent = cert.certificate_type + ' Certificate';
        document.getElementById('vPosition').textContent = cert.position;
        document.getElementById('vStartDate').textContent = cert.start_date_formatted || cert.start_date;
        document.getElementById('vEndDate').textContent = cert.end_date_formatted || cert.end_date;
        document.getElementById('vIssueDate').textContent = cert.issue_date_formatted || cert.issue_date;

        const statusBadge = document.getElementById('vStatusBadge');
        if (cert.status === 'Active') {
            statusBadge.className = 'badge-status badge-status-active';
            statusBadge.textContent = 'Active (Valid)';
        } else {
            statusBadge.className = 'badge-status badge-status-revoked';
            statusBadge.textContent = 'Revoked';
        }

        currentViewingUrl = cert.verification_url || `${window.location.origin}/?id=${encodeURIComponent(cert.certificate_id)}`;
        if (btnOpenPublicVerify) btnOpenPublicVerify.href = currentViewingUrl;

        const btnOpenPdfStudio = document.getElementById('btnOpenPdfStudio');
        if (btnOpenPdfStudio) btnOpenPdfStudio.href = `generator.php?id=${cert.id}`;

        // Render QR with high clarity and Level H error correction
        if (modalQrCode && typeof QRCode !== 'undefined') {
            modalQrCode.innerHTML = '';
            modalQrInstance = new QRCode(modalQrCode, {
                text: currentViewingUrl,
                width: 220,
                height: 220,
                colorDark: '#000000',
                colorLight: '#FFFFFF',
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        viewModal.classList.add('active');
    }

    function closeViewModal() {
        viewModal.classList.remove('active');
    }

    if (btnCloseViewModal) btnCloseViewModal.addEventListener('click', closeViewModal);
    if (btnCloseViewModalBtn) btnCloseViewModalBtn.addEventListener('click', closeViewModal);

    if (btnCopyVerifyUrl) {
        btnCopyVerifyUrl.addEventListener('click', async () => {
            if (!currentViewingUrl) return;
            try {
                await navigator.clipboard.writeText(currentViewingUrl);
                const original = copyUrlText.textContent;
                copyUrlText.textContent = 'Copied Link!';
                setTimeout(() => {
                    copyUrlText.textContent = original;
                }, 2000);
            } catch (e) {
                alert('Copied link: ' + currentViewingUrl);
            }
        });
    }

    // ==================== TOGGLE STATUS (ACTIVE <-> REVOKED) ====================
    async function toggleCertStatus(id) {
        try {
            const res = await fetch('api/toggle-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id, csrf_token: csrfToken })
            });

            const data = await res.json();
            if (data.success) {
                showToast(`Certificate status changed to ${data.new_status}.`);
                loadStats();
                loadCertificates(currentPage);
            } else {
                showToast(data.message || 'Failed to toggle status', 'error');
            }
        } catch (e) {
            console.error('Toggle status error:', e);
            showToast('Error changing certificate status.', 'error');
        }
    }

    // ==================== DELETE MODAL ====================
    function openDeleteModal(id, certId, studentName) {
        certToDeleteId = id;
        if (delCertId) delCertId.textContent = certId;
        if (delStudentName) delStudentName.textContent = studentName;
        deleteModal.classList.add('active');
    }

    function closeDeleteModal() {
        deleteModal.classList.remove('active');
        certToDeleteId = null;
    }

    if (btnCloseDeleteModal) btnCloseDeleteModal.addEventListener('click', closeDeleteModal);
    if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);

    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', async () => {
            if (!certToDeleteId) return;

            if (delSpinner) delSpinner.style.display = 'block';
            btnConfirmDelete.disabled = true;

            try {
                const res = await fetch('api/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: certToDeleteId, csrf_token: csrfToken })
                });

                const data = await res.json();
                if (data.success) {
                    showToast('Certificate deleted permanently.');
                    closeDeleteModal();
                    loadStats();
                    loadCertificates(currentPage);
                } else {
                    alert(data.message || 'Failed to delete certificate.');
                }
            } catch (e) {
                console.error('Delete error:', e);
                alert('An error occurred while deleting.');
            } finally {
                if (delSpinner) delSpinner.style.display = 'none';
                btnConfirmDelete.disabled = false;
            }
        });
    }

    // ==================== SEARCH & FILTERS ====================
    if (tableSearch) {
        tableSearch.addEventListener('input', () => {
            const hasVal = Boolean(tableSearch.value.trim());
            if (btnClearSearch) btnClearSearch.style.display = hasVal ? 'block' : 'none';

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadCertificates(1);
            }, 300);
        });
    }

    if (btnClearSearch) {
        btnClearSearch.addEventListener('click', () => {
            tableSearch.value = '';
            btnClearSearch.style.display = 'none';
            loadCertificates(1);
        });
    }

    if (filterType) {
        filterType.addEventListener('change', () => loadCertificates(1));
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', () => loadCertificates(1));
    }

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            loadStats();
            loadCertificates(currentPage);
        });
    }

    // Close modals on clicking backdrop
    [certModal, viewModal, deleteModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
    });

    // Escape key closes active modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            [certModal, viewModal, deleteModal].forEach(modal => {
                if (modal && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            });
        }
    });

    // Helper: HTML escaper
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initial Load
    loadStats();
    loadCertificates(1);
});

