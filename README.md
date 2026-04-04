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
* `SHOW_PHASE3_NAV_LINKS=false` hides store/order/staff links until Phase 3 pages exist.

--

## Milestone 2 feature implementation (TBD)

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