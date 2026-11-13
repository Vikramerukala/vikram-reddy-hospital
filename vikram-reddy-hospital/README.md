# Vikram Reddy Hospital Management System

A PHP 8+ / MySQL hospital-management web application designed to run locally with XAMPP. The UI uses Bootstrap 5, Font Awesome and Chart.js. The app uses PHP sessions, password hashing, PDO prepared statements, CSRF tokens, role-based module access and audit events.

> **Important:** This is a functional local-development/demo system, not a certified production electronic medical record or billing platform. Before storing real patient information, arrange a professional security/privacy review, HTTPS, encrypted and tested backups, access controls at patient/record level, a deployment hardening review, and validation of clinical and financial workflows against your hospital's policies. Demo records and sample accounts are included.

## Modules included

- Dashboard and analytics: hospital KPIs, collections (finance roles only), patient registrations, department statistics, today's appointments, low-stock and expiry warnings.
- People and care: patients, doctors, departments, appointments, reception queue, admissions, rooms, beds, prescriptions and prescription items, medical certificates.
- Operations: staff, attendance, doctor schedules, laboratory tests, lab orders and results, medicines, suppliers, pharmacy sales and sale items, ambulance fleet, blood bank inventory and medical equipment.
- Finance: bills, payments and printable invoices / receipts.
- Administration: user accounts, roles, announcements, holidays, feedback, notifications and audit events (written for logins and record create/update/delete/password changes).
- Printable patient information, invoices and prescriptions.
- File uploads for doctor profile photos and laboratory-result attachments. Accepted types are JPEG, PNG, WebP and PDF where applicable; maximum 5 MB per file.

The application has CRUD screens for the configured operational modules. Appointment slots are protected by an application check and a unique database key for pending/confirmed appointments. Pharmacy sale items update stock in a transaction; prescription-required medicines are checked against the linked patient's prescription. Payments update bill balances; admitted beds are marked occupied and released on discharge.

## 1. Requirements

- Windows with XAMPP (Apache, MySQL/MariaDB and PHP 8+).
- PHP extensions enabled: `pdo_mysql`, `fileinfo`, `mbstring` (commonly enabled in XAMPP).
- A modern browser.
- Internet access for CDN-hosted Bootstrap, Font Awesome and Chart.js. The application itself runs locally, but those front-end libraries are loaded from CDNs.

## 2. Copy the project into XAMPP

1. Extract the `vikram-reddy-hospital` folder into `C:\xampp\htdocs\`.
2. Confirm the folder is exactly `C:\xampp\htdocs\vikram-reddy-hospital\`.
3. Open the XAMPP Control Panel.
4. Start **Apache** and **MySQL**.
5. Keep the project folder name as-is. If you choose a different folder name, edit `BASE_URL` in `config/app.php` to match the URL path (for example, `/my-hospital`).

## 3. Create the database

1. Open `http://localhost/phpmyadmin/`.
2. Select **Import**.
3. Choose `database/hospital_management.sql` from this project.
4. Click **Import** / **Go**.
5. Wait for phpMyAdmin to show a successful import. The SQL creates and selects the `hospital_management` database and creates all tables plus demonstration records.

Import the SQL into a **new, empty database**. The script does not delete existing tables. If a previous partial import exists, back it up and remove that empty test database from phpMyAdmin before importing again.

## 4. Check the database connection

Open `config/database.php`. The XAMPP defaults are:

```php
$host = '127.0.0.1';
$db   = 'hospital_management';
$user = 'root';
$pass = '';
```

These are suitable for a default local XAMPP installation. If you have set a MySQL root password, update `$pass`. Environment variables `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS` can also override the defaults.

If the project folder name is different, update `BASE_URL` in `config/app.php`, or set `APP_BASE_URL` in your PHP/Apache environment.

## 5. Patient-facing home page and separate portals

The website root (`http://localhost/vikram-reddy-hospital/`) now opens a public white-and-blue hospital home page. It offers three separate actions:

- **Patient Registration** — a patient creates a patient record and their own patient login account.
- **Patient Login** — patients sign in separately and enter `patient_portal.php`, where they can view their own profile, appointments, prescriptions and billing summaries.
- **Admin / Staff Login** — staff continue to use the existing `login.php` and role-based hospital dashboard.

