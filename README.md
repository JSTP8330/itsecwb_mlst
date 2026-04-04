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

### **Backlog**

* Better UI/UX especially on the user side (order flow, feedback, visuals, accessibility).
* Improvements planned for clearer order confirmation pages, user-friendly cart/checkout experience, and easier navigation.

* Document Phase 3B: admin_products.php, staff vs admin sidebar, order status on admin_dash.php, audit action names.
* Decide fate of admin_tables.php (implement with allowlists + CSRF, or remove/guard).
* Note log_audit_action vs itsec_audit_log() (procedure optional; PHP is canonical).

* Deployment: APP_PASSWORD_PEPPER, APP_DEBUG, HTTPS + cookie Secure.
* Optional: login success/failure audit rows; refresh session role on profile or periodic check.
* Optional: dedicated staff_dash.php redirect vs shared admin_dash.php (current behavior).

| Requirement | Status |
|-------------|--------|
| SQL-based | Met |
| Text + ≥2 numeric storage/display | Met |
| Users ≥3 actions | Met |
| Admin ≥3 admin-only actions | Met (audit + roles + password reset); clarify staff vs admin in write-ups |
| Logging auth + txn + admin **to a log file** | **Partial** — DB audit + `login_attempts`; file mainly via `error_log` for errors |
| Session timeout | Met |
| Debug vs generic errors + **stack traces** | **Partial** — debug detail exists; **not** full stack traces broadly |
| HTTPS | **Partial** — cookie flags; **prove** TLS in demo/docs |
| Bonus syslog | No |
| Bonus public SSL | N/A in repo |

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