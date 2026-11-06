# Webkit24 – Official Certificate Verification Portal

**Subdomain:** `https://certificate.webkit24.com/`  
**Parent Organization:** [Webkit24](https://webkit24.com)  
**Primary Accent:** `#FF4938` (Orange/Coral) | **Dark Navy:** `#080D25` | **Background:** `#F8F9FC` / `#FFFFFF`

A complete, modern, responsive, and secure Certificate Verification Portal built specifically for **Webkit24**. It allows students, employers, universities, and partner organizations to verify the authenticity of internship, training, and experience certificates issued by Webkit24 using unique Certificate IDs and QR codes.

---

## 🌟 Key Features

### 1. Public Verification Portal (`/` or `index.php`)
- **Single-Page Verification Interface**: Sleek hero search bar with auto-capitalization and instant feedback.
- **Dynamic Result States**:
  - **Valid Certificate**: Displays green verification badge, student name, unique certificate ID, certificate type, internship/training role, tenure dates, issue date, issuing authority (*Webkit24*), and official guarantee notice.
  - **Invalid / Not Found**: Elegant warning card with troubleshooting tips and "Try Again" action.
  - **Revoked Certificate**: Distinct revocation badge and warning notice indicating that the certificate has been cancelled by Webkit24.
- **Direct QR Code Scanning**:
  - Scanning a QR code printed on a certificate automatically opens `https://certificate.webkit24.com/?id=WEBKIT24/INT/2026/0035` and validates the certificate instantly without manual typing.
- **Client-Side QR Code Rendering**:
  - Generates crisp offline SVG/Canvas QR codes using the embedded library (`qrcode.min.js`).
- **Share & Print**:
  - "Copy Verification Link" button for easy sharing.
  - "Print / Save Record" button with specialized print CSS media styles.

### 2. Secure Admin Management Panel (`/admin/`)
- **Authentication & Security**:
  - Session-based login with BCrypt password hashing.
  - CSRF token validation on all state-altering requests.
  - Rate limiting on login attempts to mitigate brute-force attacks.
  - Session timeout and fixation protection (`session_regenerate_id`).
- **Real-Time KPI Dashboard**:
  - Total Certificates Issued
  - Active (Valid) Certificates
  - Revoked Certificates
  - Active Certificate Types breakdown
- **Full CRUD Certificate Management**:
  - **Issue New Certificate**: Auto-generates sequential IDs in the format `WEBKIT24/{TYPE}/{YEAR}/{0001}` (e.g. `WEBKIT24/INT/2026/0001`, `WEBKIT24/TRN/2026/0002`, `WEBKIT24/EXP/2026/0003`) while allowing custom manual entry with database uniqueness checks.
  - **Edit Certificate**: Modify any attribute with real-time uniqueness validation.
  - **One-Click Status Toggle**: Instantly activate or revoke certificates.
  - **View Details & QR Code**: Modal preview with full details and printable QR code.
  - **Search & Filter**: Live debounced search across student names, certificate IDs, and positions; filter by status and type.
  - **Delete Certificate**: Modal confirmation with database deletion.

### 3. Integrated PDF Document Studio (`/admin/generator.php`)
- **Suite of 10 Official Webkit24 Documents**:
  - **Internship Documents**:
    1. **Internship Offer Letter (A4 Portrait)**: Official Webkit24 letterhead, registered office address in Lucknow, customized greeting & date, personalized internship role, tenure dates, job type, bulleted responsibilities, HR department signoff, and footer contact strip.
    2. **Internship Joining Letter (A4 Portrait)**: Formal joining confirmation, assigned mentor/lead, work arrangement, and developer conduct guidelines.
    3. **Internship Completion Letter (A4 Portrait)**: Formal completion certificate letter with performance highlights, domain appraisal, and executive commendations.
    4. **Internship Certificate (A4 Landscape)**: Features official Webkit24 logo, double hairline security frame, guilloche watermark, 24-facet Rosette medal seal, calendar date box, dynamic QR verification code with `Verify at: certificate.webkit24.com`, Unique ID box, and authentic executive signatures (*Aditya Singh, CEO* & *Sudhanshu Pandey, Founder*).
    5. **Internship Relieving Letter (A4 Portrait)**: Formal separation confirmation, handover acknowledgment, and no-dues clearance.
  - **Job / Employee Documents**:
    1. **Job Offer Letter (A4 Portrait)**: Employment offer with designation, department, annual CTC compensation, joining date, responsibilities, and candidate acceptance endorsement block.
    2. **Job Appointment Letter (A4 Portrait)**: Formal binding appointment letter covering remuneration, reporting hierarchy, probation terms, notice period, and IP/confidentiality clauses.
    3. **Job Joining Letter (A4 Portrait)**: Official onboarding confirmation and workstation/credential provisioning report.
    4. **Job Relieving Letter (A4 Portrait)**: Formal relieving & experience letter acknowledging service tenure, handover, and no-dues settlement.
    5. **Job Experience Certificate (A4 Landscape)**: Prestigious experience credential matching the authenticated certificate styling with QR verification code and rosette seal.
- **Split-Screen Studio with Live Visual Preview**:
  - Admins fill or modify details on the left, and the preview on the right instantly reflects every keystroke across all 10 document types.
  - **Quick-Load From Database**: Select any intern or employee from the dropdown to automatically prefill their details.
- **One-Click 300 DPI PDF Export**:
  - Powered by embedded `html2pdf.js`, generating crisp vector/canvas PDFs downloaded directly to the device.
  - Orientation is automatically selected: **Landscape** for certificates, **Portrait** for formal letters.

---

## 🎨 Webkit24 Brand Guidelines

| Element | Color Hex | Usage |
| :--- | :--- | :--- |
| **Primary Orange/Coral** | `#FF4938` | Primary CTA buttons, key icons, active navigation indicators, accents |
| **Dark Navy** | `#080D25` | Headings, brand title, footer background, primary typography |
| **Light Background** | `#F8F9FC` | Page body background, table alternating rows, card insets |
| **Pure White** | `#FFFFFF` | Cards, modal dialogs, top navigation bar |
| **Success Emerald** | `#10B981` | Verified badge, active status pill |
| **Revoked Red** | `#EF4444` | Revoked alerts, deletion button |

Official Webkit24 logo assets:
- Primary logo (for light backgrounds, certificates, offer letters, headers): [`assets/images/webkit24-logo.png`](assets/images/webkit24-logo.png)
- Inverted white-text logo (for dark navy footer): [`assets/images/webkit24-logo-white.png`](assets/images/webkit24-logo-white.png)

---

## 📁 Project Structure

```text
certificate-webkit24/
├── assets/
│   ├── css/
│   │   ├── style.css           # Public portal styles (brand colors & responsive layout)
│   │   └── admin.css           # Admin dashboard & login styling
│   ├── js/
│   │   ├── app.js             # Public verification AJAX & QR logic
│   │   ├── admin.js           # Admin CRUD, live search & stats logic
│   │   └── qrcode.min.js      # Zero-dependency QR code generator
│   └── images/
│       ├── webkit24-logo.png  # Official Webkit24 logo (512x512)
│       └── favicon.png        # Brand favicon
├── config/
│   ├── config.php             # Core constants, brand URLs & session configuration
│   └── database.php           # PDO MySQL connection handler (with SQLite fallback)
├── includes/
│   ├── auth.php               # Session security & admin authentication guard
│   ├── csrf.php               # CSRF token generation & validation
│   └── helpers.php            # Sanitization, sequential ID generator, rate limiting
├── admin/
│   ├── index.php              # Admin Dashboard interface
│   ├── login.php              # Admin Sign-In interface
│   ├── logout.php             # Admin Session Destroyer
│   └── api/
│       ├── stats.php          # KPI statistics endpoint
│       ├── list.php           # Filtered & paginated certificate list
│       ├── next-id.php        # Next sequential ID calculator
│       ├── create.php         # New certificate insertion
│       ├── update.php         # Existing certificate update
│       ├── delete.php         # Certificate removal
│       └── toggle-status.php  # Instant Active <-> Revoked status switcher
├── api/
│   └── verify.php             # Public verification endpoint (Rate limited & prepared SQL)
├── index.php                  # Public Single-Page Verification Portal
├── install.php                # Interactive web-based installer & database initializer
├── schema.sql                 # Production MySQL database schema
├── .htaccess                  # Apache security rules, compression & cache controls
├── dev-server.js              # Standalone local development server (Node.js)
└── README.md                  # Documentation
```

---

## 🚀 Deployment Instructions (Shared Hosting / cPanel)

### Step 1: Upload Files
1. Create the subdomain in cPanel or your DNS manager: `certificate.webkit24.com`.
2. Set the document root folder to `public_html/certificate` (or your subdomain directory).
3. Upload all project files to that directory.

### Step 2: Database Setup
1. In cPanel, navigate to **MySQL Databases** and create a new database (e.g. `webkit24_certs`).
2. Create a database user, set a strong password, and assign **All Privileges** to the database.
3. Open **phpMyAdmin**, select `webkit24_certs`, and import `schema.sql`.
   *Alternatively, run the web installer in Step 3.*

### Step 3: Configure Database Credentials
Open [`config/database.php`](config/database.php) and update your database credentials:
```php
private static $db_host = '127.0.0.1';
private static $db_port = '3306';
private static $db_name = 'webkit24_certs';
private static $db_user = 'your_cpanel_db_user';
private static $db_pass = 'your_cpanel_db_password';
```

### Step 4: Web Installer (Optional Alternative)
Visit `https://certificate.webkit24.com/install.php` in your browser.
The wizard tests your server compatibility, initializes all tables, creates your administrator account, and optionally loads the sample demonstration certificate.
*Once installed, the installer automatically locks itself.*

---

## 💻 Local Development & Testing

You can run and test the complete application locally with zero NPM dependencies:

```bash
# Start local development server
node dev-server.js
```

Then visit:
- **Homepage:** [http://localhost:3000/](http://localhost:3000/)
- **Admin Portal:** [http://localhost:3000/admin/](http://localhost:3000/admin/)
  - **Username:** `admin`
  - **Password:** `Webkit24@Admin2026`

### Sample Demonstration Records:
- **Valid Certificate:** `WEBKIT24/INT/2026/0035` (Hariom Pandey – Frontend Developer Intern)
- **Revoked Certificate:** `WEBKIT24/TRN/2026/0012` (Aman Sharma – Python Backend Developer)
- **Direct QR Verification URL:** `http://localhost:3000/?id=WEBKIT24/INT/2026/0035`

---

## 🔒 Security Architecture
1. **Prepared SQL Statements**: 100% of database queries use PDO prepared statements with parameter binding, completely preventing SQL injection.
2. **CSRF Protection**: Every state-altering action in the admin dashboard requires a valid session CSRF token.
3. **Public Rate Limiting**: The public verification endpoint enforces a rate limit (30 requests/minute per IP) to prevent automated scraping or brute-forcing.
4. **XSS Protection**: All dynamic output is escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **Session Hardening**: Sessions enforce `HttpOnly`, `SameSite=Lax`, and `cookie_secure` over HTTPS, preventing session hijacking.
6. **File Access Protection**: The `.htaccess` file blocks direct access to configuration directories, SQL files, and environment files.

---

## 📄 License & Ownership
© 2026 **Webkit24**. All Rights Reserved.  
Main Website: [https://webkit24.com](https://webkit24.com)

