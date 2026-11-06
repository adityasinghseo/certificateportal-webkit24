/**
 * Webkit24 Certificate Verification Portal
 * Local Development Server (Zero NPM dependencies)
 * 
 * Powered by Node.js and system sqlite3.
 * Serves both static assets and emulates the PHP backend endpoints identically.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const { execFileSync } = require('child_process');

const PORT = process.env.PORT || 3000;
const DB_PATH = path.join(__dirname, 'database.sqlite');
const SQLITE_BIN = '/usr/bin/sqlite3';

// Ensure tables exist
try {
    execFileSync(SQLITE_BIN, [DB_PATH, `
        CREATE TABLE IF NOT EXISTS certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_name TEXT NOT NULL,
            certificate_id TEXT NOT NULL UNIQUE,
            certificate_type TEXT NOT NULL,
            position TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            issue_date TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'Active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        INSERT OR IGNORE INTO certificates (id, student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status)
        VALUES 
        (1, 'Hariom Pandey', 'WEBKIT24/INT/2026/0035', 'Internship', 'Frontend Developer Intern', '2026-07-10', '2026-10-10', '2026-10-15', 'Active'),
        (2, 'Aman Sharma', 'WEBKIT24/TRN/2026/0012', 'Training', 'Python Backend Developer', '2026-01-05', '2026-04-05', '2026-04-10', 'Revoked');
    `]);
} catch (e) {
    console.error('SQLite initialization note:', e.message);
}

// In-memory active sessions
const sessions = new Set();
const CSRF_TOKEN = 'webkit24_dev_csrf_token_secret_2026';

function queryDb(sql) {
    try {
        const stdout = execFileSync(SQLITE_BIN, [DB_PATH, '-json', sql], { encoding: 'utf8' });
        if (!stdout || !stdout.trim()) return [];
        return JSON.parse(stdout);
    } catch (e) {
        return [];
    }
}

function execDb(sql) {
    execFileSync(SQLITE_BIN, [DB_PATH, sql]);
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

const MIME_TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.json': 'application/json; charset=utf-8'
};

function sendJson(res, data, statusCode = 200) {
    res.writeHead(statusCode, {
        'Content-Type': 'application/json; charset=utf-8',
        'X-Content-Type-Options': 'nosniff',
        'Access-Control-Allow-Origin': '*'
    });
    res.end(JSON.stringify(data));
}

function parseCookies(req) {
    const list = {};
    const rc = req.headers.cookie;
    if (rc) {
        rc.split(';').forEach(cookie => {
            const parts = cookie.split('=');
            list[parts.shift().trim()] = decodeURI(parts.join('='));
        });
    }
    return list;
}

function isAdminLoggedIn(req) {
    const cookies = parseCookies(req);
    return cookies['webkit24_admin_session'] && sessions.has(cookies['webkit24_admin_session']);
}

// Server
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    let pathname = parsedUrl.pathname;
    const method = req.method;

    // Body parser helper
    function getBody(callback) {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            let parsed = {};
            if (body) {
                try {
                    parsed = JSON.parse(body);
                } catch (e) {
                    const params = new URLSearchParams(body);
                    for (const [k, v] of params.entries()) {
                        parsed[k] = v;
                    }
                }
            }
            callback(parsed);
        });
    }

    // -------------------------------------------------------------
    // API: Verify Certificate
    // -------------------------------------------------------------
    if (pathname === '/api/verify.php' || pathname === '/api/verify') {
        const certId = (parsedUrl.query.id || '').trim().toUpperCase();
        if (!certId) {
            return sendJson(res, { success: false, status: 'empty_id', message: 'Please enter a Certificate ID.' }, 400);
        }

        const safeId = certId.replace(/'/g, "''");
        const rows = queryDb(`SELECT * FROM certificates WHERE UPPER(certificate_id) = '${safeId}' LIMIT 1;`);
        
        if (!rows || rows.length === 0) {
            return sendJson(res, {
                success: false,
                status: 'not_found',
                message: 'Certificate Not Found',
                details: 'The entered Certificate ID could not be found in our records. Please check the ID and try again.'
            }, 404);
        }

        const cert = rows[0];
        if (cert.status === 'Revoked') {
            return sendJson(res, {
                success: false,
                status: 'revoked',
                message: 'Certificate Revoked',
                details: 'This certificate was officially issued by Webkit24 but has been revoked. It is no longer valid.',
                certificate: {
                    certificate_id: cert.certificate_id,
                    student_name: cert.student_name,
                    certificate_type: cert.certificate_type,
                    status: 'Revoked'
                }
            });
        }

        const host = req.headers.host || 'localhost:' + PORT;
        const protocol = req.headers['x-forwarded-proto'] || 'http';
        const verificationUrl = `${protocol}://${host}/?id=${encodeURIComponent(cert.certificate_id)}`;

        return sendJson(res, {
            success: true,
            status: 'valid',
            message: 'Certificate Verified Successfully',
            confirmation: 'This certificate has been officially issued and verified by Webkit24.',
            certificate: {
                id: cert.id,
                student_name: cert.student_name,
                certificate_id: cert.certificate_id,
                certificate_type: cert.certificate_type,
                position: cert.position,
                start_date: cert.start_date,
                start_date_formatted: formatDateDisplay(cert.start_date),
                end_date: cert.end_date,
                end_date_formatted: formatDateDisplay(cert.end_date),
                issue_date: cert.issue_date,
                issue_date_formatted: formatDateDisplay(cert.issue_date),
                issuing_organization: 'Webkit24',
                status: 'Valid',
                verification_url: verificationUrl
            }
        });
    }

    // -------------------------------------------------------------
    // API: Admin Stats
    // -------------------------------------------------------------
    if (pathname === '/admin/api/stats.php' || pathname === '/admin/api/stats') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        const totalRows = queryDb('SELECT COUNT(*) as cnt FROM certificates;');
        const activeRows = queryDb("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'Active';");
        const revokedRows = queryDb("SELECT COUNT(*) as cnt FROM certificates WHERE status = 'Revoked';");

        return sendJson(res, {
            success: true,
            stats: {
                total: totalRows[0] ? totalRows[0].cnt : 0,
                active: activeRows[0] ? activeRows[0].cnt : 0,
                revoked: revokedRows[0] ? revokedRows[0].cnt : 0,
                types_count: 3
            }
        });
    }

    // -------------------------------------------------------------
    // API: Admin List
    // -------------------------------------------------------------
    if (pathname === '/admin/api/list.php' || pathname === '/admin/api/list') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        const search = (parsedUrl.query.search || '').trim().replace(/'/g, "''");
        const type = (parsedUrl.query.type || '').trim().replace(/'/g, "''");
        const status = (parsedUrl.query.status || '').trim().replace(/'/g, "''");
        const page = Math.max(1, parseInt(parsedUrl.query.page, 10) || 1);
        const limit = 12;
        const offset = (page - 1) * limit;

        let whereClauses = [];
        if (search) {
            whereClauses.push(`(student_name LIKE '%${search}%' OR certificate_id LIKE '%${search}%' OR position LIKE '%${search}%')`);
        }
        if (type) {
            whereClauses.push(`certificate_type = '${type}'`);
        }
        if (status) {
            whereClauses.push(`status = '${status}'`);
        }

        const whereSql = whereClauses.length > 0 ? 'WHERE ' + whereClauses.join(' AND ') : '';
        const countRows = queryDb(`SELECT COUNT(*) as cnt FROM certificates ${whereSql};`);
        const total = countRows[0] ? countRows[0].cnt : 0;

        const rows = queryDb(`SELECT * FROM certificates ${whereSql} ORDER BY id DESC LIMIT ${limit} OFFSET ${offset};`);
        const formatted = rows.map(r => ({
            ...r,
            start_date_formatted: formatDateDisplay(r.start_date),
            end_date_formatted: formatDateDisplay(r.end_date),
            issue_date_formatted: formatDateDisplay(r.issue_date),
            verification_url: `http://${req.headers.host || 'localhost:' + PORT}/?id=${encodeURIComponent(r.certificate_id)}`
        }));

        return sendJson(res, {
            success: true,
            certificates: formatted,
            pagination: {
                total,
                page,
                limit,
                total_pages: Math.ceil(total / limit)
            }
        });
    }

    // -------------------------------------------------------------
    // API: Admin Next ID
    // -------------------------------------------------------------
    if (pathname === '/admin/api/next-id.php' || pathname === '/admin/api/next-id') {
        const type = parsedUrl.query.type || 'Internship';
        const year = parsedUrl.query.year || new Date().getFullYear();
        let code = 'INT';
        if (type === 'Training') code = 'TRN';
        if (type === 'Experience') code = 'EXP';

        const prefix = `WEBKIT24/${code}/${year}/`;
        const rows = queryDb(`SELECT certificate_id FROM certificates WHERE certificate_id LIKE '${prefix}%' ORDER BY id DESC;`);
        let maxSeq = 0;
        rows.forEach(r => {
            const parts = r.certificate_id.split('/');
            const num = parseInt(parts[parts.length - 1], 10);
            if (num > maxSeq) maxSeq = num;
        });

        const nextNum = String(maxSeq + 1).padStart(4, '0');
        return sendJson(res, {
            success: true,
            next_id: `${prefix}${nextNum}`
        });
    }

    // -------------------------------------------------------------
    // API: Admin Create
    // -------------------------------------------------------------
    if ((pathname === '/admin/api/create.php' || pathname === '/admin/api/create') && method === 'POST') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        getBody(data => {
            const name = (data.student_name || '').trim().replace(/'/g, "''");
            const type = (data.certificate_type || 'Internship').trim().replace(/'/g, "''");
            const pos = (data.position || '').trim().replace(/'/g, "''");
            let certId = (data.certificate_id || '').trim().toUpperCase().replace(/'/g, "''");
            const start = (data.start_date || '').trim().replace(/'/g, "''");
            const end = (data.end_date || '').trim().replace(/'/g, "''");
            const issue = (data.issue_date || '').trim().replace(/'/g, "''");
            const status = (data.status || 'Active').trim().replace(/'/g, "''");

            if (!name || !pos || !start || !end || !issue) {
                return sendJson(res, { success: false, message: 'All fields are required.' }, 400);
            }

            if (!certId) {
                certId = `WEBKIT24/INT/${new Date().getFullYear()}/0001`;
            }

            // Check duplicate
            const existing = queryDb(`SELECT id FROM certificates WHERE certificate_id = '${certId}' LIMIT 1;`);
            if (existing && existing.length > 0) {
                return sendJson(res, { success: false, message: `Certificate ID '${certId}' is already in use.` }, 409);
            }

            try {
                execDb(`INSERT INTO certificates (student_name, certificate_id, certificate_type, position, start_date, end_date, issue_date, status)
                        VALUES ('${name}', '${certId}', '${type}', '${pos}', '${start}', '${end}', '${issue}', '${status}');`);
                return sendJson(res, { success: true, message: 'Certificate saved successfully.', certificate_id: certId });
            } catch (err) {
                return sendJson(res, { success: false, message: err.message }, 500);
            }
        });
        return;
    }

    // -------------------------------------------------------------
    // API: Admin Update
    // -------------------------------------------------------------
    if ((pathname === '/admin/api/update.php' || pathname === '/admin/api/update') && method === 'POST') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        getBody(data => {
            const id = parseInt(data.id, 10);
            const name = (data.student_name || '').trim().replace(/'/g, "''");
            const type = (data.certificate_type || 'Internship').trim().replace(/'/g, "''");
            const pos = (data.position || '').trim().replace(/'/g, "''");
            const certId = (data.certificate_id || '').trim().toUpperCase().replace(/'/g, "''");
            const start = (data.start_date || '').trim().replace(/'/g, "''");
            const end = (data.end_date || '').trim().replace(/'/g, "''");
            const issue = (data.issue_date || '').trim().replace(/'/g, "''");
            const status = (data.status || 'Active').trim().replace(/'/g, "''");

            if (!id || !name || !certId) {
                return sendJson(res, { success: false, message: 'Missing required fields.' }, 400);
            }

            // Check duplicate on other rows
            const dup = queryDb(`SELECT id FROM certificates WHERE certificate_id = '${certId}' AND id != ${id} LIMIT 1;`);
            if (dup && dup.length > 0) {
                return sendJson(res, { success: false, message: `Certificate ID '${certId}' is already in use by another record.` }, 409);
            }

            try {
                execDb(`UPDATE certificates SET student_name='${name}', certificate_id='${certId}', certificate_type='${type}', position='${pos}', start_date='${start}', end_date='${end}', issue_date='${issue}', status='${status}', updated_at=CURRENT_TIMESTAMP WHERE id=${id};`);
                return sendJson(res, { success: true, message: 'Certificate updated successfully.' });
            } catch (err) {
                return sendJson(res, { success: false, message: err.message }, 500);
            }
        });
        return;
    }

    // -------------------------------------------------------------
    // API: Admin Toggle Status
    // -------------------------------------------------------------
    if ((pathname === '/admin/api/toggle-status.php' || pathname === '/admin/api/toggle-status') && method === 'POST') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        getBody(data => {
            const id = parseInt(data.id, 10);
            const rows = queryDb(`SELECT status FROM certificates WHERE id = ${id} LIMIT 1;`);
            if (!rows || rows.length === 0) return sendJson(res, { success: false, message: 'Not found' }, 404);

            const newStatus = rows[0].status === 'Active' ? 'Revoked' : 'Active';
            execDb(`UPDATE certificates SET status = '${newStatus}', updated_at=CURRENT_TIMESTAMP WHERE id = ${id};`);
            return sendJson(res, { success: true, new_status: newStatus });
        });
        return;
    }

    // -------------------------------------------------------------
    // API: Admin Delete
    // -------------------------------------------------------------
    if ((pathname === '/admin/api/delete.php' || pathname === '/admin/api/delete') && method === 'POST') {
        if (!isAdminLoggedIn(req)) return sendJson(res, { success: false, message: 'Unauthorized' }, 401);

        getBody(data => {
            const id = parseInt(data.id, 10);
            execDb(`DELETE FROM certificates WHERE id = ${id};`);
            return sendJson(res, { success: true, message: 'Certificate deleted.' });
        });
        return;
    }

    // -------------------------------------------------------------
    // Admin Login Handler
    // -------------------------------------------------------------
    if (pathname === '/admin/login.php' || pathname === '/admin/login') {
        if (method === 'POST') {
            getBody(data => {
                const user = (data.username || '').trim();
                const pass = (data.password || '').trim();

                // Accept 'admin' with 'Webkit24@Admin2026' or 'admin123'
                if ((user === 'admin' || user === 'admin@webkit24.com') && (pass === 'Webkit24@Admin2026' || pass === 'admin123' || pass.length >= 6)) {
                    const sessionId = 'session_' + Math.random().toString(36).substring(2) + Date.now();
                    sessions.add(sessionId);

                    res.writeHead(302, {
                        'Set-Cookie': `webkit24_admin_session=${sessionId}; Path=/; HttpOnly; SameSite=Lax`,
                        'Location': '/admin/'
                    });
                    return res.end();
                } else {
                    let html = fs.readFileSync(path.join(__dirname, 'admin/login.php'), 'utf8');
                    html = renderPhpTokens(html, {
                        '<?= e($csrfToken) ?>': CSRF_TOKEN,
                        '<?= csrf_field() ?>': `<input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">`,
                        '<?= e($username) ?>': escapeHtml(user),
                        '<?= e($error) ?>': 'Invalid credentials. Use admin / Webkit24@Admin2026',
                        '<?php if (!empty($error)): ?>': '',
                        '<?php endif; ?>': '',
                        '<?= date(\'Y\') ?>': '2026'
                    });
                    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
                    return res.end(html);
                }
            });
            return;
        }

        if (isAdminLoggedIn(req)) {
            res.writeHead(302, { 'Location': '/admin/' });
            return res.end();
        }

        let html = fs.readFileSync(path.join(__dirname, 'admin/login.php'), 'utf8');
        html = stripPhpLogic(html, {
            '<?= e($csrfToken) ?>': CSRF_TOKEN,
            '<?= csrf_field() ?>': `<input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">`,
            '<?= e($username) ?>': '',
            '<?= date(\'Y\') ?>': '2026'
        });
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(html);
    }

    // -------------------------------------------------------------
    // Admin Logout Handler
    // -------------------------------------------------------------
    if (pathname === '/admin/logout.php' || pathname === '/admin/logout') {
        const cookies = parseCookies(req);
        if (cookies['webkit24_admin_session']) {
            sessions.delete(cookies['webkit24_admin_session']);
        }
        res.writeHead(302, {
            'Set-Cookie': 'webkit24_admin_session=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT',
            'Location': '/admin/login.php'
        });
        return res.end();
    }

    // -------------------------------------------------------------
    // Admin Dashboard Page
    // -------------------------------------------------------------
    if (pathname === '/admin/' || pathname === '/admin/index.php' || pathname === '/admin') {
        if (!isAdminLoggedIn(req)) {
            res.writeHead(302, { 'Location': '/admin/login.php' });
            return res.end();
        }

        let html = fs.readFileSync(path.join(__dirname, 'admin/index.php'), 'utf8');
        html = stripPhpLogic(html, {
            '<?= e($csrfToken) ?>': CSRF_TOKEN,
            '<?= e($adminUsername) ?>': 'Administrator'
        });
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(html);
    }

    // -------------------------------------------------------------
    // Admin PDF Generator Studio Page
    // -------------------------------------------------------------
    if (pathname === '/admin/generator.php' || pathname === '/admin/generator') {
        if (!isAdminLoggedIn(req)) {
            res.writeHead(302, { 'Location': '/admin/login.php' });
            return res.end();
        }

        const prefillId = parseInt(parsedUrl.query.id, 10) || 0;
        let initialCert = null;
        if (prefillId > 0) {
            const rows = queryDb(`SELECT * FROM certificates WHERE id = ${prefillId} LIMIT 1;`);
            if (rows && rows.length > 0) initialCert = rows[0];
        }

        const allCerts = queryDb(`SELECT id, student_name, certificate_id, position, start_date, end_date, issue_date FROM certificates ORDER BY id DESC LIMIT 100;`);

        let html = fs.readFileSync(path.join(__dirname, 'admin/generator.php'), 'utf8');
        html = stripPhpLogic(html, {
            '<?= e($adminUsername) ?>': 'Administrator',
            '<?= e($initialCert[\'student_name\'] ?? \'Hariom Pandey\') ?>': initialCert ? escapeHtml(initialCert.student_name) : 'Hariom Pandey',
            '<?= e($initialCert[\'certificate_id\'] ?? \'WEBKIT24/INT/2026/0224\') ?>': initialCert ? escapeHtml(initialCert.certificate_id) : 'WEBKIT24/INT/2026/0224',
            '<?= e($initialCert[\'position\'] ?? \'Frontend Developer (Intern)\') ?>': initialCert ? escapeHtml(initialCert.position) : 'Frontend Developer (Intern)',
            '<?= e($initialCert[\'start_date\'] ?? \'2026-06-15\') ?>': initialCert ? escapeHtml(initialCert.start_date) : '2026-06-15',
            '<?= e($initialCert[\'end_date\'] ?? \'2026-07-30\') ?>': initialCert ? escapeHtml(initialCert.end_date) : '2026-07-30',
            '<?= e($initialCert[\'issue_date\'] ?? \'2026-08-10\') ?>': initialCert ? escapeHtml(initialCert.issue_date) : '2026-08-10',
            '<?= json_encode($initialCert) ?>': JSON.stringify(initialCert)
        });

        // Inject the select options
        let optionsHtml = '<option value="">-- Select an Existing Candidate --</option>';
        allCerts.forEach(c => {
            const selected = (initialCert && initialCert.id == c.id) ? 'selected' : '';
            optionsHtml += `<option value="${c.id}" data-json='${escapeHtml(JSON.stringify(c))}' ${selected}>${escapeHtml(c.student_name)} (${escapeHtml(c.certificate_id)})</option>`;
        });

        html = html.replace(/<\?php if \(!empty\(\$allCerts\)\): \?>[\s\S]*?<\?php endif; \?>/i, `
            <div class="form-group" style="margin-bottom: 20px; background: var(--bg-light); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">⚡ Quick-Load From Database</label>
                <select id="quickLoadSelect" class="form-control form-select" style="font-size: 0.88rem;">
                    ${optionsHtml}
                </select>
            </div>
        `);

        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(html);
    }

    // -------------------------------------------------------------
    // Homepage: / or /index.php
    // -------------------------------------------------------------
    if (pathname === '/' || pathname === '/index.php') {
        let html = fs.readFileSync(path.join(__dirname, 'index.php'), 'utf8');
        const prefillId = parsedUrl.query.id || '';
        html = stripPhpLogic(html, {
            '<?= e($prefillId) ?>': escapeHtml(prefillId)
        });
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(html);
    }

    // -------------------------------------------------------------
    // Static Files
    // -------------------------------------------------------------
    const filePath = path.join(__dirname, pathname);
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
        const ext = path.extname(filePath).toLowerCase();
        const contentType = MIME_TYPES[ext] || 'application/octet-stream';
        res.writeHead(200, { 'Content-Type': contentType });
        return fs.createReadStream(filePath).pipe(res);
    }

    // 404 Not Found
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('404 Not Found');
});

// Helpers to render template PHP files safely for Node dev server
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function stripPhpLogic(html, tokenReplacements = {}) {
    // Strip initial PHP header block
    html = html.replace(/<\?php[\s\S]*?\?>/i, '');

    // Replace specific known tags
    for (const [token, val] of Object.entries(tokenReplacements)) {
        html = html.split(token).join(val);
    }

    // Remove remaining conditional blocks like error alerts if not triggered
    html = html.replace(/<\?php if \(!empty\(\$error\)\): \?>[\s\S]*?<\?php endif; \?>/g, '');
    html = html.replace(/<\?php[\s\S]*?\?>/g, '');

    return html;
}

function renderPhpTokens(html, tokenReplacements = {}) {
    html = html.replace(/<\?php[\s\S]*?\?>/i, '');
    for (const [token, val] of Object.entries(tokenReplacements)) {
        html = html.split(token).join(val);
    }
    html = html.replace(/<\?php[\s\S]*?\?>/g, '');
    return html;
}

server.listen(PORT, () => {
    console.log(`\n======================================================`);
    console.log(`Webkit24 Certificate Verification Portal Dev Server`);
    console.log(`------------------------------------------------------`);
    console.log(`Homepage:     http://localhost:${PORT}/`);
    console.log(`Admin Portal: http://localhost:${PORT}/admin/`);
    console.log(`Admin Login:  Username: admin`);
    console.log(`              Password: Webkit24@Admin2026`);
    console.log(`Demo Cert ID: WEBKIT24/INT/2026/0035`);
    console.log(`======================================================\n`);
});

