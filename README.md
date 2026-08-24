# LibraCore – Library Management System

LibraCore is a modern, blue-themed **admin-only Library Management System** for schools and universities. It is built with plain **HTML5, Tailwind CSS (CDN), JavaScript, PHP 8+ and MySQL/MariaDB** – no frameworks.

## Features

- Admin login/logout with hashed passwords (`password_hash` / `password_verify`) and PHP sessions
- Dashboard with live statistics, recent activity panels and quick actions
- Student management: add / view / edit / delete / search with pagination
- Book management: add / view / edit / delete / search + category filter, stock status badges
- Borrowing: issue books (availability-aware), borrow records with search & filters
- Returns: one-click return with overdue detection and automatic stock updates
- Reports: totals, most borrowed books, currently borrowed, overdue list
- Protection against SQL injection (PDO prepared statements), XSS (`htmlspecialchars`), unauthorized page access

## Requirements

- XAMPP / WAMP (PHP 8.x, MySQL or MariaDB, Apache)

## Setup Instructions

### 1. Copy the project

Place the `libracore` folder inside your web root:

```
C:\xampp\htdocs\Library Management System\libracore
```

### 2. Start the servers

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

> Note: on this machine MySQL is configured to run on port **3307**. If your MySQL uses the default port 3306, change `DB_PORT` in `config/database.php`.

### 3. Create the database

Option A – phpMyAdmin:
1. Go to <http://localhost/phpmyadmin>
2. Open the **Import** tab
3. Choose `database/libracore.sql`
4. Press **Import** (the file creates the `libracore` database, all tables and demo data automatically)

Option B – Command line:

```bash
cd "C:\xampp\htdocs\Library Management System\libracore\database"
mysql -u root -P 3307 < libracore.sql
```

(Adjust `-P` to your MySQL port; add `-p` if your root user has a password.)

### 4. Configure the database connection

Edit `config/database.php` if needed:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');   // default XAMPP installs usually use 3306
define('DB_NAME', 'libracore');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Open LibraCore

Visit:

```
http://localhost/Library%20Management%20System/libracore/
```

You will be redirected to the login page.

### 6. Default admin credentials

| Username | Password   |
|----------|------------|
| `admin`  | `admin123` |

## Demo Data Overview

- **1 admin account**
- **10 students** (`STU-1001` … `STU-1010`)
- **10 books** across 8 categories
- **18 borrow records** – 10 still out (4 of them overdue) and 8 returned

## Project Structure

```text
libracore/
├── config/database.php        PDO connection settings
├── auth/                      login.php, logout.php
├── includes/                  auth_check.php, header.php, sidebar.php,
│                              footer.php, functions.php
├── dashboard/index.php        statistics + recent activity
├── students/                  index, add, edit, view, delete
├── books/                     index, add, edit, view, delete
├── borrow/                    index (records), add, return
├── reports/index.php          library reports (printable)
├── assets/js/script.js        UI interactions & client validation
├── database/libracore.sql     schema + sample data
└── index.php                  entry point redirect
```

## Troubleshooting

| Problem | Fix |
|---|---|
| "Could not connect to the database" | Make sure MySQL is running and `config/database.php` matches your port/user/password. |
| Blank styles | Tailwind CSS loads from a CDN – an internet connection is required on first load. |
| Login says invalid credentials | Re-import `database/libracore.sql`, then use `admin` / `admin123`. |
| Cannot delete student/book | The system blocks deletion while copies are still borrowed – process returns first. |
