## ITSECWB Milestone Website Group Project

This repository contains the ITSECWB website group project for **Milestones 1 and 2**.
The project is based on a previous work from https://github.com/Yoshiii04/ITDBADM5.

The website presents a range of computer peripherals and accessories designed to accommodate different budgets and user needs. 

It has been simplified to accommodate the scope of a security web development course, focusing on the implementation of secure login and registration systems, authentication mechanisms, and basic user access control.

---

## Milestone 1 patch-fixes before Milestone 2

### **Authentication & Passwords**

* Removed unsafe email-only reset (`change_password.php` → contact admin page).
* Login: `session_regenerate_id(true)` after success; forgot-password alert removed.
* Passwords: Argon2id + optional pepper; legacy hashes auto-upgrade on login.

### **Sessions & Cookies**

* `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS sessions.
* Idle timeout 30 min, max 24h; expired sessions → `login.php`.
* Helpers centralized in `session.php` via `config.php`.

### **Admin & Authorization**

* CSRF tokens on role change and admin password reset (`admin_roleassignment.php`).
* Confirm-password field added for admin reset.

### **Database & Logs**

* `log_audit_action` procedure aligned with `audit_logs`.
* `UNIQUE(email)` on `users`; duplicate email shows same message as duplicate username.

### **Debug & UI**

* `APP_DEBUG` default on local; off for generic browser messages, errors go to `error_log`.
* `SHOW_PHASE3_NAV_LINKS` (default **true** in `session.php`) toggles store/cart nav; set `false` to hide storefront links.

---

## Milestone 2 feature implementation

### **Phase 3A — customer store (products, cart, checkout, order history)**

* Pages: `store.php`, `product.php`, `categoriespage.php`, `cart.php`, `checkout.php`, `orderhistory.php`.
* Cart is **session-based** (no `cart_items` table). Checkout uses a DB **transaction** (order + line items + stock decrement).
* Successful orders write a row to `audit_logs` (`orders` / `order_placed`); failures log `checkout_failed`.
* Store pages load helpers via **`website/includes/store_bootstrap.php`** (wraps `store_common.php`, `csrf.php`, `cart_session.php`, `audit_log.php`).
* Phase 3B admin flows reuse the same DB tables (`products`, `orders`) and `website/includes/audit_log.php`.

### **Phase 3B — admin & staff (catalog + orders)**

* Products: `admin_products.php` — list, add, edit, activate/deactivate (`is_active`); CSRF on POST; `itsec_audit_log` for create/update/activate/deactivate.
* Orders: `admin_dash.php` — stats (pending / completed / total products), recent orders, order status updates (`pending` / `completed` / `cancelled`); CSRF; audit `order_status_changed`.
* Roles: Admin — full sidebar (Dashboard, Products, Audit logs, Role assignment). **Staff** — Dashboard + Products only (order/product ops); Audit & Role assignment hidden. `checkStaffOrAdmin()` in `session.php`; `checkRole('admin')` for sensitive pages.
* Role assignment (`admin_roleassignment.php`): audit `user_role_changed`, `admin_password_reset` on success.
* Sidebar: **Tables** link removed (no backend); `staff_dash.php` not used — header “Staff Panel” → `admin_dash.php`.

### **Phase 4 — logging, errors, admin stub, profile images**

* Logging channels:
  1. `audit_logs` (MySQL) via `itsec_audit_log()` for structured events (orders, catalog, roles, `user_login`).
  2. `app.log` (file) mirrors successful audit events as JSON lines (`website/storage/logs/app.log`, override with `ITSEC_APP_LOG`). Also logs `auth`, `security`, and `exception`.
  3. PHP `error_log` for engine/mysqli errors and uncaught exceptions (also written to `app.log`).
* Auth events: Failed logins → `login_attempts` + `auth/login_failed`; brute-force → `login_blocked`; success → `auth/login_success` + audit `user_login`.
* Admin download: `admin_auditlogs.php` provides “Download application log (app.log)” (streamed file, no path exposure).
* Error handling: `includes/error_bootstrap.php` (loaded via `config.php`). `APP_DEBUG` shows short hint; otherwise generic message (full trace only in logs).
* Admin tables: `admin_tables.php` is a disabled “coming soon” page (no schema editing UI).
* Roles vs sessions: Self role change updates `$_SESSION['role']` immediately; other users keep old role until logout/session expiry.
* Profile images: Re-encoded via GD as JPEG on register/profile (`includes/image_sanitize.php`) to strip polyglots; product images unchanged.

### **Phase 5 — headers, syslog, configuration, TLS**

* Security headers: `includes/security_headers.php` sets CSP (Report-Only), `X-Frame-Options: SAMEORIGIN`, and `Referrer-Policy: strict-origin-when-cross-origin` (loaded from `config.php`).
* Syslog (optional): Enable with `APP_USE_SYSLOG=true`. Sends selected events (exceptions, `checkout_failed`, CSRF failures) via `syslog()`.
* Environment vars: See `.env.example` (`APP_DEBUG`, `APP_PASSWORD_PEPPER`, `APP_USE_SYSLOG`, `ITSEC_APP_LOG`, DB settings). No dotenv loader; set via Apache/XAMPP or `secrets.php`.
* HTTPS: Supports self-signed/mkcert locally. Session cookies use `secure` over HTTPS. Optional HTTP→HTTPS redirect may be disabled for grading.
* XAMPP logs: PHP `error_log` (per `php.ini`, often `C:\xampp\php\logs\php_error_log`) and Apache error log (`C:\xampp\apache\logs\error.log`).

### **Backlog**

* Better UI/UX especially on the user side (order flow, feedback, visuals, accessibility).
* Improvements planned for clearer order confirmation pages, user-friendly cart/checkout experience, and easier navigation.

* Document Phase 3B: admin_products.php, staff vs admin sidebar, order status on admin_dash.php, audit action names.
* `admin_tables.php` is a **disabled stub** (Phase 4); use SQL migrations instead of the old modal/AJAX UI.
* Note log_audit_action vs itsec_audit_log() (procedure optional; PHP is canonical).

* Deployment: APP_PASSWORD_PEPPER, APP_DEBUG, HTTPS + cookie Secure.
* Optional: login success/failure audit rows; refresh session role on profile or periodic check.
* Optional: dedicated staff_dash.php redirect vs shared admin_dash.php (current behavior).

| Requirement | Status |
|-------------|--------|
| SQL-based | Met |
| Text + ≥2 numeric storage/display | Met |
| Regular users ≥3 distinct actions | Met |
| Admin ≥3 admin-only actions | Met (audit log viewer, role assignment, admin password reset; staff limited to dashboard + products) |
| Logging auth + txn + admin **to a log file** | Met — `website/storage/logs/app.log` (JSON lines); auth includes `user_login` / `login_success`, failures, brute-force block; txn + admin mirrored when `itsec_audit_log()` succeeds; download from Audit Logs (`admin_auditlogs.php?download_app_log=1`) |
| Session timeout | Met (`website/session.php`, idle 30m / max 24h) |
| Debug vs generic errors + **stack traces** | Met — uncaught exceptions: full trace in `error_log` + app log (`error_bootstrap.php`); generic message in browser when `APP_DEBUG` is off |
| HTTPS (self-signed acceptable) | Partial in repo — session cookie `Secure` when HTTPS; actual TLS is Apache / reverse-proxy setup (see README Phase 5) |
| Bonus: syslog | Optional — `APP_USE_SYSLOG=true` forwards selected lines (`audit_log.php`: exceptions, `checkout_failed`, CSRF failures) |
| Bonus: CSP / security headers | Met — `website/includes/security_headers.php` (CSP **Report-Only**, `X-Frame-Options`, `Referrer-Policy`) |
| Bonus: public domain + valid SSL | N/A in repo (depends on deployment) |

---

Setup Instructions

1. Clone/Copy Project Files  

- Copy the website folder to your XAMPP htdocs directory.

2. Create Database  

- Start XAMPP Control Panel  
- Start Apache and MySQL services  
- Open phpMyAdmin: http://localhost/phpmyadmin  
- Create a new database matching the name in config.php  
- Import the SQL schema (online_store.sql) to create necessary tables  

3. Database migration (existing installs only)  

- If you already created `users` before Phase 2, run in phpMyAdmin:  
  `ALTER TABLE users ADD UNIQUE KEY email (email);`  
  (Fix duplicate emails first if this errors.)

4. Access the Application  

- Config Sample (run once): http://localhost/itsec/website/config_sample.php  
- Login Page: http://localhost/itsec/website/login.php  
- Admin Dashboard: http://localhost/itsec/website/admin_dash.php  