# ResourceHub &mdash; Equipment Loan &amp; Maintenance Management System

[![Environment](https://img.shields.io/badge/OS-Ubuntu%2026.04%20LTS-E95420?logo=ubuntu&logoColor=white)](#1-environment--system-specifications)
[![PHP](https://img.shields.io/badge/PHP-8.5.12%20(cli%20%7C%20apache2)-777BB4?logo=php&logoColor=white)](#1-environment--system-specifications)
[![Database](https://img.shields.io/badge/PostgreSQL-16%2B%20%7C%2018.6-4169E1?logo=postgresql&logoColor=white)](#1-environment--system-specifications)
[![Web Server](https://img.shields.io/badge/Apache-2.4.69%2B-D22128?logo=apache&logoColor=white)](#step-6-web-server-configuration-apache-24)
[![Frontend](https://img.shields.io/badge/Bootstrap-5.3.3%20Offline-7952B3?logo=bootstrap&logoColor=white)](#1-environment--system-specifications)
[![Acceptance Tests](https://img.shields.io/badge/Acceptance%20Tests-T01--T15%20Passed%20(100%25)-success)](#5-testing--verification)
[![Repository](https://img.shields.io/badge/GitHub-nyeya%2Fresourcehub-181717?logo=github&logoColor=white)](https://github.com/nyeya/resourcehub)

ResourceHub is a local equipment loan and maintenance management web application designed for small organisations to manage shared gear (laptops, cameras, laboratory instruments, projectors, and fieldwork tools). It provides role-based access control (Staff, Coordinator, Administrator), inclusive date overlap prevention, transactional concurrency locking in PostgreSQL, automated damage and maintenance tracking, and 100% offline Bootstrap 5 styling.

---

## Table of Contents

- [1. Environment &amp; System Specifications](#1-environment--system-specifications)
- [2. Directory Architecture](#2-directory-architecture)
- [3. Installation &amp; Local Setup](#3-installation--local-setup)
  - [Step 1: Install System Packages](#step-1-install-system-packages)
  - [Step 2: Clone &amp; Prepare Codebase](#step-2-clone--prepare-codebase)
  - [Step 3: Database &amp; User Provisioning](#step-3-database--user-provisioning)
  - [Step 4: Application Configuration](#step-4-application-configuration)
  - [Step 5: Database Schema Initialization &amp; Seeding](#step-5-database-schema-initialization--seeding)
  - [Step 6: Web Server Configuration (Apache 2.4)](#step-6-web-server-configuration-apache-24)
  - [Alternative: PHP Built-in Server](#alternative-php-built-in-server)
  - [Troubleshooting &amp; Common Pitfalls](#troubleshooting--common-pitfalls)
- [4. Demo Accounts &amp; Credentials](#4-demo-accounts--credentials)
- [5. Testing &amp; Verification](#5-testing--verification)
- [6. Project Documentation Index](#6-project-documentation-index)

---

## 1. Environment &amp; System Specifications

ResourceHub is built and verified on the reference Ubuntu LTS stack with exact specifications:

| Component | Specification | Role in System |
| :--- | :--- | :--- |
| **Operating System** | **Ubuntu 26.04 LTS** (x86_64) | Reference environment / VM |
| **Web Server** | **Apache 2.4.69+** (`apache2`, `libapache2-mod-php`) | DocumentRoot strictly pointed to `public/` |
| **PHP Runtime** | **PHP 8.5.12** (CLI &amp; Apache module) | Extensions: `pdo`, `pdo_pgsql`, `session`, `filter`, `mbstring`, `ctype` |
| **Database Engine** | **PostgreSQL 16+ / 18.6** | Transactional row-level locking &amp; relational storage |
| **Frontend UI** | **Bootstrap 5.3.3 + Icons 1.11.3** | 100% offline in `public/assets/` (no internet/CDN needed) |
| **Network Binding** | `127.0.0.1` (Loopback only) | Local URL: `http://localhost/` (Ports `80`, `5432`) |

### Key Paths &amp; Log Locations
- **Apache Web Root:** `/var/www/html/resourcehub/public`
- **Apache Site Config:** `/etc/apache2/sites-available/resourcehub.conf`
- **Apache Error Log:** `/var/log/apache2/error.log`
- **Application Error Log:** `/var/www/html/resourcehub/logs/error.log` (outside web root)
- **Database Configuration:** `config/database.php` (outside web root, excluded from Git)

---

## 2. Directory Architecture

ResourceHub follows the structured directory architecture separating public entry, business logic, templates, configuration, database migrations, and documentation:

```text
resourcehub/
├── public/                  # Apache DocumentRoot (publicly accessible web root)
│   ├── assets/              # Local offline Bootstrap 5.3.3 CSS, JS, and Icons
│   ├── index.php            # Front Controller & entry router
│   ├── .htaccess            # Apache configuration & clean URL routing
│   └── *.php                # Clean entry delegates to Router
├── src/                     # Core business logic & services
│   ├── Controllers/         # Action controllers (Auth, Dashboard, Equipment, Loans, Maintenance, Reports, Users)
│   ├── Services/            # Service layers (Auth, Equipment, Loan, Maintenance, Report, User)
│   ├── Database.php         # PDO database singleton connection
│   └── Router.php           # Front controller dispatcher
├── templates/               # Server-rendered HTML templates with escaped output
│   ├── layout/              # Header, footer, sidebar navigation, flash messages
│   ├── auth/                # Login template with quick-fill demo roles
│   ├── dashboard/           # Metrics, fleet status cards, and loan summaries
│   ├── equipment/           # Catalogue, search/filter, and asset forms
│   ├── loans/               # Loan requests, pending approvals, and detail views
│   ├── maintenance/         # Maintenance tickets, damage triage, and repairs
│   ├── reports/             # Utilization metrics and safe CSV exports
│   ├── users/               # User administration and account forms
│   └── errors/              # 403 Forbidden and 404 Not Found templates
├── config/                  # Protected configuration (outside public/)
│   ├── config.php           # Session and app configuration
│   ├── database.php         # Local PostgreSQL credentials (gitignored)
│   └── database.example.php # Versioned configuration template
├── database/                # Relational migrations and seeders
│   ├── schema.sql           # PostgreSQL DDL, foreign keys (ON DELETE RESTRICT), indexes
│   ├── seed.php             # Reproducible relative-date demonstration seeder
│   └── reset.php            # 1-command fresh schema rebuild and seed
├── docs/                    # Complete project deliverables and documentation
│   ├── INSTALLATION.md      # Detailed installation guide
│   ├── INSTALLATION_LOG.md  # Step-by-step verified installation log
│   ├── USER_GUIDE.md        # User workflows & success scenario walkthrough
│   ├── DATA_DICTIONARY.md   # Relational schema & data dictionary
│   ├── BACKUP_RESTORE.md    # Backup (pg_dump) & disaster recovery (psql)
│   ├── TEST_REPORT.md       # Acceptance test report (T01 - T15)
│   └── CREDENTIALS.md       # Pre-seeded test accounts and roles
├── logs/                    # Application error logs (writable by www-data)
└── tests/                   # Automated acceptance and verification tests
```

---

## 3. Installation &amp; Local Setup

### Step 1: Install System Packages
```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-mbstring postgresql postgresql-contrib git unzip
sudo systemctl enable --now apache2 postgresql
```

### Step 2: Clone &amp; Prepare Codebase
```bash
cd /var/www/html
sudo git clone https://github.com/nyeya/resourcehub.git
cd resourcehub

# Assign directory ownership to your current user and web group
sudo chown -R $USER:www-data /var/www/html/resourcehub
sudo chmod -R 775 /var/www/html/resourcehub

# Grant write permissions for local application logs
sudo chown -R www-data:www-data logs/
sudo chmod -R 775 logs/
```

### Step 3: Database &amp; User Provisioning
Open PostgreSQL CLI as administrative user:
```bash
sudo -u postgres psql
```

Execute the provisioning script:
```sql
CREATE DATABASE resourcehub WITH ENCODING 'UTF8';
CREATE USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';
GRANT ALL PRIVILEGES ON DATABASE resourcehub TO resourcehub_app;
\c resourcehub
GRANT ALL ON SCHEMA public TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO resourcehub_app;
\q
```

### Step 4: Application Configuration
Copy the configuration template:
```bash
cp config/database.example.php config/database.php
```

Ensure `config/database.php` matches your local PostgreSQL connection parameters:
```php
<?php
return [
    'driver'   => 'pgsql',
    'host'     => '127.0.0.1',
    'port'     => 5432,
    'database' => 'resourcehub',
    'username' => 'resourcehub_app',
    'password' => 'root',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
```

### Step 5: Database Schema Initialization &amp; Seeding
Run the 1-step migration and seeding script:
```bash
php database/reset.php
```
This builds all tables with constraints (`ON DELETE RESTRICT`) and seeds:
- **6 Users** (1 administrator, 1 coordinator, 4 staff)
- **4 Categories** (Computing, Photography, Audio &amp; Video, Lab &amp; Field)
- **3 Storage Locations** (Main IT Store, Media Studio, Engineering Workshop)
- **20 Equipment Assets** (`EQ-1001` through `EQ-1020`)
- **1 Open Maintenance Ticket** (on damaged camera `EQ-1004`)
- **12 Seeded Loans** with full audit trails covering all lifecycle states

### Step 6: Web Server Configuration (Apache 2.4)
Create `/etc/apache2/sites-available/resourcehub.conf`:
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/resourcehub/public

    <Directory /var/www/html/resourcehub/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/resourcehub_error.log
    CustomLog ${APACHE_LOG_DIR}/resourcehub_access.log combined
</VirtualHost>
```

Enable rewrite, activate site, and reload Apache:
```bash
sudo a2enmod rewrite
sudo a2dissite 000-default.conf
sudo a2ensite resourcehub.conf
sudo systemctl restart apache2
```
Navigate to: `http://localhost/`

### Alternative: PHP Built-in Server
For rapid local testing without Apache:
```bash
php -S 127.0.0.1:8000 -t public
```
Navigate to: `http://127.0.0.1:8000/`

### Troubleshooting &amp; Common Pitfalls

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| `HTTP 500: could not find driver` | `php-pgsql` module is disabled or not installed. | Run `sudo apt install php-pgsql` and verify with `php -m \| grep pgsql`. Restart Apache. |
| `HTTP 403 Forbidden` on asset loading | File permissions on `public/assets/` are restrictive. | Run `sudo chmod -R 755 /var/www/html/resourcehub/public`. |
| `HTTP 404 Not Found` on clean paths | Apache `mod_rewrite` is disabled or `AllowOverride All` is omitted. | Run `sudo a2enmod rewrite` and verify `AllowOverride All` in `resourcehub.conf`. |
| `SQLSTATE[08006] Connection refused` | PostgreSQL service is inactive or listening on wrong port. | Run `sudo systemctl status postgresql` and verify port 5432 in `config/database.php`. |
| `Password authentication failed for user resourcehub_app` | Password mismatch in `config/database.php`. | Re-run `ALTER USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';` in `psql`. |
| `cp: cannot create regular file 'config/database.php': Permission denied` | Repository was cloned with `sudo`, leaving directory ownership under `root`. | Run `sudo chown -R $USER:www-data /var/www/html/resourcehub` and `sudo chmod -R 775 /var/www/html/resourcehub`. |

---

## 4. Demo Accounts &amp; Credentials

All seeded test accounts share the password: **`Password123!`**  
*(The login page at `public/login.php` also provides 1-click quick-fill tiles).*

| User Name | Email Address | Role | Testing Scenarios |
| :--- | :--- | :--- | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | User administration, role assignments, account active toggle, last admin protection. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | Equipment CRUD, loan approval/rejection, checkouts, damaged return triage, maintenance tickets. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | Catalogue browsing, requesting loans, viewing own loans. Holds an active overdue loan (`EQ-1001`). |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | Field researcher. Holds an active checkout (`EQ-1002`) and conflicting pending request on `EQ-1007`. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | Lab researcher. Holds approved future loan (`EQ-1003`) and returned damaged camera history (`EQ-1004`). |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | Inactive account verification (sign-in is rejected). |

---

## 5. Testing &amp; Verification

Run the automated test runner verifying all 15 acceptance criteria (T01 - T15):

```bash
php tests/test_acceptance_cases.php
```

### Component Test Scripts
```bash
php tests/verify.php           # Core rules, authentication, pagination, and guard checks
php tests/test_pages.php       # Architectural and URL dispatch verification
php tests/test_refactoring.php # Service models, helpers, and data sanitization
php tests/verify_backup.php    # Database backup and restoration verification
```

---

## 6. Project Documentation Index

All detailed specifications and technical manuals are maintained in the `docs/` directory:

- [Installation &amp; Setup Guide](docs/INSTALLATION.md) &mdash; Detailed installation instructions, prerequisites, and Apache configuration.
- [Environment Installation Log](docs/INSTALLATION_LOG.md) &mdash; Verified installation log of package installations, commands, and parameter verification.
- [User Guide &amp; Workflows](docs/USER_GUIDE.md) &mdash; Role-based workflows (Staff, Coordinator, Admin) and the core success scenario walkthrough.
- [Relational Data Dictionary](docs/DATA_DICTIONARY.md) &mdash; Full database schema, column definitions, data types, constraints, and ER diagram.
- [Backup &amp; Disaster Recovery Guide](docs/BACKUP_RESTORE.md) &mdash; Step-by-step `pg_dump` and `psql` restore procedures.
- [Acceptance Test Report](docs/TEST_REPORT.md) &mdash; Formal test report and execution logs for Acceptance Cases T01 through T15.
- [Demonstration Credentials](docs/CREDENTIALS.md) &mdash; Fictional user accounts and testing responsibilities.