### One-time database upgrade required for patient accounts

After importing `database/hospital_management.sql`, open phpMyAdmin, select the SQL tab (or Import tab), and run `database/patient_portal_upgrade.sql` **once**. This adds the `patient` role, links patient accounts to patient records, and creates password-reset token storage. Do not run the upgrade more than once because it adds a column and constraint.

New patient registrations automatically create a linked patient record and login account. To protect existing medical records, a public sign-up cannot claim an already-existing patient record based only on an email address; those patients must contact reception to verify identity and arrange portal activation.

### Forgot Password

The Patient Login page includes **Forgot password?**. It creates a random, expiring, one-use reset token and sends the link using PHP `mail()`. A fresh XAMPP install usually does not have outbound email configured. Configure the PHP mail/SMTP settings in `php.ini` / `sendmail.ini` (or configure a trusted mail service) before relying on email password resets. Until mail is configured, the page tells the patient to contact reception; it does not reveal reset tokens in the browser.

## 6. Open the website

Visit the public home page:

`http://localhost/vikram-reddy-hospital/`

Use **Patient Registration** to create a new patient account, **Patient Login** for an existing patient account, or **Admin / Staff Login** for the hospital team.

Sign in with the demo administrator account:

| Field | Value |
|---|---|
| Email | `admin@vikramreddyhospital.in` |
| Password | `Admin@12345` |

**Change the demo password immediately:** open the user menu at the top right, choose **My profile**, and use **Change password**. All seeded demo accounts use the same demo password listed below.

### Other demo accounts

| Role | Email | Password |
|---|---|---|
| Super Admin | `superadmin@vikramreddyhospital.in` | `Admin@12345` |
| Doctor | `ravi@vikramreddyhospital.in` | `Admin@12345` |
| Receptionist | `reception@vikramreddyhospital.in` | `Admin@12345` |
| Pharmacist | `pharmacy@vikramreddyhospital.in` | `Admin@12345` |
| Laboratory Technician | `lab@vikramreddyhospital.in` | `Admin@12345` |
| Accountant | `accounts@vikramreddyhospital.in` | `Admin@12345` |
| Nurse | `nurse@vikramreddyhospital.in` | `Admin@12345` |

The demo administrator can create, disable and assign roles to user accounts from **Administration → User Accounts**. Do not use shared demo credentials after setup.

## 7. Basic testing checklist

Use the demo data for a first pass, then test with newly created records.

1. **Authentication:** sign in, sign out, sign in with an incorrect password, and change the password from My profile.
2. **Role access:** sign in as Receptionist, Pharmacist, Laboratory Technician, Accountant and Nurse. Confirm each sees only the modules granted to that role. Sign in as Hospital Admin to access all modules.
3. **Patients:** create a patient, search by name or phone, edit the record and print patient details.
4. **Doctors / departments:** add a department, add a doctor assigned to it, edit the doctor and verify the department selector.
5. **Appointments:** create a pending or confirmed appointment. Attempt another pending/confirmed appointment for the same doctor, date and time; the system should reject the duplicate. Cancel the first appointment and try again.
6. **Reception:** add a check-in entry and update its queue status.
7. **Pharmacy:** add a medicine and supplier, create a pharmacy sale, then add sale items. Confirm stock decreases and the sale total updates. Try selling more than the available stock. Try a prescription-required medicine without a matching patient prescription; it should be rejected.
8. **Billing:** create a bill, add one or more payments, and confirm paid amount, balance and status update. Print the invoice.
9. **Laboratory:** add a test, create a lab order, add a result and upload a PDF/image report. Confirm the order becomes Completed.
10. **Admissions:** add a room and bed, admit a patient to the bed and confirm it becomes Occupied. Discharge the patient and confirm it becomes Available.
11. **Staff and operations:** add staff, attendance, schedules, ambulance, blood-bank inventory, equipment, holiday, announcement and feedback records.
12. **Reports:** open Reports & Analytics, compare patient / appointment statistics, and confirm finance totals are shown only to Super Admin, Hospital Admin and Accountant.
13. **CRUD and audit:** create, edit and delete an unlinked test record. Confirm a delete is denied for non-admin roles. The SQL `audit_logs` table should record authentication and record changes.

