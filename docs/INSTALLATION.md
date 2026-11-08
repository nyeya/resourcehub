# ResourceHub &mdash; Installation &amp; Setup Guide

ResourceHub is a local equipment loan and maintenance management system built with beginner-friendly traditional PHP, PDO, PostgreSQL, and Bootstrap 5.

---

## 1. Prerequisites

- **Web Server:** Apache HTTP Server 2.4+ (DocumentRoot configured to `public/` directory)
- **PHP:** PHP 8.1+ with extensions:
  - `pdo`
  - `pdo_pgsql`
  - `session`
  - `filter`
- **Database:** PostgreSQL 14+ running locally (e.g., on `127.0.0.1:5432`)
- **Web Browser:** Modern standards-compliant browser with JavaScript enabled

---

## 2. Directory Architecture (Section 8 Standard)

| Location | Purpose (Section 8 Standard) |
| :--- | :--- |
| `public/` | Apache document root; `index.php` front controller and locally stored Bootstrap assets. |
| `src/` | Controllers, database queries, permission checks, and workflow services. |
| `templates/` | Shared layout, forms, and view templates with escaped output. |
| `config/` | Tracked example configuration; ignored local credentials outside `public/`. |
| `database/` | Ordered migrations and separate reproducible sample data script. |
| `tests/` & `docs/` | Focused checks, test evidence, installation guide, and user guide. |

```text
resource-hub/
├── public/                  # Apache DocumentRoot (publicly accessible web root)
│   ├── assets/              # Local Bootstrap 5 CSS, JS, and Icons
│   ├── index.php            # Front Controller & entry router
│   ├── .htaccess            # Prevent directory listing
│   └── *.php                # Clean entry delegates to Router
├── src/                     # Controllers, queries, permissions & services
│   ├── Controllers/         # Application action controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EquipmentController.php
│   │   ├── LoanController.php
│   │   ├── MaintenanceController.php
│   │   ├── ReportController.php
│   │   └── UserController.php
│   ├── Auth.php             # Login, session, and role checks
│   ├── Database.php         # PDO database connection
│   ├── Equipment.php        # Inventory queries & management
│   ├── Loan.php             # Reservations, checkouts, and returns
│   ├── Maintenance.php      # Repair tickets and resolution
│   ├── Report.php           # Analytics & CSV exports
│   ├── Router.php           # Front controller dispatcher
│   └── User.php             # Account management & guards
├── templates/               # Shared layout, forms & view templates
│   ├── layout/              # Shared header, footer & alert templates
│   ├── auth/                # Login template
│   ├── dashboard/           # Overview template
│   ├── equipment/           # Catalogue, item view, and form templates
│   ├── loans/               # Loan lists, request, and view templates
│   ├── maintenance/         # Ticket list and add form templates
│   ├── reports/             # Summary and distribution chart templates
│   ├── users/               # User list and user form templates
│   └── errors/              # 403 Access Denied & 404 Not Found templates
├── config/                  # Database and application configuration
│   ├── config.php
│   ├── database.php
│   └── database.example.php
├── database/                # Relational schema and seed data
│   ├── schema.sql
│   ├── seed.php
│   └── reset.php            # 1-command fresh reset script
├── docs/                    # Documentation and credentials
├── includes/                # Shared bootstrap and initialization
│   ├── init.php             # Autoloader & service initializer
│   └── functions.php        # Helper functions (escaping, flash, CSRF, view)
├── logs/                    # Application error logs
└── tests/                   # Verification test scripts
```

---

## 3. Database Configuration

1. Create a PostgreSQL database and user (if not already existing):
   ```sql
   CREATE DATABASE resourcehub;
   CREATE USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';
   GRANT ALL PRIVILEGES ON DATABASE resourcehub TO resourcehub_app;
   ```

2. Review or update credentials in `config/database.php`:
   ```php
   return [
       'driver'   => 'pgsql',
       'host'     => '127.0.0.1',
       'port'     => 5432,
       'database' => 'resourcehub',
       'username' => 'resourcehub_app',
       'password' => 'root',
   ];
   ```

---

## 4. Database Initialization &amp; Seeding

To create all database tables and populate the demonstration dataset:

```bash
php database/reset.php
```

---

## 5. Running the Application

### Option A: Using Local Apache
Set your Apache `DocumentRoot` in `C:\Apache24\conf\httpd.conf` (or inside a `<VirtualHost>` block):
```apache
DocumentRoot "C:/Apache24/htdocs/resource-hub/public"
<Directory "C:/Apache24/htdocs/resource-hub/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```
Then restart Apache and navigate to: `http://localhost/`

### Option B: Using PHP Built-in Development Server
From the project root:
```bash
php -S 127.0.0.1:8000 -t public
```
Then visit: `http://127.0.0.1:8000` in your web browser.
