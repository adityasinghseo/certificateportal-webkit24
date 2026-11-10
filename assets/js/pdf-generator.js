/**
 * Webkit24 PDF Document Generator Studio
 * Live preview & High-Fidelity 300 DPI PDF exporter for:
 * 
 * a- Internship Documents:
 *   1. Internship Offer Letter (Portrait)
 *   2. Internship Joining Letter (Portrait)
 *   3. Internship Completion Letter (Portrait)
 *   4. Internship Certificate (Landscape)
 *   5. Internship Relieving Letter (Portrait)
 * 
 * b- Job / Employee Documents:
 *   1. Job Offer Letter (Portrait)
 *   2. Job Appointment Letter (Portrait)
 *   3. Job Joining Letter (Portrait)
 *   4. Job Relieving Letter (Portrait)
 *   5. Job Experience Certificate (Landscape)
 */

window.Webkit24PDF = (function () {
    // Current Active Category ('internship' | 'job')
    let currentCategory = 'internship';

    // Current Document Key
    let currentDocType = 'internship_certificate';

    // Registry of All 10 Supported Documents
    const DOC_REGISTRY = {
        // a- Internship Documents
        internship_offer: {
            category: 'internship',
            name: 'Internship Offer Letter',
            shortLabel: '1. Offer Letter',
            icon: '📄',
            orientation: 'portrait',
            fileSuffix: 'Internship_Offer_Letter'
        },
        internship_joining: {
            category: 'internship',
            name: 'Internship Joining Letter',
            shortLabel: '2. Joining Letter',
            icon: '📋',
            orientation: 'portrait',
            fileSuffix: 'Internship_Joining_Letter'
        },
        internship_completion: {
            category: 'internship',
            name: 'Internship Completion Letter',
            shortLabel: '3. Completion Letter',
            icon: '📜',
            orientation: 'portrait',
            fileSuffix: 'Internship_Completion_Letter'
        },
        internship_certificate: {
            category: 'internship',
            name: 'Internship Certificate',
            shortLabel: '4. Certificate',
            icon: '🎓',
            orientation: 'landscape',
            fileSuffix: 'Internship_Certificate'
        },
        internship_relieving: {
            category: 'internship',
            name: 'Internship Relieving Letter',
            shortLabel: '5. Relieving Letter',
            icon: '🕊️',
            orientation: 'portrait',
            fileSuffix: 'Internship_Relieving_Letter'
        },

        // b- Job / Employee Documents
        job_offer: {
            category: 'job',
            name: 'Job Offer Letter',
            shortLabel: '1. Offer Letter',
            icon: '📄',
            orientation: 'portrait',
            fileSuffix: 'Job_Offer_Letter'
        },
        job_appointment: {
            category: 'job',
            name: 'Job Appointment Letter',
            shortLabel: '2. Appointment Letter',
            icon: '📑',
            orientation: 'portrait',
            fileSuffix: 'Job_Appointment_Letter'
        },
        job_joining: {
            category: 'job',
            name: 'Job Joining Letter',
            shortLabel: '3. Joining Letter',
            icon: '📋',
            orientation: 'portrait',
            fileSuffix: 'Job_Joining_Letter'
        },
        job_relieving: {
            category: 'job',
            name: 'Job Relieving Letter',
            shortLabel: '4. Relieving Letter',
            icon: '🕊️',
            orientation: 'portrait',
            fileSuffix: 'Job_Relieving_Letter'
        },
        job_experience: {
            category: 'job',
            name: 'Job Experience Certificate',
            shortLabel: '5. Experience Certificate',
            icon: '🏆',
            orientation: 'landscape',
            fileSuffix: 'Job_Experience_Certificate'
        }
    };

    // Default template state
    const defaultData = {
        student_name: 'Hariom Pandey',
        certificate_id: 'WEBKIT24/INT/2026/0224',
        certificate_type: 'Internship',
        position: 'Frontend Developer Intern',
        domain_name: 'Frontend Web Development',
        start_date: 'June 15, 2026',
        end_date: 'July 30, 2026',
        issue_date: '10-Aug-2026',
        issue_date_cert: '10 AUG 2026',
        job_type: 'Part-time (Remote)',
        salary_ctc: '₹ 6,50,000 Per Annum',
        reporting_manager: 'Aditya Singh (Lead Engineer)',
        work_location: 'Lucknow HQ / Remote',
        probation_period: '3 Months',
        notice_period: '30 Days',
        pronoun: 'he', // 'he' | 'she' | 'they'
        sig1_name: 'ADITYA SINGH',
        sig1_title: 'CEO',
        sig2_name: 'SUDHANSHU PANDEY',
        sig2_title: 'FOUNDER',
        responsibilities: [
            'Gain hands-on experience in frontend web development.',
            'Work with HTML, CSS, JavaScript, and modern frontend technologies.',
            'Develop responsive and user-friendly web interfaces.',
            'Collaborate with experienced developers on real-world projects.',
            'Learn to integrate frontend designs with backend APIs.',
            'Identify and fix UI bugs to improve website performance and user experience.',
            'Participate in team meetings, code reviews, and brainstorming sessions.'
        ]
    };

    let state = { ...defaultData };

    /**
     * Pronoun helper for grammatical agreement
     */
    function getPronouns(p) {
        p = (p || 'he').toLowerCase();
        if (p === 'she') {
            return { subj: 'she', obj: 'her', poss: 'her', Subj: 'She', Obj: 'Her', Poss: 'Her' };
        } else if (p === 'they') {
            return { subj: 'they', obj: 'them', poss: 'their', Subj: 'They', Obj: 'Them', Poss: 'Their' };
        }
        return { subj: 'he', obj: 'him', poss: 'his', Subj: 'He', Obj: 'Him', Poss: 'His' };
    }

    /**
     * Date Formatting Helpers
     */
    function formatLongDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
    }

    function formatShortDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${d.getDate()}-${months[d.getMonth()]}-${d.getFullYear()}`;
    }

    function formatCertDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr.toUpperCase();
        const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    /**
     * Read and update state from form inputs
     */
    function updateStateFromInputs() {
        const nameEl = document.getElementById('genStudentName');
        const certIdEl = document.getElementById('genCertId');
        const roleEl = document.getElementById('genRole');
        const domainEl = document.getElementById('genDomain');
        const startEl = document.getElementById('genStartDate');
        const endEl = document.getElementById('genEndDate');
        const issueEl = document.getElementById('genIssueDate');
        const jobTypeEl = document.getElementById('genJobType');
        const salaryEl = document.getElementById('genSalaryCtc');
        const managerEl = document.getElementById('genReportingManager');
        const locationEl = document.getElementById('genWorkLocation');
        const probationEl = document.getElementById('genProbation');
        const noticeEl = document.getElementById('genNoticePeriod');
        const pronounEl = document.getElementById('genPronoun');
        const bulletsEl = document.getElementById('genBullets');

        if (nameEl && nameEl.value.trim()) state.student_name = nameEl.value.trim();
        if (certIdEl && certIdEl.value.trim()) state.certificate_id = certIdEl.value.trim().toUpperCase();
        if (roleEl && roleEl.value.trim()) state.position = roleEl.value.trim();
        if (domainEl && domainEl.value.trim()) state.domain_name = domainEl.value.trim();
        
        if (startEl && startEl.value) state.start_date = formatLongDate(startEl.value);
        if (endEl && endEl.value) state.end_date = formatLongDate(endEl.value);
        if (issueEl && issueEl.value) {
            state.issue_date = formatShortDate(issueEl.value);
            state.issue_date_cert = formatCertDate(issueEl.value);
        }

        if (jobTypeEl) state.job_type = jobTypeEl.value;
        if (salaryEl && salaryEl.value.trim()) state.salary_ctc = salaryEl.value.trim();
        if (managerEl && managerEl.value.trim()) state.reporting_manager = managerEl.value.trim();
        if (locationEl && locationEl.value.trim()) state.work_location = locationEl.value.trim();
        if (probationEl && probationEl.value.trim()) state.probation_period = probationEl.value.trim();
        if (noticeEl && noticeEl.value.trim()) state.notice_period = noticeEl.value.trim();
        if (pronounEl) state.pronoun = pronounEl.value;

        if (bulletsEl && bulletsEl.value.trim()) {
            state.responsibilities = bulletsEl.value
                .split('\n')
                .map(s => s.trim().replace(/^[-*•]\s*/, ''))
                .filter(s => s.length > 0);
        }

        renderPreviews();
    }

    /**
     * Common Letter Components
     */
    function buildLetterHeader() {
        return `
        <div class="ol-header">
            <div class="ol-logo-wrap">
                <img src="../assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="ol-logo-img">
            </div>
            <div class="ol-address">
                Address: 94, Sector 17,<br>
                Chandanapur, Indira Nagar,<br>
                Lucknow, Uttar Pradesh 226016
            </div>
        </div>
        `;
    }

    function buildLetterFooter() {
        return `
        <div class="ol-footer-strip">
            <div class="ol-footer-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span>Lucknow, Uttar Pradesh</span>
            </div>

            <div class="ol-footer-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                <span>www.webkit24.com</span>
            </div>

            <div class="ol-footer-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <span>info@webkit24.com</span>
            </div>
        </div>
        `;
    }

    function buildDualSignatures(d) {
        return `
        <div class="doc-letter-sigs-row">
            <div class="doc-letter-sig-col">
                <img src="../assets/images/signature-aditya.png" alt="Aditya Singh Signature">
                <div class="doc-letter-sig-line"></div>
                <div class="doc-letter-sig-name">${escapeHtml(d.sig1_name)}</div>
                <div class="doc-letter-sig-title">${escapeHtml(d.sig1_title)}, Webkit24</div>
            </div>

            <div class="doc-letter-sig-col">
                <img src="../assets/images/signature-sudhanshu.png" alt="Sudhanshu Pandey Signature">
                <div class="doc-letter-sig-line"></div>
                <div class="doc-letter-sig-name">${escapeHtml(d.sig2_name)}</div>
                <div class="doc-letter-sig-title">${escapeHtml(d.sig2_title)}, Webkit24</div>
            </div>
        </div>
        `;
    }

    // =========================================================================
    // a- INTERNSHIP DOCUMENT BUILDERS
    // =========================================================================

    /**
     * 1. Internship Offer Letter (A4 Portrait)
     */
    function buildInternshipOfferHtml(d) {
        const bulletItems = d.responsibilities.map(item => `<li>${escapeHtml(item)}</li>`).join('');

        return `
        <div class="offer-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="ol-title-wrap">
                <h1 class="ol-title">INTERNSHIP OFFER LETTER</h1>
            </div>

            <div class="ol-meta-row">
                <span class="ol-recipient">Dear ${escapeHtml(d.student_name)},</span>
                <span class="ol-date">Date: ${escapeHtml(d.issue_date)}</span>
            </div>

            <div class="ol-body">
                <p>
                    We are pleased to offer you an internship position as a <strong>${escapeHtml(d.position)}</strong> at Webkit24, a leading web development company specializing in cutting-edge web solutions.
                </p>

                <p>
                    We believe that your skills, enthusiasm, and passion for ${escapeHtml(d.domain_name.toLowerCase())} align perfectly with our company's mission and values. We are excited to welcome you to our team.
                </p>

                <div class="ol-param-list">
                    <div class="ol-param-item"><strong>Position:</strong> ${escapeHtml(d.position)}</div>
                    <div class="ol-param-item"><strong>Start Date:</strong> ${escapeHtml(d.start_date)}</div>
                    <div class="ol-param-item"><strong>End Date:</strong> ${escapeHtml(d.end_date)}</div>
                    <div class="ol-param-item"><strong>Job Type:</strong> ${escapeHtml(d.job_type)}</div>
                </div>

                <div class="ol-section-title">Internship Details</div>
                <p>During your internship at Webkit24, you will have the opportunity to:</p>
                <ul class="ol-bullets">
                    ${bulletItems}
                </ul>

                <p>
                    We are excited to have you join our team and look forward to a productive and enriching internship experience.
                </p>

                <p>
                    Congratulations on your selection! We are confident that your time at Webkit24 will be both professionally and personally rewarding.
                </p>

                <div class="ol-signoff">
                    Best Regards,<br>
                    <strong>Webkit24 Team</strong>
                </div>
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 2. Internship Joining Letter (A4 Portrait)
     */
    function buildInternshipJoiningHtml(d) {
        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Ref ID: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 10px 0 16px;">
                <h1 class="ol-title" style="font-size: 15.5pt;">INTERNSHIP JOINING CONFIRMATION</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 12px;">
                <span class="ol-recipient">To: ${escapeHtml(d.student_name)}</span>
            </div>

            <div class="ol-body" style="gap: 12px;">
                <p>
                    We are pleased to formally accept and confirm your joining for the internship program at <strong>Webkit24</strong> as a <strong>${escapeHtml(d.position)}</strong> in the <strong>${escapeHtml(d.domain_name)}</strong> division, effective from <strong>${escapeHtml(d.start_date)}</strong>.
                </p>

                <table class="doc-table-params">
                    <tr>
                        <td class="param-label">Intern Full Name:</td>
                        <td class="param-val">${escapeHtml(d.student_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Designated Role:</td>
                        <td class="param-val"><strong>${escapeHtml(d.position)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Domain / Track:</td>
                        <td class="param-val">${escapeHtml(d.domain_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Internship Tenure:</td>
                        <td class="param-val">${escapeHtml(d.start_date)} to ${escapeHtml(d.end_date)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Reporting Mentor:</td>
                        <td class="param-val">${escapeHtml(d.reporting_manager)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Work Arrangement:</td>
                        <td class="param-val">${escapeHtml(d.job_type)} (${escapeHtml(d.work_location)})</td>
                    </tr>
                </table>

                <div class="ol-section-title" style="font-size: 11pt; margin-top: 4px;">Terms of Induction & Conduct</div>
                <p>
                    As an intern at Webkit24, you will undergo orientation and join our active development sprints. You are expected to strictly adhere to Webkit24's developer guidelines, Git repository standards, confidentiality directives, and daily stand-up reporting.
                </p>

                <p>
                    We welcome you warmly to Webkit24 and trust that your learning journey with us will set a solid foundation for your engineering career.
                </p>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 3. Internship Completion Letter (A4 Portrait)
     */
    function buildInternshipCompletionHtml(d) {
        const pr = getPronouns(d.pronoun);

        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Ref ID: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 10px 0 16px;">
                <h1 class="ol-title" style="font-size: 15.5pt;">INTERNSHIP COMPLETION LETTER</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 12px;">
                <span class="ol-recipient" style="letter-spacing: 0.05em; text-transform: uppercase; font-size: 11pt;">TO WHOMSOEVER IT MAY CONCERN</span>
            </div>

            <div class="ol-body" style="gap: 12px;">
                <p>
                    This is to certify that <strong>${escapeHtml(d.student_name)}</strong> has successfully completed an intensive internship program as a <strong>${escapeHtml(d.position)}</strong> at <strong>Webkit24</strong> from <strong>${escapeHtml(d.start_date)}</strong> to <strong>${escapeHtml(d.end_date)}</strong>.
                </p>

                <p>
                    During this tenure, ${pr.subj} actively contributed to client project modules and core development sprints in <strong>${escapeHtml(d.domain_name)}</strong>. ${pr.Subj} demonstrated a commendable grasp of modern web technologies, responsive layout structuring, API integration, and clean coding practices.
                </p>

                <div class="ol-section-title" style="font-size: 11pt; margin-top: 4px;">Appraisal & Performance Highlights</div>
                <ul class="ol-bullets">
                    <li>Demonstrated exceptional dedication, punctuality, and enthusiasm toward technical assignments.</li>
                    <li>Consistently met sprint deadlines while maintaining robust code quality and documentation.</li>
                    <li>Collaborated effectively with team leads and peers during code reviews and design discussions.</li>
                </ul>

                <p>
                    Throughout the internship, we found ${pr.obj} to be hardworking, disciplined, and inquisitive. We appreciate ${pr.poss} valuable contributions and wish ${pr.obj} the absolute best in all future academic and professional endeavors.
                </p>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 4. Internship Certificate (A4 Landscape with QR & Security Seal)
     */
    function buildInternshipCertificateHtml(d) {
        return buildCertificateBaseHtml(d, {
            subType: 'I N T E R N S H I P',
            mainTitle: 'C E R T I F I C A T E',
            statement: `has successfully completed the internship program in <strong>${escapeHtml(d.domain_name || d.position)}</strong> at Webkit24 from <strong>${escapeHtml(d.start_date)}</strong> to <strong>${escapeHtml(d.end_date)}</strong>. During the internship, ${escapeHtml(d.pronoun || 'he')} demonstrated dedication, enthusiasm, a willingness to learn, and commitment towards assigned tasks and projects.`
        });
    }

    /**
     * 5. Internship Relieving Letter (A4 Portrait)
     */
    function buildInternshipRelievingHtml(d) {
        const pr = getPronouns(d.pronoun);

        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Ref ID: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 10px 0 16px;">
                <h1 class="ol-title" style="font-size: 15.5pt;">INTERNSHIP RELIEVING LETTER</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 12px;">
                <span class="ol-recipient">To: ${escapeHtml(d.student_name)}</span>
            </div>

            <div class="ol-body" style="gap: 12px;">
                <p>
                    This letter serves as formal confirmation that <strong>${escapeHtml(d.student_name)}</strong> has been relieved of all duties and responsibilities as an intern (<strong>${escapeHtml(d.position)}</strong>) at Webkit24, effective from the close of business hours on <strong>${escapeHtml(d.end_date)}</strong>.
                </p>

                <table class="doc-table-params">
                    <tr>
                        <td class="param-label">Intern Full Name:</td>
                        <td class="param-val">${escapeHtml(d.student_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Position / Role:</td>
                        <td class="param-val"><strong>${escapeHtml(d.position)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Internship Tenure:</td>
                        <td class="param-val">${escapeHtml(d.start_date)} to ${escapeHtml(d.end_date)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Handover Status:</td>
                        <td class="param-val">Completed & Verified</td>
                    </tr>
                    <tr>
                        <td class="param-label">Clearance Status:</td>
                        <td class="param-val"><strong style="color: #10B981;">No Dues Pending</strong></td>
                    </tr>
                </table>

                <p>
                    All project repositories, technical documentation, assigned access credentials, and company intellectual assets under ${pr.poss} custody have been duly handed over to our engineering department.
                </p>

                <p>
                    We confirm that there are no pending dues or liabilities against ${pr.obj}. We thank you for your contributions during your internship and wish you every success in your academic and professional future.
                </p>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    // =========================================================================
    // b- JOB / EMPLOYEE DOCUMENT BUILDERS
    // =========================================================================

    /**
     * 1. Job Offer Letter (A4 Portrait)
     */
    function buildJobOfferHtml(d) {
        const bulletItems = d.responsibilities.slice(0, 5).map(item => `<li>${escapeHtml(item)}</li>`).join('');

        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Offer Ref: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 8px 0 14px;">
                <h1 class="ol-title" style="font-size: 16pt;">EMPLOYMENT OFFER LETTER</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 10px;">
                <span class="ol-recipient">Dear ${escapeHtml(d.student_name)},</span>
            </div>

            <div class="ol-body" style="gap: 10px;">
                <p>
                    On behalf of <strong>Webkit24</strong>, we are thrilled to offer you the position of <strong>${escapeHtml(d.position)}</strong>. We were thoroughly impressed by your technical proficiency, problem-solving mindset, and alignment with our mission to innovate, transform, and thrive.
                </p>

                <table class="doc-table-params">
                    <tr>
                        <td class="param-label">Designation / Role:</td>
                        <td class="param-val"><strong>${escapeHtml(d.position)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Department / Team:</td>
                        <td class="param-val">${escapeHtml(d.domain_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Total Compensation (CTC):</td>
                        <td class="param-val"><strong style="color: #FF4938;">${escapeHtml(d.salary_ctc)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Joining Date:</td>
                        <td class="param-val">${escapeHtml(d.start_date)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Work Arrangement:</td>
                        <td class="param-val">${escapeHtml(d.job_type)} • ${escapeHtml(d.work_location)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Probation Period:</td>
                        <td class="param-val">${escapeHtml(d.probation_period)}</td>
                    </tr>
                </table>

                <div class="ol-section-title" style="font-size: 10.5pt; margin-top: 2px;">Key Responsibilities</div>
                <ul class="ol-bullets" style="font-size: 9.5pt;">
                    ${bulletItems}
                </ul>

                <p>
                    Please sign and return a duplicate copy of this letter within 5 working days as an indication of your formal acceptance. We eagerly await welcoming you to the Webkit24 team.
                </p>

                <div class="doc-letter-acceptance">
                    <strong>Candidate Acceptance:</strong> I hereby accept the offer of employment on the terms and conditions outlined above.<br>
                    Signature: __________________________ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date: ____________________
                </div>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 2. Job Appointment Letter (A4 Portrait)
     */
    function buildJobAppointmentHtml(d) {
        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Appointment Ref: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 8px 0 14px;">
                <h1 class="ol-title" style="font-size: 16pt;">LETTER OF APPOINTMENT</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 10px;">
                <span class="ol-recipient">To: ${escapeHtml(d.student_name)}</span>
            </div>

            <div class="ol-body" style="gap: 10px;">
                <p>
                    Further to your acceptance of our employment offer, the management of <strong>Webkit24</strong> is pleased to formally appoint you as a full-time <strong>${escapeHtml(d.position)}</strong> with effect from <strong>${escapeHtml(d.start_date)}</strong>.
                </p>

                <div class="ol-section-title" style="font-size: 10.5pt; margin-top: 2px;">Terms & Conditions of Employment</div>
                <ol style="padding-left: 20px; font-size: 9.6pt; line-height: 1.45; display: flex; flex-direction: column; gap: 4px; margin: 4px 0;">
                    <li><strong>Remuneration:</strong> Your annual Cost to Company (CTC) is <strong>${escapeHtml(d.salary_ctc)}</strong>, payable on a monthly basis subject to statutory tax deductions.</li>
                    <li><strong>Reporting & Duties:</strong> You will report to <strong>${escapeHtml(d.reporting_manager)}</strong> and perform duties pertaining to software development and company objectives.</li>
                    <li><strong>Probation & Confirmation:</strong> You shall be on probation for a period of <strong>${escapeHtml(d.probation_period)}</strong>. Upon satisfactory appraisal, your employment will be confirmed in writing.</li>
                    <li><strong>Notice Period:</strong> Either party may terminate employment by providing <strong>${escapeHtml(d.notice_period)}</strong> written notice or compensation in lieu thereof.</li>
                    <li><strong>Confidentiality & IP:</strong> All code, intellectual property, architectures, and client data created or accessed by you remain the sole and exclusive property of Webkit24.</li>
                </ol>

                <p>
                    We welcome you to Webkit24 and look forward to your valuable dedication, technical innovation, and leadership in our shared success.
                </p>

                <p style="margin-top: 6px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 3. Job Joining Letter (A4 Portrait)
     */
    function buildJobJoiningHtml(d) {
        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Employee ID / Ref: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 10px 0 16px;">
                <h1 class="ol-title" style="font-size: 15.5pt;">EMPLOYEE JOINING CONFIRMATION</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 12px;">
                <span class="ol-recipient">To: ${escapeHtml(d.student_name)}</span>
            </div>

            <div class="ol-body" style="gap: 12px;">
                <p>
                    This is to formally confirm and record that <strong>${escapeHtml(d.student_name)}</strong> has reported for duty and officially joined the services of <strong>Webkit24</strong> as a full-time <strong>${escapeHtml(d.position)}</strong> on <strong>${escapeHtml(d.start_date)}</strong>.
                </p>

                <table class="doc-table-params">
                    <tr>
                        <td class="param-label">Employee Full Name:</td>
                        <td class="param-val"><strong>${escapeHtml(d.student_name)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Designation:</td>
                        <td class="param-val"><strong>${escapeHtml(d.position)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Department / Division:</td>
                        <td class="param-val">${escapeHtml(d.domain_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Official Joining Date:</td>
                        <td class="param-val">${escapeHtml(d.start_date)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Assigned Lead / Manager:</td>
                        <td class="param-val">${escapeHtml(d.reporting_manager)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Primary Work Station:</td>
                        <td class="param-val">${escapeHtml(d.work_location)}</td>
                    </tr>
                </table>

                <div class="ol-section-title" style="font-size: 11pt; margin-top: 4px;">Onboarding Status</div>
                <p>
                    All onboarding formalities, corporate credential provisioning, repository permissions, and infrastructure allocations have been successfully executed.
                </p>

                <p>
                    We extend our warmest welcome to you on becoming an integral part of Webkit24. We are confident that your expertise will make a significant impact on our ongoing and future endeavors.
                </p>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 4. Job Relieving Letter (A4 Portrait)
     */
    function buildJobRelievingHtml(d) {
        const pr = getPronouns(d.pronoun);

        return `
        <div class="doc-letter-page pdf-page-container" id="currentDocRenderRoot">
            ${buildLetterHeader()}

            <div class="doc-letter-ref-bar">
                <span>Relieving Ref: <strong class="font-mono">${escapeHtml(d.certificate_id)}</strong></span>
                <span>Date: <strong>${escapeHtml(d.issue_date)}</strong></span>
            </div>

            <div class="ol-title-wrap" style="margin: 10px 0 16px;">
                <h1 class="ol-title" style="font-size: 15.5pt;">EMPLOYEE RELIEVING LETTER</h1>
            </div>

            <div class="ol-meta-row" style="margin-bottom: 12px;">
                <span class="ol-recipient">To: ${escapeHtml(d.student_name)}</span>
            </div>

            <div class="ol-body" style="gap: 12px;">
                <p>
                    This has reference to your formal resignation letter from the services of Webkit24. We hereby confirm that you are relieved of all duties and obligations as a <strong>${escapeHtml(d.position)}</strong> with effect from the close of business hours on <strong>${escapeHtml(d.end_date)}</strong>.
                </p>

                <table class="doc-table-params">
                    <tr>
                        <td class="param-label">Employee Name:</td>
                        <td class="param-val">${escapeHtml(d.student_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Designation at Exit:</td>
                        <td class="param-val"><strong>${escapeHtml(d.position)}</strong></td>
                    </tr>
                    <tr>
                        <td class="param-label">Tenure of Service:</td>
                        <td class="param-val">${escapeHtml(d.start_date)} to ${escapeHtml(d.end_date)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Department:</td>
                        <td class="param-val">${escapeHtml(d.domain_name)}</td>
                    </tr>
                    <tr>
                        <td class="param-label">Clearance & Settlement:</td>
                        <td class="param-val"><strong style="color: #10B981;">No Dues Cleared</strong></td>
                    </tr>
                </table>

                <p>
                    We confirm that you have completed a comprehensive handover of all source repositories, intellectual property, development keys, and hardware assets to the engineering department. Full and final settlement of your accounts has been processed with no outstanding liabilities.
                </p>

                <p>
                    We place on record our sincere appreciation for the dedicated service and contributions you rendered during your employment with Webkit24. We wish you continued success, fulfillment, and prosperity in your future career.
                </p>

                <p style="margin-top: 4px; font-weight: 600;">For Webkit24,</p>
                ${buildDualSignatures(d)}
            </div>

            ${buildLetterFooter()}
        </div>
        `;
    }

    /**
     * 5. Job Experience Certificate (A4 Landscape with QR & Security Seal)
     */
    function buildJobExperienceHtml(d) {
        const pr = getPronouns(d.pronoun);

        return buildCertificateBaseHtml(d, {
            subType: 'E X P E R I E N C E',
            mainTitle: 'C E R T I F I C A T E',
            statement: `has served as a full-time <strong>${escapeHtml(d.position)}</strong> in the <strong>${escapeHtml(d.domain_name)}</strong> division at Webkit24 from <strong>${escapeHtml(d.start_date)}</strong> to <strong>${escapeHtml(d.end_date)}</strong>. During ${pr.poss} tenure, ${pr.subj} demonstrated exceptional technical proficiency, exemplary leadership, commitment, and high professional ethics toward corporate objectives and client deliverables.`
        });
    }

    /**
     * Cache and generator for crystal-clear high-DPI rasterized PNG corner vector assets.
     * Guarantees 100% pixel-perfect PDF rendering without html2canvas SVG polygon bugs.
     */
    let cachedCornerPngs = null;

    function getCornerPngDataUrls() {
        if (cachedCornerPngs) {
            return cachedCornerPngs;
        }

        // 1. Top-Left Geometric Polygon Accents (Vibrant Coral, Dark Coral, Soft Coral)
        const tlCanvas = document.createElement('canvas');
        tlCanvas.width = 840;
        tlCanvas.height = 680;
        const tlCtx = tlCanvas.getContext('2d');
        tlCtx.scale(2, 2);

        // Layer 3: Soft Coral Accent Triangle
        tlCtx.fillStyle = '#FF7062';
        tlCtx.globalAlpha = 0.35;
        tlCtx.beginPath();
        tlCtx.moveTo(120, 0);
        tlCtx.lineTo(380, 0);
        tlCtx.lineTo(0, 330);
        tlCtx.lineTo(0, 260);
        tlCtx.closePath();
        tlCtx.fill();

        // Layer 1: Vibrant Coral Main Triangle
        tlCtx.globalAlpha = 1.0;
        tlCtx.fillStyle = '#FF4938';
        tlCtx.beginPath();
        tlCtx.moveTo(0, 0);
        tlCtx.lineTo(280, 0);
        tlCtx.lineTo(0, 260);
        tlCtx.closePath();
        tlCtx.fill();

        // Layer 2: Deeper Coral Triangle Corner Accent
        tlCtx.fillStyle = '#E33827';
        tlCtx.beginPath();
        tlCtx.moveTo(0, 0);
        tlCtx.lineTo(130, 0);
        tlCtx.lineTo(0, 120);
        tlCtx.closePath();
        tlCtx.fill();

        // 2. Bottom-Right Geometric Navy Polygon with Coral Edge and White Dot Matrix
        const brCanvas = document.createElement('canvas');
        brCanvas.width = 640;
        brCanvas.height = 440;
        const brCtx = brCanvas.getContext('2d');
        brCtx.scale(2, 2);

        // Layer 1: Vibrant Coral Angled Border Stripe
        brCtx.fillStyle = '#FF4938';
        brCtx.beginPath();
        brCtx.moveTo(90, 220);
        brCtx.lineTo(320, 50);
        brCtx.lineTo(320, 220);
        brCtx.closePath();
        brCtx.fill();

        // Layer 2: Dark Navy Base Polygon
        brCtx.fillStyle = '#080D25';
        brCtx.beginPath();
        brCtx.moveTo(160, 220);
        brCtx.lineTo(320, 100);
        brCtx.lineTo(320, 220);
        brCtx.closePath();
        brCtx.fill();

        // Layer 3: Dot Grid inside Dark Navy Polygon
        brCtx.save();
        brCtx.beginPath();
        brCtx.moveTo(160, 220);
        brCtx.lineTo(320, 100);
        brCtx.lineTo(320, 220);
        brCtx.closePath();
        brCtx.clip();

        brCtx.fillStyle = '#FFFFFF';
        brCtx.globalAlpha = 0.65;
        for (let x = 160; x <= 320; x += 14) {
            for (let y = 100; y <= 220; y += 14) {
                brCtx.beginPath();
                brCtx.arc(x, y, 1.4, 0, Math.PI * 2);
                brCtx.fill();
            }
        }
        brCtx.restore();

        // 3. Top-Right Curved Line Waves
        const trCanvas = document.createElement('canvas');
        trCanvas.width = 240;
        trCanvas.height = 240;
        const trCtx = trCanvas.getContext('2d');
        trCtx.scale(2.4, 2.4);
        trCtx.strokeStyle = '#FF4938';
        trCtx.lineWidth = 1.8;
        trCtx.lineCap = 'round';
        const trCurves = [
            { d: [[10, 10], [50, 20], [90, 90]], op: 0.4 },
            { d: [[25, 10], [60, 25], [90, 75]], op: 0.5 },
            { d: [[40, 10], [70, 30], [90, 60]], op: 0.6 },
            { d: [[55, 10], [75, 35], [90, 45]], op: 0.7 },
            { d: [[70, 10], [80, 40], [90, 30]], op: 0.8 }
        ];
        trCurves.forEach(c => {
            trCtx.globalAlpha = c.op;
            trCtx.beginPath();
            trCtx.moveTo(c.d[0][0], c.d[0][1]);
            trCtx.quadraticCurveTo(c.d[1][0], c.d[1][1], c.d[2][0], c.d[2][1]);
            trCtx.stroke();
        });

        // 4. Bottom-Left Curved Line Waves
        const blCanvas = document.createElement('canvas');
        blCanvas.width = 240;
        blCanvas.height = 240;
        const blCtx = blCanvas.getContext('2d');
        blCtx.scale(2.4, 2.4);
        blCtx.strokeStyle = '#FF4938';
        blCtx.lineWidth = 1.8;
        blCtx.lineCap = 'round';
        const blCurves = [
            { d: [[10, 90], [20, 50], [90, 10]], op: 0.4 },
            { d: [[10, 75], [25, 40], [75, 10]], op: 0.5 },
            { d: [[10, 60], [30, 30], [60, 10]], op: 0.6 },
            { d: [[10, 45], [35, 25], [45, 10]], op: 0.7 },
            { d: [[10, 30], [40, 20], [30, 10]], op: 0.8 }
        ];
        blCurves.forEach(c => {
            blCtx.globalAlpha = c.op;
            blCtx.beginPath();
            blCtx.moveTo(c.d[0][0], c.d[0][1]);
            blCtx.quadraticCurveTo(c.d[1][0], c.d[1][1], c.d[2][0], c.d[2][1]);
            blCtx.stroke();
        });

        // 5. Date Calendar Icon (Vibrant Brand Coral with Navy Binder Rings and Dot Grid)
        const dateCanvas = document.createElement('canvas');
        dateCanvas.width = 120;
        dateCanvas.height = 120;
        const dateCtx = dateCanvas.getContext('2d');
        dateCtx.scale(2.5, 2.5); // 48x48 coordinate space

        // Calendar body (rounded rectangle)
        dateCtx.fillStyle = '#FFF8F7';
        dateCtx.strokeStyle = '#FF4938';
        dateCtx.lineWidth = 2.4;
        dateCtx.beginPath();
        if (dateCtx.roundRect) {
            dateCtx.roundRect(6, 10, 36, 32, 5);
        } else {
            dateCtx.rect(6, 10, 36, 32);
        }
        dateCtx.fill();
        dateCtx.stroke();

        // Header bar (coral fill)
        dateCtx.fillStyle = '#FF4938';
        dateCtx.beginPath();
        if (dateCtx.roundRect) {
            dateCtx.roundRect(6, 10, 36, 10, [5, 5, 0, 0]);
        } else {
            dateCtx.rect(6, 10, 36, 10);
        }
        dateCtx.fill();

        // Top Binder Rings (Dark Navy)
        dateCtx.fillStyle = '#080D25';
        dateCtx.beginPath();
        if (dateCtx.roundRect) {
            dateCtx.roundRect(14, 6, 3.5, 8, 1.75);
            dateCtx.roundRect(30.5, 6, 3.5, 8, 1.75);
        } else {
            dateCtx.rect(14, 6, 3.5, 8);
            dateCtx.rect(30.5, 6, 3.5, 8);
        }
        dateCtx.fill();

        // Calendar Grid Dots
        const dots = [
            [15, 26, '#FF4938'], [24, 26, '#FF4938'], [33, 26, '#FF4938'],
            [15, 34, '#FF4938'], [24, 34, '#FF4938'], [33, 34, '#080D25']
        ];
        dots.forEach(([x, y, col]) => {
            dateCtx.fillStyle = col;
            dateCtx.beginPath();
            dateCtx.arc(x, y, 1.8, 0, Math.PI * 2);
            dateCtx.fill();
        });

        cachedCornerPngs = {
            tl: tlCanvas.toDataURL('image/png'),
            br: brCanvas.toDataURL('image/png'),
            tr: trCanvas.toDataURL('image/png'),
            bl: blCanvas.toDataURL('image/png'),
            dateIcon: dateCanvas.toDataURL('image/png')
        };

        return cachedCornerPngs;
    }

    /**
     * Base Shared Generator for Official A4 Landscape Certificates
     */
    function buildCertificateBaseHtml(d, opts) {
        const decorPng = getCornerPngDataUrls();

        return `
        <div class="certificate-page pdf-page-container" id="currentDocRenderRoot">
            <!-- Authentic Security Frame / Double Border -->
            <div class="cert-security-frame"></div>

            <!-- Subtle Background Security Watermark -->
            <img src="../assets/images/cert-watermark.svg" alt="" class="cert-watermark">

            <!-- High-Resolution Canvas-Rendered PNG Vector Corners (Guarantees Flawless PDF Export without SVG Polygon Bugs) -->
            <img src="${decorPng.tl}" alt="" class="cert-decor-top-left">
            <img src="${decorPng.tr}" alt="" class="cert-decor-lines-tr">
            <img src="${decorPng.bl}" alt="" class="cert-decor-lines-bl">
            <img src="${decorPng.br}" alt="" class="cert-decor-bottom-right">

            <!-- Certificate Inner Typography Content -->
            <div class="cert-content">
                <!-- Header Logo -->
                <div class="cert-header">
                    <img src="../assets/images/webkit24-logo.png" alt="Webkit24 Logo" class="cert-logo">
                </div>

                <!-- Headings -->
                <div class="cert-headings">
                    <div class="cert-sub-type">${escapeHtml(opts.subType)}</div>
                    <div class="cert-main-title">${escapeHtml(opts.mainTitle)}</div>
                    <div class="cert-certify-line">
                        <span class="cert-certify-text">T H I S &nbsp; I S &nbsp; T O &nbsp; C E R T I F Y &nbsp; T H A T</span>
                    </div>
                </div>

                <!-- Recipient Name -->
                <div class="cert-recipient-name">
                    ${escapeHtml(d.student_name)}
                </div>

                <!-- Statement -->
                <div class="cert-statement">
                    ${opts.statement}
                </div>

                <!-- Metadata Row (Date Card - Rosette Medal - Unique ID & QR Card) -->
                <div class="cert-meta-row">
                    <!-- Symmetrical Prestigious Date Card -->
                    <div class="cert-meta-box cert-date-verify-box">
                        <div class="cert-date-inner">
                            <div class="cert-doc-date-frame" title="Official Date of Issuance">
                                <img src="${decorPng.dateIcon}" alt="Date" class="cert-doc-date-img">
                            </div>
                            <div class="cert-date-text-col">
                                <span class="cert-meta-label">DATE OF ISSUANCE</span>
                                <span class="cert-meta-val">${escapeHtml(d.issue_date_cert || d.issue_date)}</span>
                                <span class="cert-date-sub">Officially Certified Credential</span>
                            </div>
                        </div>
                    </div>

                    <!-- Certified Rosette Medal Badge -->
                    <div class="cert-medal-box">
                        <img src="../assets/images/certificate-medal.svg" alt="Certified Badge" class="cert-medal-img">
                    </div>

                    <!-- Verification QR Code & Unique ID Box -->
                    <div class="cert-meta-box cert-qr-verify-box">
                        <div class="cert-qr-inner">
                            <div id="certDocQrCanvas" class="cert-doc-qr-frame" title="Scan to Verify Certificate"></div>
                            <div class="cert-qr-text-col">
                                <span class="cert-meta-label">UNIQUE ID</span>
                                <span class="cert-meta-val font-mono">${escapeHtml(d.certificate_id)}</span>
                                <span class="cert-verify-link">Verify at: certificate.webkit24.com</span>
                                <span class="cert-scan-tip">Scan QR to verify authenticity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signatures Row -->
                <div class="cert-signatures-row">
                    <div class="cert-sig-block">
                        <div class="cert-sig-wrap">
                            <img src="../assets/images/signature-aditya.png" alt="Aditya Singh Signature" class="cert-sig-img">
                        </div>
                        <div class="cert-sig-line"></div>
                        <div class="cert-sig-name">${escapeHtml(d.sig1_name)}</div>
                        <div class="cert-sig-title">${escapeHtml(d.sig1_title)}</div>
                    </div>

                    <div class="cert-sig-block">
                        <div class="cert-sig-wrap">
                            <img src="../assets/images/signature-sudhanshu.png" alt="Sudhanshu Pandey Signature" class="cert-sig-img">
                        </div>
                        <div class="cert-sig-line"></div>
                        <div class="cert-sig-name">${escapeHtml(d.sig2_name)}</div>
                        <div class="cert-sig-title">${escapeHtml(d.sig2_title)}</div>
                    </div>
                </div>

                <!-- Authentic Security Microprint Strip -->
                <div class="cert-security-strip">
                    <span>OFFICIAL DIGITAL CREDENTIAL</span>
                    <span class="strip-dot">◆</span>
                    <span>ISSUED BY WEBKIT24</span>
                    <span class="strip-dot">◆</span>
                    <span>VERIFY AT CERTIFICATE.WEBKIT24.COM</span>
                    <span class="strip-dot">◆</span>
                    <span>SECURE QR VERIFIED</span>
                </div>
            </div>
        </div>
        `;
    }

    /**
     * Dispatcher to build HTML based on current document type
     */
    function buildCurrentDocHtml() {
        switch (currentDocType) {
            // a- Internship Documents
            case 'internship_offer':
                return buildInternshipOfferHtml(state);
            case 'internship_joining':
                return buildInternshipJoiningHtml(state);
            case 'internship_completion':
                return buildInternshipCompletionHtml(state);
            case 'internship_certificate':
                return buildInternshipCertificateHtml(state);
            case 'internship_relieving':
                return buildInternshipRelievingHtml(state);

            // b- Job / Employee Documents
            case 'job_offer':
                return buildJobOfferHtml(state);
            case 'job_appointment':
                return buildJobAppointmentHtml(state);
            case 'job_joining':
                return buildJobJoiningHtml(state);
            case 'job_relieving':
                return buildJobRelievingHtml(state);
            case 'job_experience':
                return buildJobExperienceHtml(state);

            default:
                return buildInternshipCertificateHtml(state);
        }
    }

    let zoomMode = 'fit'; // 'fit' or 'manual'
    let currentScale = 1.0;

    /**
     * Auto-Fit & Responsive Zoom Controller
     * Ensures document fits 100% inside any viewport width/height without horizontal/vertical cut-offs.
     */
    function applyZoom(modeOrScale) {
        const scrollArea = document.getElementById('previewScrollArea') || document.querySelector('.preview-scroll-area');
        const wrapper = document.getElementById('previewScaleWrapper');
        const root = document.getElementById('currentDocRenderRoot');
        if (!scrollArea || !wrapper || !root) return;

        let scale = 1.0;
        if (modeOrScale === 'fit') {
            zoomMode = 'fit';
            const availW = Math.max(scrollArea.clientWidth - 50, 320);
            const availH = Math.max(scrollArea.clientHeight - 50, 320);
            const docW = root.offsetWidth || (currentDocType.includes('certificate') ? 1122 : 794);
            const docH = root.offsetHeight || (currentDocType.includes('certificate') ? 790 : 1122);
            const scaleW = availW / docW;
            const scaleH = availH / docH;
            scale = Math.min(scaleW, scaleH);
            if (scale > 1.0) scale = 1.0;
            if (scale < 0.25) scale = 0.25;
        } else if (typeof modeOrScale === 'number') {
            zoomMode = 'manual';
            scale = Math.max(0.25, Math.min(2.0, Math.round(modeOrScale * 100) / 100));
        }

        currentScale = scale;
        wrapper.style.transform = `scale(${scale})`;

        // Adjust layout margins so scrollbars adapt to the visual scaled boundary
        const docH = root.offsetHeight || 800;
        const docW = root.offsetWidth || 1122;
        wrapper.style.marginBottom = `${(docH * scale) - docH}px`;
        wrapper.style.marginRight = `${(docW * scale) - docW}px`;

        // Update Zoom UI buttons & badge
        const badge = document.getElementById('zoomLevelBadge');
        if (badge) badge.textContent = `${Math.round(scale * 100)}%`;

        const btnFit = document.getElementById('btnZoomFit');
        const btn100 = document.getElementById('btnZoom100');
        if (btnFit) btnFit.classList.toggle('active', zoomMode === 'fit');
        if (btn100) btn100.classList.toggle('active', zoomMode === 'manual' && Math.round(scale * 100) === 100);
    }

    /**
     * Render the active document into preview canvas
     */
    function renderPreviews() {
        const renderArea = document.getElementById('activeDocRenderArea');
        if (!renderArea) return;

        renderArea.innerHTML = buildCurrentDocHtml();

        // Update Orientation badge
        const badge = document.getElementById('activeDocBadge');
        const docDef = DOC_REGISTRY[currentDocType] || DOC_REGISTRY.internship_certificate;
        if (badge) {
            badge.textContent = docDef.orientation === 'landscape'
                ? 'A4 Landscape (297 × 210 mm)'
                : 'A4 Portrait (210 × 297 mm)';
        }

        // Generate dynamic high-resolution QR code if document has QR canvas (Certificates)
        const qrFrame = document.getElementById('certDocQrCanvas');
        if (qrFrame && typeof QRCode !== 'undefined') {
            qrFrame.innerHTML = '';
            const verifyUrl = 'https://certificate.webkit24.com/?id=' + encodeURIComponent(state.certificate_id);
            try {
                new QRCode(qrFrame, {
                    text: verifyUrl,
                    width: 96,
                    height: 96,
                    colorDark: '#080D25',
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.M
                });

                // Apply strict containment styling to ensure QR fits perfectly inside its 20mm box
                const syncQrElements = () => {
                    const cvs = qrFrame.querySelector('canvas');
                    let img = qrFrame.querySelector('img');
                    if (cvs) {
                        cvs.style.width = '100%';
                        cvs.style.height = '100%';
                        cvs.style.maxWidth = '100%';
                        cvs.style.maxHeight = '100%';
                        cvs.style.display = 'block';
                        try {
                            const dataUrl = cvs.toDataURL('image/png');
                            if (!img) {
                                img = document.createElement('img');
                                qrFrame.appendChild(img);
                            }
                            img.src = dataUrl;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '100%';
                            img.style.display = 'block';
                            img.style.objectFit = 'contain';
                            cvs.style.display = 'none';
                        } catch (e) {}
                    }
                    if (img) {
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        img.style.objectFit = 'contain';
                    }
                };

                syncQrElements();
                setTimeout(syncQrElements, 40);
                setTimeout(syncQrElements, 120);
            } catch (qrErr) {
                console.error('QR code generation error:', qrErr);
            }
        }

        // Auto-scale preview to fit screen without overlapping
        setTimeout(() => applyZoom(zoomMode), 30);
    }

    /**
     * Render Sub-Tabs in Studio Toolbar
     */
    function renderSubTabs() {
        const container = document.getElementById('docSubtabsContainer');
        if (!container) return;

        container.innerHTML = '';

        // Filter documents belonging to current category
        const docKeys = Object.keys(DOC_REGISTRY).filter(k => DOC_REGISTRY[k].category === currentCategory);

        docKeys.forEach(key => {
            const def = DOC_REGISTRY[key];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `doc-tab-btn ${key === currentDocType ? 'active' : ''}`;
            btn.innerHTML = `<span>${def.icon} ${escapeHtml(def.shortLabel)}</span>`;
            btn.addEventListener('click', () => {
                switchDoc(key);
            });
            container.appendChild(btn);
        });
    }

    /**
     * Switch Active Document
     */
    function switchDoc(docKey) {
        if (!DOC_REGISTRY[docKey]) return;

        currentDocType = docKey;
        currentCategory = DOC_REGISTRY[docKey].category;

        // Update Category Buttons
        const internCatBtn = document.getElementById('catBtnInternship');
        const jobCatBtn = document.getElementById('catBtnJob');
        if (internCatBtn && jobCatBtn) {
            if (currentCategory === 'internship') {
                internCatBtn.classList.add('active');
                jobCatBtn.classList.remove('active');
            } else {
                internCatBtn.classList.remove('active');
                jobCatBtn.classList.add('active');
            }
        }

        renderSubTabs();
        renderPreviews();
    }

    /**
     * Switch Category
     */
    function switchCategory(cat) {
        currentCategory = cat;

        // Pick first document in category if current isn't in it
        if (DOC_REGISTRY[currentDocType].category !== cat) {
            const firstKey = Object.keys(DOC_REGISTRY).find(k => DOC_REGISTRY[k].category === cat);
            if (firstKey) currentDocType = firstKey;
        }

        const internCatBtn = document.getElementById('catBtnInternship');
        const jobCatBtn = document.getElementById('catBtnJob');
        if (internCatBtn && jobCatBtn) {
            if (currentCategory === 'internship') {
                internCatBtn.classList.add('active');
                jobCatBtn.classList.remove('active');
            } else {
                internCatBtn.classList.remove('active');
                jobCatBtn.classList.add('active');
            }
        }

        renderSubTabs();
        renderPreviews();
    }

    /**
     * Download PDF using html2pdf.js ensuring STRICT 1-PAGE A4 output
     */
    async function downloadPdf() {
        const targetElement = document.getElementById('currentDocRenderRoot');

        if (!targetElement) {
            alert('Template target element not found for rendering.');
            return;
        }

        const docDef = DOC_REGISTRY[currentDocType] || DOC_REGISTRY.internship_certificate;
        const isLandscape = docDef.orientation === 'landscape';

        const studentSafeName = (state.student_name || 'Candidate').replace(/[^a-zA-Z0-9_-]/g, '_');
        const filename = `${studentSafeName}_Webkit24_${docDef.fileSuffix}.pdf`;

        // Configure options to strictly avoid page break and enforce exact A4 1-page output
        const opt = {
            margin:       0,
            filename:     filename,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { 
                scale: 2.5, 
                useCORS: true, 
                logging: false, 
                scrollX: 0, 
                scrollY: 0,
                letterRendering: true
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: isLandscape ? 'landscape' : 'portrait'
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        // UI Feedback
        const dlBtn = document.getElementById('btnDownloadActivePdf');
        const originalText = dlBtn ? dlBtn.innerHTML : '';
        if (dlBtn) {
            dlBtn.disabled = true;
            dlBtn.innerHTML = '<span>Exporting Single-Page PDF...</span>';
        }

        // Temporarily reset zoom transformation for 1:1 true-size PDF capture
        const scaleWrapper = document.getElementById('previewScaleWrapper');
        const prevTransform = scaleWrapper ? scaleWrapper.style.transform : '';
        const prevMarginBottom = scaleWrapper ? scaleWrapper.style.marginBottom : '';
        const prevMarginRight = scaleWrapper ? scaleWrapper.style.marginRight : '';
        if (scaleWrapper) {
            scaleWrapper.style.transform = 'none';
            scaleWrapper.style.marginBottom = '0px';
            scaleWrapper.style.marginRight = '0px';
        }

        try {
            // Strictly enforce exact height limit on target before capturing
            if (isLandscape) {
                targetElement.style.height = '209mm';
                targetElement.style.maxHeight = '209mm';
            } else {
                targetElement.style.height = '296mm';
                targetElement.style.maxHeight = '296mm';
            }
            targetElement.style.overflow = 'hidden';

            // Pre-load and sync all image assets
            const imgs = Array.from(targetElement.querySelectorAll('img'));
            await Promise.all(imgs.map(img => {
                if (img.complete && img.naturalWidth !== 0) return Promise.resolve();
                return new Promise(resolve => {
                    img.onload = img.onerror = resolve;
                });
            }));

            // Ensure QR image is fully synchronized before capturing canvas
            if (isLandscape) {
                const qrFrame = document.getElementById('certDocQrCanvas');
                if (qrFrame) {
                    const cvs = qrFrame.querySelector('canvas');
                    let img = qrFrame.querySelector('img');
                    if (cvs) {
                        try {
                            const dataUrl = cvs.toDataURL('image/png');
                            if (!img) {
                                img = document.createElement('img');
                                qrFrame.appendChild(img);
                            }
                            img.src = dataUrl;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '100%';
                            img.style.objectFit = 'contain';
                            img.style.display = 'block';
                            cvs.style.display = 'none';
                        } catch (e) {}
                    }
                    if (img) {
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        img.style.objectFit = 'contain';
                    }
                }
            }

            if (typeof html2pdf !== 'undefined') {
                await html2pdf().set(opt).from(targetElement).save();
            } else {
                // Fallback to print
                window.print();
            }
        } catch (err) {
            console.error('PDF Generation Error:', err);
            alert('Could not generate PDF directly. Opening print dialog instead.');
            window.print();
        } finally {
            if (scaleWrapper) {
                scaleWrapper.style.transform = prevTransform;
                scaleWrapper.style.marginBottom = prevMarginBottom;
                scaleWrapper.style.marginRight = prevMarginRight;
            }
            if (dlBtn) {
                dlBtn.disabled = false;
                dlBtn.innerHTML = originalText;
            }
        }
    }

    /**
     * Load an existing certificate into the editor
     */
    function loadCertificateRecord(cert) {
        state.student_name = cert.student_name || 'Hariom Pandey';
        state.certificate_id = cert.certificate_id || 'WEBKIT24/INT/2026/0224';
        state.position = cert.position || 'Frontend Developer Intern';
        state.domain_name = cert.position.replace(/\s+intern(ship)?/i, '') || 'Frontend Web Development';
        state.start_date = formatLongDate(cert.start_date);
        state.end_date = formatLongDate(cert.end_date);
        state.issue_date = formatShortDate(cert.issue_date);
        state.issue_date_cert = formatCertDate(cert.issue_date);

        // Auto-switch to Job category if certificate type is Experience
        if (cert.certificate_type === 'Experience') {
            switchDoc('job_experience');
        } else {
            switchDoc('internship_certificate');
        }

        // Populate form inputs
        const nameEl = document.getElementById('genStudentName');
        const certIdEl = document.getElementById('genCertId');
        const roleEl = document.getElementById('genRole');
        const domainEl = document.getElementById('genDomain');
        const startEl = document.getElementById('genStartDate');
        const endEl = document.getElementById('genEndDate');
        const issueEl = document.getElementById('genIssueDate');

        if (nameEl) nameEl.value = state.student_name;
        if (certIdEl) certIdEl.value = state.certificate_id;
        if (roleEl) roleEl.value = state.position;
        if (domainEl) domainEl.value = state.domain_name;
        if (startEl && cert.start_date) startEl.value = cert.start_date;
        if (endEl && cert.end_date) endEl.value = cert.end_date;
        if (issueEl && cert.issue_date) issueEl.value = cert.issue_date;

        renderPreviews();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    return {
        init: function () {
            // Event Listeners for form inputs
            const inputs = [
                'genStudentName', 'genCertId', 'genRole', 'genDomain',
                'genStartDate', 'genEndDate', 'genIssueDate', 'genJobType',
                'genSalaryCtc', 'genReportingManager', 'genWorkLocation',
                'genProbation', 'genNoticePeriod', 'genPronoun', 'genBullets'
            ];

            inputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateStateFromInputs);
                    el.addEventListener('change', updateStateFromInputs);
                }
            });

            // Category Toggle Buttons
            const internCatBtn = document.getElementById('catBtnInternship');
            const jobCatBtn = document.getElementById('catBtnJob');

            if (internCatBtn) {
                internCatBtn.addEventListener('click', () => switchCategory('internship'));
            }
            if (jobCatBtn) {
                jobCatBtn.addEventListener('click', () => switchCategory('job'));
            }

            // Print & Download Buttons
            const printBtn = document.getElementById('btnPrintActiveDoc');
            const downloadBtn = document.getElementById('btnDownloadActivePdf');

            if (printBtn) {
                printBtn.addEventListener('click', () => window.print());
            }

            if (downloadBtn) {
                downloadBtn.addEventListener('click', () => downloadPdf());
            }

            // Interactive Zoom Controls
            const btnZoomIn = document.getElementById('btnZoomIn');
            const btnZoomOut = document.getElementById('btnZoomOut');
            const btnZoomFit = document.getElementById('btnZoomFit');
            const btnZoom100 = document.getElementById('btnZoom100');
            const btnToggleSidebar = document.getElementById('btnToggleSidebar');

            if (btnZoomIn) {
                btnZoomIn.addEventListener('click', () => applyZoom(currentScale + 0.1));
            }
            if (btnZoomOut) {
                btnZoomOut.addEventListener('click', () => applyZoom(currentScale - 0.1));
            }
            if (btnZoomFit) {
                btnZoomFit.addEventListener('click', () => applyZoom('fit'));
            }
            if (btnZoom100) {
                btnZoom100.addEventListener('click', () => applyZoom(1.0));
            }

            // Collapsible Sidebar for Wide View
            if (btnToggleSidebar) {
                btnToggleSidebar.addEventListener('click', () => {
                    const layout = document.querySelector('.studio-layout');
                    const text = document.getElementById('sidebarToggleText');
                    if (layout) {
                        layout.classList.toggle('sidebar-collapsed');
                        const isCollapsed = layout.classList.contains('sidebar-collapsed');
                        if (text) {
                            text.textContent = isCollapsed ? '▶ Show Form' : '◀ Hide Form';
                        }
                        setTimeout(() => applyZoom(zoomMode === 'fit' ? 'fit' : currentScale), 260);
                    }
                });
            }

            // Window resize auto-recalibration
            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (zoomMode === 'fit') {
                        applyZoom('fit');
                    }
                }, 100);
            });

            // Initial render of sub-tabs and active preview
            renderSubTabs();
            renderPreviews();
        },

        loadCertificateRecord: loadCertificateRecord,
        switchDoc: switchDoc,
        switchCategory: switchCategory,
        downloadPdf: downloadPdf,
        applyZoom: applyZoom
    };
})();