## 8. Project structure

```text
vikram-reddy-hospital/
├── actions.php                 # Validated create / update / delete handler
├── index.php                   # Authenticated application router
├── login.php                   # Admin / staff secure login
├── home.php                    # Public white-and-blue landing page
├── patient_register.php        # Patient registration and account creation
├── patient_login.php           # Separate patient sign-in
├── patient_portal.php          # Patient-only portal
├── patient_logout.php          # Patient sign-out
├── forgot_password.php         # Patient password reset request
├── reset_password.php          # One-time password reset
├── logout.php                  # CSRF-protected sign out
├── config/
│   ├── app.php                 # App name, URL base and session setup
│   ├── database.php            # PDO MySQL connection
│   └── modules.php             # Module definitions and CRUD field rules
├── database/
│   ├── hospital_management.sql # Database schema and demo data
│   └── patient_portal_upgrade.sql # One-time patient portal database upgrade
├── includes/
│   ├── functions.php           # Auth, CSRF, permissions, helpers and audit
│   ├── header.php              # Sidebar and top navigation
│   └── footer.php              # JavaScript libraries and layout footer
├── pages/
│   ├── dashboard.php
│   ├── module.php              # Shared CRUD screen for configured modules
│   ├── profile.php
│   ├── print.php
│   └── reports.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── uploads/
    ├── profiles/
    └── lab-reports/
```

## 9. Security and deployment notes

- Passwords are stored using `password_hash()` and checked using `password_verify()`.
- Database access uses PDO prepared statements; module/table/field identifiers come from server-side allowlisted configuration.
- Important forms use session-based CSRF tokens. Login rotates the session ID; session cookies use HttpOnly and SameSite=Lax.
- Access to modules is role-based; delete actions require Super Admin or Hospital Admin.
- Input values are escaped in HTML output. File uploads are MIME-checked, renamed randomly and limited to 5 MB.
- Do not store card numbers, CVVs or payment credentials. Only a payment method and optional transaction reference are stored.
- The provided `.htaccess` files block direct access to configuration, SQL and include files and prevent PHP execution from upload folders. Apache must allow `.htaccess` overrides for these protections to apply.
- The role system is module-level, not a full per-patient / per-field permission engine. For real hospital use, add patient-assignment scoping, finer-grained access to diagnoses and reports, multi-factor authentication, password reset workflow, rate limiting, HTTPS, encryption at rest, audit review, off-site backups and tested disaster recovery.
- SMS / email gateways, payment gateway processing, backup/restore UI and external hospital/insurance integrations are not connected in this local build. Notification records can be maintained in the system, but no message is sent to an external provider.
- Keep the application on a trusted development machine until a professional security review is complete. Do not expose the XAMPP development server directly to the public internet.

## 10. Troubleshooting

**Database connection failed**
- Check MySQL is running in XAMPP.
- Check the database name is `hospital_management`.
- Update MySQL username/password in `config/database.php` if needed.

**404 / CSS does not load**
- Confirm the folder is `htdocs/vikram-reddy-hospital`.
- Confirm `BASE_URL` in `config/app.php` is `/vikram-reddy-hospital`.

**File uploads fail**
- Check that PHP has `fileinfo` enabled.
- Check `upload_max_filesize` is at least `6M` and `post_max_size` at least `8M` in `php.ini`, then restart Apache.
- Ensure the `uploads` directory is writable by Apache.

**Charts do not appear**
- The dashboard uses Chart.js from a CDN. Check internet access and that the browser is not blocking the CDN.

## 11. Before using real data

Delete the demonstration patient/user/medicine records only after reviewing foreign-key relationships and backup needs. Create named accounts for each staff member, assign the minimum required role, change every demo password, configure HTTPS and backups, and have qualified hospital IT/security and clinical-finance staff review the application. This code is a starting application and does not by itself establish compliance with any healthcare privacy or medical-device standard.
