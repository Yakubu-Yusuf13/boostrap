# FCWM Student Complaint & Support Portal

A PHP and MySQL complaint-ticket system for the Federal College of Wildlife Management. It uses the Bootstrap dashboard assets already in `dist/assets` and creates its database and tables automatically on first use.

## Run with XAMPP

1. Place this project in `C:\xampp\htdocs\boostrap` (or another folder under `htdocs`).
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Visit `http://localhost/boostrap/`. The portal creates the `fcwm_support` database and its tables, and seeds the department list.
4. Choose **First-time setup** on the portal landing page to create the first administrator. This one-time setup requires a password of at least 12 characters.
5. Students can create accounts from the landing page. Sign in as the administrator and add department administrators from **Department admins**.

The default XAMPP connection is `127.0.0.1:3306`, database `fcwm_support`, user `root`, and an empty password. The MySQL account must be allowed to create a database and tables. Override the defaults by setting `FCWM_DB_HOST`, `FCWM_DB_PORT`, `FCWM_DB_NAME`, `FCWM_DB_USER`, and `FCWM_DB_PASSWORD` in the PHP/Apache environment before starting Apache.

## Portal areas

- **Students:** create an account, submit a complaint under a selected topic, receive a unique reference number, view their cases, and check public case status and updates with the reference number.
- **Administrators:** review all cases, route a case to a department, create department-admin accounts, and publish status updates for students.
- **Department administrators:** see and update cases routed to their assigned department. They cannot view other departments’ cases or route cases.

Department admins and administrators sign in through the same sign-in page. The initial departments are seeded automatically and include academic, student services, ICT, bursary, and facilities teams.

## Requirements

- PHP 7.4 or newer with PDO MySQL enabled
- MySQL or MariaDB (the default XAMPP setup)
- Apache with PHP enabled

Passwords are stored as PHP password hashes. Forms use CSRF tokens, database writes use prepared statements, and role checks are applied to private pages and case queries.
