# FCWM Student Complaint System

A PHP and MySQL complaint portal for students, college administrators, and department support staff. It uses the Bootstrap and icon assets already included in this folder.

## Run with XAMPP

1. Copy this project folder into `C:\xampp\htdocs\` (or use it in place).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Visit `http://localhost/quantam-lite/`.
4. On first request, the application creates the `fcwm_complaints` database, its tables, the initial departments, and an administrator account. XAMPP's default local MySQL settings (`root` with an empty password) are used.

The database settings can be overridden with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables. The MySQL account needs permission to create the configured database and its tables. If MySQL is not available, the app displays the connection error rather than showing a working-looking empty portal.

## First administrator sign-in

- Email: `admin@fcwm.edu.ng`
- Password: `Admin@12345`

Change this password immediately from **Departments & Accounts → Change your administrator password**. Department support accounts are created by the administrator from the same page. Students can register from the sign-in page.

## Complaint workflow

- Students register with full name, email, matriculation number, department, and password.
- A student submits a topic, department, subject, and description. The system assigns a unique reference such as `FCWM-2026-A1B2C3D4E5F6`.
- Students can see their own complaint history or use **Track a Reference** to see a complaint's status and routing information.
- Administrators can view all cases, assign or reassign a department, update status, and create department accounts.
- Department accounts can view and update only cases assigned to their own department.
- Students and support staff can add updates to the complaint conversation after signing in.

## Requirements

- PHP 8.0 or newer with PDO MySQL enabled.
- MySQL or MariaDB (the version bundled with XAMPP is supported).

Database tables are initialized automatically. No SQL import is required.
