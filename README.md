# TutorHub — Tuition Management System

A complete, database-driven Tuition Management System built with **PHP 8+, MySQL/MariaDB, PDO, HTML5, CSS3, and vanilla JavaScript**, designed as a university DBMS project.

Every meaningful action in the app (register, post, apply, accept, message, schedule, mark attendance, pay, review, report) performs a real INSERT/UPDATE/DELETE against MySQL — there is no fake front-end-only functionality.

---

## 1. Requirements

- XAMPP (PHP 8.0+, MySQL/MariaDB, Apache) — https://www.apachefriends.org/
- A web browser
- (Optional) phpMyAdmin, included with XAMPP

---

## 2. Installation (XAMPP)

1. **Copy the project folder.**
   Copy the entire `tutorhub` folder into your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\tutorhub        (Windows)
   /Applications/XAMPP/htdocs/tutorhub   (macOS)
   /opt/lampp/htdocs/tutorhub      (Linux)
   ```

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Import the database.**
   - Open `http://localhost/phpmyadmin`
   - Click **Import** in the top menu
   - Choose the file `sql/database.sql` from this project
   - Click **Go**

   This single file will:
   - Create the `tutorhub` database
   - Create all 18 tables with relationships, indexes, and constraints
   - Insert subjects and a small set of realistic demo records

   Alternatively, from a terminal:
   ```bash
   mysql -u root -p < sql/database.sql
   ```

4. **Check your database configuration.**
   Open `config/config.php` and confirm these match your MySQL setup (defaults work for a fresh XAMPP install):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'tutorhub');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', '/tutorhub'); // change if your folder name differs
   ```

5. **Visit the site.**
   Open `http://localhost/tutorhub/index.php` in your browser.

---

## 3. Demo Login Credentials

All demo accounts use the password: **`Passw0rd!`**

| Role    | Email                    |
|---------|--------------------------|
| Student | student@example.com      |
| Tutor   | tutor@example.com        |
| Admin   | admin@example.com        |
| Both    | nadia.both@example.com   |

---

## 4. Project Structure

```
/tutorhub
├── config/            Database connection & app configuration
├── includes/          Shared PHP: header, footer, navbar, sidebars, auth/CSRF/helper functions
├── assets/css/        Theme stylesheet (black / navy / gold)
├── assets/js/         (reserved for future JS enhancements)
├── auth/              Register, login, logout
├── student/           Student dashboard, profile, tuition posts, applications, favorites
├── tutor/             Tutor dashboard, profile, availability, tutor posts, applications
├── admin/             Admin dashboard, user/tutor/post/report/review/payment/subject management
├── messages/          Inbox + conversation thread
├── notifications/     Notification center
├── schedule/          Tuition class scheduling
├── attendance/        Attendance marking & viewing
├── payments/          Monthly payment tracking
├── reviews/           Post-completion review submission
├── reports/           Report/complaint submission
├── sql/database.sql   Full schema + demo data (import via phpMyAdmin)
├── docs/              Team page & documentation
└── index.php          Landing page
```

---

## 5. Database Design

**18 tables:** users, student_profiles, tutor_profiles, subjects, tuition_posts, tutor_posts, applications, conversations, messages, favorites, notifications, reviews, tutor_availability, tuition_schedule, attendance, payments, reports, admin_actions.
(A `tutor_subjects` junction table is also included to model the many-to-many relationship between tutors and subjects.)

Key relationships:
- `users` 1—1 `student_profiles` / `tutor_profiles`
- `tuition_posts` many—1 `subjects`, many—1 `student_profiles`
- `applications` many—1 `tuition_posts`, many—1 `tutor_profiles`
- `conversations` 1—many `messages`
- `tuition_schedule` many—1 `applications`; `attendance` 1—1 `tuition_schedule`
- `payments`, `reviews`, `favorites`, `reports` all reference `tuition_posts` and/or `users`
- `admin_actions` logs every moderation action taken by an admin

All foreign keys use `InnoDB` with explicit `ON DELETE`/`ON UPDATE` rules (mostly `CASCADE`, with `SET NULL` where a soft reference makes sense, e.g. a deleted user's reports).

See `docs/database_relationships.md` for a full relationship diagram description.

---

## 6. Security Features

- PDO prepared statements everywhere (no raw string-concatenated SQL)
- `password_hash()` / `password_verify()` for credentials
- Session-based authentication with role-based authorization (`requireRole()`, `requireAdmin()`)
- CSRF tokens on every state-changing form and action link
- Ownership checks on every edit/delete (a student cannot edit another student's post, etc.)
- Output escaped via `htmlspecialchars()` (the `e()` helper) throughout

---

## 7. Testing Checklist

See `docs/testing_checklist.md` for the full end-to-end manual test script (register → apply → accept → schedule → attendance → payment → review → report → admin moderation).

---

## 8. Notes for Submission

- Replace the placeholder names in `docs/team.php` with your actual team members.
- The SQL file intentionally seeds only 1–2 demo rows per table (not hundreds) so relationships are easy to inspect in phpMyAdmin.
- `BASE_URL` in `config/config.php` must match the folder name you placed under `htdocs` (default assumes `/tutorhub`).
