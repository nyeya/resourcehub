# ResourceHub &mdash; Equipment Loan &amp; Maintenance Management System

[![Environment](https://img.shields.io/badge/OS-Ubuntu%2026.04%20LTS-E95420?logo=ubuntu&logoColor=white)](#2-environment--system-specifications)
[![PHP](https://img.shields.io/badge/PHP-8.5.12%20(cli%20%7C%20apache2)-777BB4?logo=php&logoColor=white)](#2-environment--system-specifications)
[![Database](https://img.shields.io/badge/PostgreSQL-16%2B%20%7C%2018.6-4169E1?logo=postgresql&logoColor=white)](#2-environment--system-specifications)
[![Web Server](https://img.shields.io/badge/Apache-2.4.69%2B-D22128?logo=apache&logoColor=white)](#step-5-web-server-configuration)
[![Frontend](https://img.shields.io/badge/Bootstrap-5.3.3%20Offline-7952B3?logo=bootstrap&logoColor=white)](#1-product-overview--scope)
[![Acceptance Tests](https://img.shields.io/badge/Acceptance%20Tests-T01--T15%20Passed%20(100%25)-success)](#10-acceptance-test-report-t01--t15)
[![Repository](https://img.shields.io/badge/GitHub-nyeya%2Fresourcehub-181717?logo=github&logoColor=white)](https://github.com/nyeya/resourcehub)](#10-acceptance-test-report-t01--t15)

ResourceHub is a local equipment loan and maintenance management web application designed for small organisations (such as academic departments, media studios, field research teams, and non-profits) to manage shared physical gear &mdash; including laptops, cameras, laboratory instruments, projectors, and fieldwork tools.

The application enforces strict role-based access control (RBAC), multi-day inclusive date overlap checking, row-level transactional concurrency locking in PostgreSQL, immutable audit logging, and defensive input/output sanitization with zero external internet or CDN dependencies.

---

## Table of Contents

- [1. Product Overview &amp; Scope](#1-product-overview--scope)
- [2. Environment &amp; System Specifications](#2-environment--system-specifications)
- [3. Installation &amp; Local Setup](#3-installation--local-setup)
  - [Step 1: Install System Packages (Ubuntu 26.04 LTS)](#step-1-install-system-packages-ubuntu-2604-lts)
  - [Step 2: Clone &amp; Prepare Codebase](#step-2-clone--prepare-codebase)
  - [Step 3: Database &amp; User Provisioning (PostgreSQL)](#step-3-database--user-provisioning-postgresql)
  - [Step 4: Application Configuration](#step-4-application-configuration)
  - [Step 5: Database Schema Migration &amp; Seeding](#step-5-database-schema-migration--seeding)
  - [Step 6: Web Server Configuration (Apache 2.4)](#step-6-web-server-configuration-apache-24)
  - [Alternative: PHP Built-in Server for Rapid Testing](#alternative-php-built-in-server-for-rapid-testing)
  - [Day 2 Installation Acceptance Gate](#day-2-installation-acceptance-gate)
  - [Troubleshooting &amp; Common Pitfalls](#troubleshooting--common-pitfalls)
- [4. Demo Accounts &amp; Credentials](#4-demo-accounts--credentials)
- [5. System Architecture &amp; Implementation Standards](#5-system-architecture--implementation-standards)
  - [Section 8 Directory Standard](#section-8-directory-standard)
  - [Annotated File Tree](#annotated-file-tree)
  - [Dynamic Convention-over-Configuration Routing](#dynamic-convention-over-configuration-routing)
  - [Non-Negotiable Coding Standards](#non-negotiable-coding-standards)
- [6. User Workflows by Role](#6-user-workflows-by-role)
  - [6.1. Staff Workflow (Borrower Journey)](#61-staff-workflow-borrower-journey)
  - [6.2. Coordinator Workflow (Inventory &amp; Operations Journey)](#62-coordinator-workflow-inventory--operations-journey)
  - [6.3. Administrator Workflow (Governance Journey)](#63-administrator-workflow-governance-journey)
- [7. Relational Schema &amp; Data Dictionary](#7-relational-schema--data-dictionary)
  - [Entity-Relationship Diagram](#entity-relationship-diagram)
  - [Detailed Table Specifications](#detailed-table-specifications)
- [8. Business Rules, Concurrency &amp; Defensive Hardening](#8-business-rules-concurrency--defensive-hardening)
  - [Loan Lifecycle State Machine](#loan-lifecycle-state-machine)
  - [Inclusive Calendar Boundaries &amp; Overlap Detection](#inclusive-calendar-boundaries--overlap-detection)
  - [PostgreSQL Concurrency Control (`SELECT ... FOR UPDATE`)](#postgresql-concurrency-control-select--for-update)
  - [Overdue &amp; Maintenance Blocking Logic](#overdue--maintenance-blocking-logic)
  - [Defensive Security Protections](#defensive-security-protections)
- [9. Database Backup &amp; Disaster Recovery](#9-database-backup--disaster-recovery)
  - [Creating Backups (`pg_dump`)](#creating-backups-pg_dump)
  - [Restoring from Backup (`psql`)](#restoring-from-backup-psql)
  - [Automated Restoration Verification](#automated-restoration-verification)
- [10. Acceptance Test Report (T01 &mdash; T15)](#10-acceptance-test-report-t01--t15)
  - [Acceptance Matrix](#acceptance-matrix)
  - [Automated Test Execution Evidence](#automated-test-execution-evidence)
- [11. Extensions &amp; Known Limitations](#11-extensions--known-limitations)

---

## 1. Product Overview &amp; Scope

### Primary Purpose
Enable shared resource tracking across three authenticated organisational roles:
- **Staff Borrowers:** Search catalogue, verify real-time availability, submit date-bounded loan requests, track status, and cancel pending reservations.
- **Equipment Coordinators:** Manage equipment catalogue, approve or decline loan requests with mandatory rationale, execute physical checkout handovers, receive returns, triage defects, and manage maintenance tickets.
- **Administrators:** Provision and manage user accounts, assign roles, deactivate users, and enforce institutional access guards.

### Core Architecture Principles
- **Small, Framework-Free Architecture:** Server-rendered PHP with PDO and a lightweight controller, service, and template layout separation. Avoids bulky third-party ORMs or heavy build pipelines.
- **Zero External Runtime Dependencies:** Bootstrap 5.3.3 and Bootstrap Icons 1.11.3 are served completely offline from `public/assets/`.
- **Relational Data Integrity:** Foreign keys use `ON DELETE RESTRICT` to ensure audit events and historical records cannot be orphaned or deleted.
- **Complete Audit Trail:** Every status change writes an immutable timestamped event to `loan_history`.

---

## 2. Environment &amp; System Specifications

ResourceHub is built and verified on the reference Ubuntu LTS stack with exact package versions documented below:

| Component | Exact Specification | Role in System |
| :--- | :--- | :--- |
| **Operating System** | **Ubuntu 26.04 LTS** (x86_64) | Host reference environment / VM |
| **Web Server** | **Apache 2.4.69+** (`apache2`, `libapache2-mod-php`) | DocumentRoot pointed strictly to `public/` |
| **PHP Runtime** | **PHP 8.5.12** (CLI &amp; Apache module) | Native server-side script execution |
| **Database Engine** | **PostgreSQL 16+ / 18.6** | Transactional row-level locking &amp; relational storage |
| **Frontend UI** | **Bootstrap 5.3.3 + Icons 1.11.3** | 100% offline responsive UI in `public/assets/` |
| **Configured Timezone**| **UTC** | Standardized in `config/config.php` and PostgreSQL |
| **Network Binding** | `127.0.0.1` (Loopback only) | Internal ports `80` (Apache) and `5432` (PostgreSQL) |

### Required PHP Extensions
- `pdo` &mdash; PHP Data Objects database abstraction core.
- `pdo_pgsql` &mdash; PostgreSQL PDO driver.
- `session` &mdash; State management with `HttpOnly` and `SameSite` cookies.
- `filter` &mdash; Server-side input sanitization.
- `mbstring` &mdash; Multi-byte UTF-8 character string operations.
- `ctype` &mdash; Character type checking.

### Local Paths &amp; Log Locations (Ubuntu Standard)
- **Apache Web Root:** `/var/www/html/resourcehub/public`
- **Apache Site Config:** `/etc/apache2/sites-available/resourcehub.conf`
- **Apache Error Log:** `/var/log/apache2/error.log`
- **Application Error Log:** `/var/www/html/resourcehub/logs/error.log` (outside web root)
- **PHP Config (Apache):** `/etc/php/8.5/apache2/php.ini`
- **PHP Config (CLI):** `/etc/php/8.5/cli/php.ini`
- **Database Config:** `config/database.php` (excluded from Git)

---

## 3. Installation &amp; Local Setup

Follow these commands sequentially to prepare the environment, provision the database, seed test data, and launch the application on an Ubuntu system.

### Step 1: Install System Packages (Ubuntu 26.04 LTS)

Update repositories and install the required web stack packages:

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-mbstring postgresql postgresql-contrib git unzip
sudo systemctl enable --now apache2 postgresql
```

Verify service states and installed versions:

```bash
php -v
php -m | grep -E "pdo|pgsql|mbstring"
psql --version
sudo apache2ctl configtest
```

---

### Step 2: Clone &amp; Prepare Codebase

Navigate to the web server directory and clone the repository:

```bash
cd /var/www/html
sudo git clone https://github.com/nyeya/resourcehub.git
cd resourcehub

# Grant write permissions for local application logs
sudo chown -R www-data:www-data logs/
sudo chmod -R 775 logs/
```

---

### Step 3: Database &amp; User Provisioning (PostgreSQL)

Connect to PostgreSQL via the administrative CLI and create the dedicated application database and restricted application user:

```bash
sudo -u postgres psql
```

Execute the provisioning SQL script:

```sql
-- 1. Create the dedicated database
CREATE DATABASE resourcehub WITH ENCODING 'UTF8';

-- 2. Create the application role with a secure password
CREATE USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';

-- 3. Grant connection and database privileges
GRANT ALL PRIVILEGES ON DATABASE resourcehub TO resourcehub_app;

-- 4. Grant schema permissions for PostgreSQL 15+ / 16+
\c resourcehub
GRANT ALL ON SCHEMA public TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO resourcehub_app;

-- 5. Exit psql
\q
```

---

### Step 4: Application Configuration

Copy the tracked example configuration to the local credentials file:

```bash
cp config/database.example.php config/database.php
```

Ensure `config/database.php` contains the local database connection parameters:

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

> **Security Note:** `config/database.php` is ignored by `.gitignore` to prevent credentials from ever entering version control.

---

### Step 5: Database Schema Migration &amp; Seeding

Execute the automated database initialisation script from the project root:

```bash
php database/reset.php
```

#### What this script performs:
1. Applies `database/schema.sql` &mdash; builds all tables, foreign keys with `ON DELETE RESTRICT`, indexes, and constraints.
2. Executes `database/seed.php` &mdash; populates the dynamic demonstration dataset:
   - **6 Users:** 1 administrator, 1 coordinator, 4 staff (including 1 inactive account for testing).
   - **4 Categories:** Computing, Photography, Audio &amp; Video, Lab &amp; Field.
   - **3 Storage Locations:** Main IT Store, Media Studio, Engineering Workshop.
   - **20 Physical Assets:** Full inventory with tags `EQ-1001` through `EQ-1020`.
   - **1 Open Maintenance Ticket:** On damaged camera `EQ-1004`.
   - **12+ Seeded Loans:** Covering `pending`, `approved`, `checked_out`, `returned`, `rejected`, `cancelled`, overdue loans, and conflicting overlap dates relative to execution time.

#### Expected Output:
```text
===========================================
  ResourceHub Database Initializer / Reset 
===========================================
1. Applying schema.sql...
 -> Schema applied successfully.
2. Seeding sample dataset...
 -> 6 users seeded (password for all: Password123!)
 -> 4 categories seeded
 -> 3 locations seeded
 -> 20 equipment assets seeded
 -> 1 open maintenance record seeded
 -> 12 loans with full audit history seeded
Seeding completed successfully!
===========================================
  Database setup and seeding completed!   
===========================================
```

---

### Step 6: Web Server Configuration (Apache 2.4)

Configure Apache to bind strictly to the local loopback and point its `DocumentRoot` exclusively to `public/`:

1. Create a new VirtualHost configuration file:
   ```bash
   sudo nano /etc/apache2/sites-available/resourcehub.conf
   ```

2. Add the VirtualHost configuration:
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

3. Enable `mod_rewrite`, enable the site, and restart Apache:
   ```bash
   sudo a2enmod rewrite
   sudo a2dissite 000-default.conf
   sudo a2ensite resourcehub.conf
   sudo apache2ctl configtest
   sudo systemctl restart apache2
   ```

4. Navigate to `http://localhost/` in your web browser.

---

### Alternative: PHP Built-in Server for Rapid Testing

For standalone testing without Apache, run PHP's built-in server directly from the project root:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/` in your browser.

---

### Day 2 Installation Acceptance Gate

Verify that your local environment satisfies all criteria of the project's Day 2 Acceptance Gate:

- [x] **Local PHP execution:** Browser loads the local PHP application at `http://localhost/`.
- [x] **PostgreSQL PDO Driver:** PHP PDO successfully lists `pgsql` among installed drivers.
- [x] **Database Connectivity:** `SELECT current_database()` returns `resourcehub`.
- [x] **Offline Styling:** Bootstrap 5.3.3 and Icons load cleanly with the host disconnected from the internet.
- [x] **Service Recovery:** Both `apache2` and `postgresql` recover and start automatically after a machine reboot (`sudo reboot`).
- [x] **Clean Security:** No `phpinfo()` or database passwords are committed to Git.

---

### Troubleshooting &amp; Common Pitfalls

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| `HTTP 500: could not find driver` | `php-pgsql` module is disabled or not installed. | Run `sudo apt install php-pgsql` and verify with `php -m \| grep pgsql`. Restart Apache. |
| `HTTP 403 Forbidden` on asset loading | File permissions on `public/assets/` are restrictive. | Run `sudo chmod -R 755 /var/www/html/resourcehub/public`. |
| `HTTP 404 Not Found` on clean paths (e.g. `/loans/request`) | Apache `mod_rewrite` is disabled or `AllowOverride All` is omitted. | Execute `sudo a2enmod rewrite` and ensure `AllowOverride All` is inside `/etc/apache2/sites-available/resourcehub.conf`. |
| `SQLSTATE[08006] Connection refused` | PostgreSQL service is inactive or listening on wrong port. | Run `sudo systemctl status postgresql` and verify port 5432 in `config/database.php`. |
| `Password authentication failed for user resourcehub_app` | Password mismatch in `config/database.php`. | Re-run `ALTER USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';` in `psql`. Avoid solving errors by granting broad permissions. |

---

## 4. Demo Accounts &amp; Credentials

All pre-seeded test accounts share a single fictional password:  
**`Password123!`**

| User Name | Email Address | Role | Status | Testing Purpose |
| :--- | :--- | :--- | :---: | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | **Active** | Full user management, role assignments, password resets, account deactivation, and last active admin protection. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | **Active** | Equipment CRUD, loan approval/rejection, physical checkout &amp; return, maintenance ticket management. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | **Active** | Catalogue browsing, requesting loans, viewing own loans. Holds an active overdue loan on Dell Latitude (`EQ-1001`). |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | **Active** | Field researcher. Holds an active checkout (`EQ-1002`) and a conflicting pending request on `EQ-1007`. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | **Active** | Lab researcher. Holds an approved future loan (`EQ-1003`) and returned damaged camera history (`EQ-1004`). |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | **Inactive** | Verifies that deactivated accounts are blocked at authentication. |

### 1-Click Quick-Fill Authentication
The sign-in interface (`public/login.php`) provides **One-Click Demo Role Tiles** (Administrator, Coordinator, Staff) to immediately populate credentials for swift demonstration and testing without manual typing.

---

## 5. System Architecture &amp; Implementation Standards

### Section 8 Directory Standard

ResourceHub enforces strict separation between public entry points, private business services, shared templates, configuration, migrations, logs, and verification suites:

| Location | Purpose (Section 8 Standard) |
| :--- | :--- |
| `public/` | Apache document root; `index.php` front controller and locally stored Bootstrap assets. |
| `src/` | Controllers, database queries, permission checks, and workflow services. |
| `templates/` | Shared layout, forms, and view templates with escaped output. |
| `config/` | Tracked example configuration; ignored local credentials outside `public/`. |
| `database/` | Ordered migrations and separate reproducible sample data script. |
| `tests/` &amp; `docs/` | Focused checks, test evidence, installation guide, and user guide. |

---

### Annotated File Tree

```text
resourcehub/
├── public/                  # Public web root (Apache DocumentRoot)
│   ├── assets/              # Offline Bootstrap 5.3.3 CSS, JS, & Icons
│   ├── index.php            # Front controller & entry router
│   ├── .htaccess            # Apache rewrite rules & directory protection
│   └── *.php                # Clean entry delegates to front controller
├── src/                     # Core application source (inaccessible from web)
│   ├── Controllers/         # Application action controllers
│   │   ├── AuthController.php        # Session & authentication handling
│   │   ├── DashboardController.php   # Metrics & recent activity
│   │   ├── EquipmentController.php   # Inventory CRUD, catalogue & filters
│   │   ├── LoanController.php        # Requests, approvals, checkouts, returns
│   │   ├── MaintenanceController.php # Defect reporting & ticket closure
│   │   ├── ReportController.php      # Summary analytics & CSV export
│   │   └── UserController.php        # User administration & safety guards
│   ├── Auth.php             # Authentication, session, & RBAC permission checks
│   ├── Database.php         # PDO connection singleton & helper methods
│   ├── Equipment.php        # Equipment queries & availability computation
│   ├── Loan.php             # Concurrency locking, overlap detection, lifecycle
│   ├── Maintenance.php      # Maintenance ticketing & repair lifecycle
│   ├── Report.php           # Metric aggregations & formula-sanitized CSV export
│   ├── Router.php           # Dynamic convention-over-configuration router
│   └── User.php             # User management & last-admin safeguards
├── templates/               # Reusable view templates (XSS-escaped)
│   ├── layout/              # Base layout templates
│   │   ├── header.php       # HTML head, offline CSS, & navigation bar
│   │   ├── footer.php       # Page footer & offline scripts
│   │   └── _flashes.php     # Reusable toast / alert banner messages
│   ├── auth/                # Login template with demo quick-fill
│   ├── dashboard/           # Metrics cards & active alerts
│   ├── equipment/           # Inventory catalogue, item details, add/edit form
│   ├── loans/               # Loan lists, request form, checkout/return modals
│   ├── maintenance/         # Open ticket lists, defect logger, resolve form
│   ├── reports/             # Summary charts, analytics, & CSV export
│   ├── users/               # User list, account creation, & edit forms
│   └── errors/              # 403 Forbidden & 404 Not Found error views
├── config/                  # Environment & system configuration
│   ├── config.php           # Timezone, session policy, & app settings
│   ├── database.php         # Local credentials (ignored from version control)
│   └── database.example.php # Versioned configuration template
├── database/                # Relational schema & migration scripts
│   ├── schema.sql           # Complete DDL tables, constraints, & indexes
│   ├── seed.php             # Reproducible relative-date seeder
│   └── reset.php            # 1-step migration & seeder runner
├── includes/                # Common initialization helpers
│   ├── init.php             # Service bootstrapper & class autoloader
│   └── functions.php        # Escaping, CSRF helpers, & URL generators
├── logs/                    # Application error logs (outside DocumentRoot)
├── docs/                    # Technical documentation & reference material
│   ├── BACKUP_RESTORE.md    # Backup and disaster recovery procedures
│   ├── CREDENTIALS.md       # Demo accounts & passwords
│   ├── DATA_DICTIONARY.md   # Relational data dictionary & schema
│   ├── INSTALLATION.md      # Installation walkthrough
│   ├── TEST_REPORT.md       # Acceptance test evidence (T01 - T15)
│   └── USER_GUIDE.md        # User workflows by role
├── tests/                   # Automated verification test suites
│   ├── test_acceptance_cases.php # Complete T01 - T15 acceptance runner
│   ├── test_dynamic_routing.php  # Convention-over-configuration router tests
│   ├── test_pages.php       # Architectural & routing assertions
│   ├── test_refactoring.php # Service model & helper tests
│   ├── verify.php           # Core smoke test runner
│   └── verify_backup.php    # Post-restore integrity verification
└── README.md                # Master comprehensive documentation
```

---

### Dynamic Convention-over-Configuration Routing

ResourceHub uses a dynamic router (`src/Router.php`) supporting three clean routing conventions:
1. **Semantic Clean Paths:** `/loans/request`, `/equipment/create`, `/users/4/edit`.
2. **Query Parameters:** `?page=loans&action=request`.
3. **Dedicated Entry Delegates:** Files such as `public/loans.php` automatically invoke `Router::dispatch('loans')`.

---

### Non-Negotiable Coding Standards

- **Server-Side Validation & Prepared Statements:** All values passed to SQL queries are bound using PDO prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`). Form fields are validated on the server.
- **CSRF Token Verification:** All mutating HTTP POST requests verify a unique session token using `hash_equals()`.
- **Context-Aware Output Escaping:** Every dynamic string rendered in HTML is sanitized using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
- **Strict Role & Ownership Verification:** Actions check both role and record ownership (e.g. staff can only view and export their own loans; coordinators access organization-wide records).
- **Secure Password Hashing:** Credentials use native `password_hash($pass, PASSWORD_BCRYPT)` and `password_verify()`.
- **Session Cookie Security:** Session cookies use `HttpOnly` and `SameSite=Lax`. `Secure` flag is enabled only over HTTPS so local HTTP testing remains functional.
- **Database Transactions & Rollback:** All state changes and history records execute inside database transactions, rolling back immediately on any exception.
- **Credential & Backup Privacy:** Local configuration, credentials, and SQL dumps are excluded from version control.

---

## 6. User Workflows by Role

### 6.1. Staff Workflow (Borrower Journey)

1. **Browse Catalogue (`equipment.php`):** Filter items by Category, Physical Location, Condition (`new`, `good`, `fair`), and real-time Availability.
2. **Submit Request (`loan_request.php`):**
   - Select an available asset.
   - Select start date (must be today or later) and return due date (must be on or after start date).
   - Enter justification purpose (e.g. "Fieldwork environmental survey").
3. **Track Status (`loans.php`):** View requests under **My Loans** transitioning dynamically from `pending` &rarr; `approved` &rarr; `checked_out` &rarr; `returned`.
4. **Cancel Reservation:** Prior to physical checkout, staff can cancel their pending or approved request from `loan_view.php`.
5. **Export Personal Loans:** Download personal loan records via CSV export.

---

### 6.2. Coordinator Workflow (Inventory &amp; Operations Journey)

1. **Review Pending Requests (`loans.php`):** Inspect all pending requests submitted across the organisation.
2. **Approve or Reject:**
   - **Approve:** Evaluates date overlaps and overdue status under row-level lock; transitions status to `approved`.
   - **Reject:** Enforces a mandatory rejection note and marks status `rejected`.
3. **Physical Checkout Handover:** When the borrower collects the item on or after the start date, the coordinator clicks **Confirm Checkout**. Status becomes `checked_out`.
4. **Physical Return &amp; Defect Triage:**
   - When returned, inspect condition (`good`, `fair`, or `damaged`).
   - If returned in `damaged` condition, the system marks the item as `maintenance` and automatically opens an urgent repair ticket.
   - If functional, equipment status is restored to `available`.
5. **Maintenance Management (`maintenance.php`):** Log repair tickets, document technician findings, select restored condition (`new`, `good`, `fair`), and close tickets to re-enable equipment in circulation.
6. **Equipment Inventory Management:** Create, edit, and retire equipment assets.

---

### 6.3. Administrator Workflow (Governance Journey)

1. **User Management (`users.php`):**
   - View, search, and filter all registered users.
   - Create accounts with assigned roles (`staff`, `coordinator`, `administrator`).
   - Update user profiles and reset credentials.
   - Toggle user `is_active` status to deactivate accounts.
2. **Built-in Administrator Safeguards:**
   - **Self-Deactivation Prevention:** An administrator cannot deactivate their own active user account.
   - **Last Active Admin Guard:** The system rejects any attempt to deactivate, delete, or demote the last remaining active administrator.

---

## 7. Relational Schema &amp; Data Dictionary

The relational PostgreSQL schema enforces integrity through strict constraints, check validations, and `ON DELETE RESTRICT` foreign key rules.

### Entity-Relationship Diagram

```text
 ┌──────────────┐          ┌──────────────┐          ┌──────────────┐          ┌────────────────┐
 │  categories  │ 1      * │  equipment   │ 1      * │    loans     │ 1      * │  loan_history  │
 ├──────────────┤──────────├──────────────┤──────────├──────────────┤──────────├────────────────┤
 │ id (PK)      │          │ id (PK)      │          │ id (PK)      │          │ id (PK)        │
 │ name         │          │ asset_tag    │          │ asset_id(FK) │          │ loan_id (FK)   │
 │ description  │          │ name         │          │ user_id (FK) │          │ action         │
 └──────────────┘          │ category_id  │          │ start_date   │          │ performed_by   │
                           │ location_id  │          │ end_date     │          │ notes          │
 ┌──────────────┐ 1      * │ condition    │          │ status       │          │ created_at     │
 │  locations   │──────────│ status       │          │ purpose      │          └────────────────┘
 ├──────────────┤          └──────────────┘          │ approved_by  │
 │ id (PK)      │                 │                  │ checked_out_by│
 │ name         │                 │ 1                │ returned_by  │
 │ building     │                 │                  │ return_cond  │
 │ room         │                 │ *                └──────────────┘
 └──────────────┘          ┌──────────────┐                 │
                           │ maintenance  │                 │
                           ├──────────────┤                 │
                           │ id (PK)      │                 │
                           │ asset_id(FK) │                 │
                           │ opened_by(FK)│                 │
                           │ closed_by(FK)│                 │
                           │ issue_desc   │                 │
                           │ status       │                 │
                           └──────────────┘                 │
                                  │                         │
                                  v                         v
                           ┌─────────────────────────────────────┐
                           │                users                │
                           ├─────────────────────────────────────┤
                           │ id (PK)                             │
                           │ name, email, password_hash          │
                           │ role (admin / coord / staff)        │
                           │ is_active (boolean)                 │
                           └─────────────────────────────────────┘
```

---

### Detailed Table Specifications

#### 1. `users`
Stores user identities, credentials, and role assignments.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Auto-incrementing identifier. |
| `name` | `VARCHAR(100)` | **NO** | | User's full name. |
| `email` | `VARCHAR(150)` | **NO** | `UNIQUE` | Case-insensitive sign-in email. |
| `password_hash` | `VARCHAR(255)` | **NO** | | Bcrypt hash (`PASSWORD_BCRYPT`). |
| `role` | `VARCHAR(20)` | **NO** | `CHECK (role IN ('administrator', 'coordinator', 'staff'))` | Access permission tier. |
| `is_active` | `BOOLEAN` | **NO** | `DEFAULT TRUE` | Active toggle; `false` denies sign-in. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Account creation timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Profile update timestamp. |

#### 2. `categories`
Classification grouping for physical inventory assets.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Category identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Category title (e.g., Computing, Photography). |
| `description` | `TEXT` | **YES** | | Contextual summary of items. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

#### 3. `locations`
Physical storage buildings and room locations.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Location identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Location name (e.g., Main IT Store, Media Studio). |
| `building` | `VARCHAR(100)` | **YES** | | Campus or facility building. |
| `room` | `VARCHAR(50)` | **YES** | | Room or shelf reference number. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

#### 4. `equipment`
Catalogue of physical hardware assets, devices, and instruments.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Asset identifier. |
| `asset_tag` | `VARCHAR(50)` | **NO** | `UNIQUE` | Organization barcode/tag (e.g., `EQ-1001`). |
| `name` | `VARCHAR(150)` | **NO** | | Asset title (e.g., Dell Latitude 5540). |
| `category_id` | `INT` | **NO** | `REFERENCES categories(id) ON DELETE RESTRICT` | Associated category. |
| `location_id` | `INT` | **NO** | `REFERENCES locations(id) ON DELETE RESTRICT` | Home storage room. |
| `model` | `VARCHAR(100)` | **YES** | | Manufacturer model number. |
| `serial_number`| `VARCHAR(100)` | **YES** | | Hardware serial number. |
| `condition` | `VARCHAR(20)` | **NO** | `CHECK (condition IN ('new', 'good', 'fair', 'damaged'))` | Physical equipment condition. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('available', 'maintenance', 'retired'))` | Operational lifecycle state. |
| `notes` | `TEXT` | **YES** | | Accessories, inclusions, handling notes. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration date. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Last updated timestamp. |

#### 5. `loans`
Equipment reservations, active checkouts, and return transactions.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Loan reference number. |
| `asset_id` | `INT` | **NO** | `REFERENCES equipment(id) ON DELETE RESTRICT` | Requested equipment item. |
| `user_id` | `INT` | **NO** | `REFERENCES users(id) ON DELETE RESTRICT` | Borrower (staff user). |
| `start_date` | `DATE` | **NO** | | Requested checkout start date. |
| `end_date` | `DATE` | **NO** | `CONSTRAINT check_dates CHECK (end_date >= start_date)` | Scheduled return due date. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('pending', 'approved', 'checked_out', 'returned', 'rejected', 'cancelled'))` | Lifecycle state. |
| `purpose` | `TEXT` | **NO** | | Fieldwork / research justification. |
| `rejection_reason`| `TEXT` | **YES** | | Coordinator decline rationale. |
| `approved_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Approving coordinator. |
| `approved_at` | `TIMESTAMPTZ` | **YES** | | Decision timestamp. |
| `checked_out_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Handover coordinator. |
| `checked_out_at` | `TIMESTAMPTZ` | **YES** | | Handover timestamp. |
| `returned_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Receiving coordinator. |
| `returned_at` | `TIMESTAMPTZ` | **YES** | | Return receipt timestamp. |
| `return_condition`| `VARCHAR(20)` | **YES** | `CHECK (return_condition IN ('new', 'good', 'fair', 'damaged'))` | Condition inspected upon return. |
| `return_notes` | `TEXT` | **YES** | | Return inspection notes. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Submission timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Status change timestamp. |

#### 6. `loan_history`
Immutable event audit log tracking each transition in a loan's lifecycle.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | History event identifier. |
| `loan_id` | `INT` | **NO** | `REFERENCES loans(id) ON DELETE RESTRICT` | Parent loan record. |
| `action` | `VARCHAR(50)` | **NO** | | Action (`requested`, `approved`, `rejected`, `checked_out`, `returned`, `cancelled`). |
| `performed_by`| `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | User performing transition. |
| `notes` | `TEXT` | **YES** | | Transition comment or reason. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Immutable event timestamp. |

#### 7. `maintenance`
Repair, damage report, and servicing tracker for equipment.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Maintenance ticket identifier. |
| `asset_id` | `INT` | **NO** | `REFERENCES equipment(id) ON DELETE RESTRICT` | Defective equipment asset. |
| `opened_by` | `INT` | **NO** | `REFERENCES users(id) ON DELETE RESTRICT` | User opening the ticket. |
| `closed_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Coordinator certifying repair. |
| `issue_description` | `TEXT` | **NO** | | Defect or damage description. |
| `resolution_notes` | `TEXT` | **YES** | | Repair details and testing outcome. |
| `restored_condition` | `VARCHAR(20)`| **YES** | `CHECK (restored_condition IN ('new', 'good', 'fair'))` | Restored condition upon closure. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('open', 'resolved', 'cancelled')) DEFAULT 'open'` | Ticket state. |
| `opened_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Ticket opened timestamp. |
| `closed_at` | `TIMESTAMPTZ` | **YES** | | Ticket resolution timestamp. |

---

## 8. Business Rules, Concurrency &amp; Defensive Hardening

### Loan Lifecycle State Machine

```text
               [ Staff Submits Request ]
                          │
                          v
                    [ PENDING ] ────────────┐
                          │                 │ Coordinator Declines
     Coordinator Approves │                 v
                          v            [ REJECTED ] (Final)
                    [ APPROVED ]
                          │     \
    Handover on or after  │      \ Borrower / Coordinator
    scheduled start date  │       \ Cancels before handover
                          v        v
                   [ CHECKED_OUT ] [ CANCELLED ] (Final)
                          │
         Borrower Returns │
         Equipment Item   │
                          v
                    [ RETURNED ] (Final)
```

- **Terminal States:** `rejected`, `cancelled`, and `returned` are immutable final states.
- **Pre-Handover Cancellation:** Borrowers or coordinators can cancel any loan in `pending` or `approved` state prior to physical checkout handover.

---

### Inclusive Calendar Boundaries &amp; Overlap Detection

1. **Past Date Guard:** `start_date >= CURRENT_DATE`. Past reservations are rejected during validation.
2. **Inverted Date Guard:** `end_date >= start_date`. Inverted spans are rejected.
3. **Inclusive Boundary Overlap:** Date spans sharing a boundary day are treated as overlapping conflicts:
   ```sql
   l.start_date <= :new_end_date AND l.end_date >= :new_start_date
   ```
   *Example:* If Loan A is scheduled Nov 1 &ndash; Nov 10, Loan B starting on Nov 10 is blocked as a conflict; Loan B may start on Nov 11.

---

### PostgreSQL Concurrency Control (`SELECT ... FOR UPDATE`)

To ensure that simultaneous coordinator approvals or rapid user submissions cannot double-book an asset, ResourceHub wraps availability-critical operations in PostgreSQL transactions with row-level locking:

```sql
BEGIN;

-- Lock the target equipment record exclusively for the transaction
SELECT * FROM equipment WHERE id = :asset_id FOR UPDATE;

-- Evaluate conflicts against active/approved loans
SELECT COUNT(*) FROM loans 
WHERE asset_id = :asset_id 
  AND status IN ('approved', 'checked_out')
  AND start_date <= :end_date 
  AND end_date >= :start_date;

-- If clean, insert/update loan and commit; otherwise rollback.
COMMIT;
```

---

### Overdue &amp; Maintenance Blocking Logic

1. **Damaged / In-Maintenance Gear:** Items marked `damaged`, `maintenance`, `retired`, or having an open maintenance ticket cannot be requested or approved.
2. **Overdue Checkout Blocking:** Any asset currently checked out with `end_date < CURRENT_DATE` is flagged overdue. The system blocks any new approvals or checkouts for that asset until the physical return is confirmed.
3. **Checkout Return Inspection:** When returning equipment:
   - If returned in `damaged` condition, equipment status automatically updates to `maintenance`, and a new open repair ticket is generated immediately.

---

### Defensive Security Protections

- **CSRF Token Validation:** Every mutating HTTP `POST` submission requires a cryptographic token stored in `$_SESSION['_csrf_token']` and verified via `hash_equals()`.
- **SQL Injection Defense:** All database interactions use PDO prepared statements with strict parameter bindings. Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
- **XSS Defense:** View templates escape all dynamic user inputs using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` via the `e()` helper function.
- **CSV Formula Injection Mitigation:** All CSV report exports sanitize field values starting with `=`, `+`, `-`, or `@` by prepending a single quote (`'`), preventing malicious spreadsheet formula execution.

---

## 9. Database Backup &amp; Disaster Recovery

ResourceHub provides standard PostgreSQL backup and restoration procedures satisfying disaster recovery and Acceptance Case **T14**.

### Creating Backups (`pg_dump`)

Dump the entire `resourcehub` database schema and data into a standard SQL archive:

#### On Ubuntu Linux (or WSL):
```bash
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub > resourcehub_backup.sql
```

#### On Windows:
```powershell
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub -F p -f resourcehub_backup.sql
```
*(Enter password `root` when prompted)*.

---

### Restoring from Backup (`psql`)

To test disaster recovery or restore into a secondary verification database:

#### Step 1: Create the target clean database
```sql
psql -U postgres -c "DROP DATABASE IF EXISTS resourcehub_restore;"
psql -U postgres -c "CREATE DATABASE resourcehub_restore WITH ENCODING 'UTF8';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE resourcehub_restore TO resourcehub_app;"
```

#### Step 2: Restore the SQL archive into the target database
```bash
psql -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub_restore -f resourcehub_backup.sql
```

---

### Automated Restoration Verification

Verify that all tables, primary keys, record counts, and relationships restored accurately by running:

```bash
php tests/verify_backup.php
```

#### Expected Post-Restoration Record Verification:
- **`users`:** 6 rows (1 administrator, 1 coordinator, 4 staff).
- **`categories`:** 4 rows.
- **`locations`:** 3 rows.
- **`equipment`:** 20 assets.
- **`loans`:** 12+ loan records.
- **`loan_history`:** Audit records intact.
- **`maintenance`:** Open tickets intact.
- **Password Hashes:** All Bcrypt passwords remain valid.

---

## 10. Acceptance Test Report (T01 &mdash; T15)

The application has been verified against all 15 formal acceptance test criteria with a 100% pass rate.

### Acceptance Matrix

| Case | Case Title &amp; Test Description | Expected Result | Actual Result | Status |
| :---: | :--- | :--- | :--- | :---: |
| **T01** | **Fresh Installation &amp; Service Reboot** | System boots from fresh schema and seeder; Apache serves root, PDO connects to PostgreSQL, offline Bootstrap assets render without external internet. | Reset script (`php database/reset.php`) initializes 6 users, 20 assets, 12 loans. UI renders offline styles cleanly. | **PASS** |
| **T02** | **Authentication &amp; Inactive Rejection** | Valid credentials authenticate; wrong password and inactive accounts (`inactive@resourcehub.local`) denied; logout terminates session. | Correct password redirects to `dashboard.php`. Inactive user and wrong password return denial alert. Logout clears session. | **PASS** |
| **T03** | **IDOR / Ownership Privilege Isolation** | A staff user entering another borrower's loan identifier into URL or POST is denied access without information disclosure. | Requesting `loan_view.php?id=...` of another user returns HTTP 403 Access Denied. No record metadata is leaked. | **PASS** |
| **T04** | **Role Hierarchy &amp; Last Admin Guard** | Staff navigating to `equipment_add.php` or `users.php` are denied. Deactivating self or the last active admin is rejected. | HTTP 403 returned on unauthorized routes. Self-deactivation and demotion of the final administrator throw protected exceptions. | **PASS** |
| **T05** | **Field Validation &amp; Inverted Dates** | Duplicate asset tag, empty name, start date in the past, or inverted dates (`end < start`) are rejected with clear user messages. | Validation halts execution with alert. Form fields retain user inputs without wiping entries. | **PASS** |
| **T06** | **Boundary Day Inclusive Overlap** | Date ranges sharing a boundary day (e.g. Loan A ends on Nov 10, Loan B starts on Nov 10) detect conflict; second approval is rejected. | Conflict detection considers calendar dates inclusive; second coordinator approval throws conflict exception. | **PASS** |
| **T07** | **Concurrent Approval Race Condition** | Two browser sessions attempting to approve overlapping requests for the same asset simultaneously result in exactly one commit. | PostgreSQL row-level lock (`SELECT ... FOR UPDATE`) serializes the transactions; second approval safely aborts with conflict error. | **PASS** |
| **T08** | **Restricted Checkout Guard** | Checkout of pending, expired, early (scheduled in future), damaged, or maintenance-blocked item is rejected without state change. | Status validation and date checks reject checkout attempts before start date or after due date without modifying records. | **PASS** |
| **T09** | **Physical Return &amp; Idempotency** | Returning checked-out item records `returned_at` timestamp, condition, and history event once. Duplicate return is rejected. | Return succeeds and marks item available. Immediate subsequent return submission is rejected as not checked out. | **PASS** |
| **T10** | **Maintenance Workflow &amp; Blocking** | Cannot open maintenance on active checkout. Returned damaged item auto-routes to maintenance. Closing repair restores availability. | Active checkout blocks ticket opening. Damaged camera return triggers maintenance ticket; resolution restores asset to inventory. | **PASS** |
| **T11** | **Overdue Checkout Blocking** | Overdue loan displays alert badge and blocks new approvals or checkouts on that asset until physically returned. | Overdue Dell Latitude (Loan #1) shows warning badge. New approval attempt on asset 1 is blocked until returned. | **PASS** |
| **T12** | **CSRF &amp; Injection Hardening** | Submissions without valid CSRF tokens are rejected. SQL injection payloads and `<script>` HTML tags are safely neutralized. | Missing CSRF returns HTTP 400. PDO prepared statements prevent SQL injection; `htmlspecialchars` neutralizes XSS. | **PASS** |
| **T13** | **Reporting &amp; CSV Sanitization** | Filtered reports match authorized records. Formula injection characters (`=`, `+`, `-`, `@`) are neutralized. | Generated CSV escapes spreadsheet formula triggers with single quote prefix; coordinators export all loans, staff only export own. | **PASS** |
| **T14** | **Database Backup &amp; Restoration** | `pg_dump` backup restores into a clean database with identical record counts and operational workflows. | Database dump and restore reproduces all 20 assets, 12 loans, and password hashes successfully. | **PASS** |
| **T15** | **Responsive Design &amp; Usability** | All primary actions remain operable on narrow screens (<768px); mobile sidebar drawer functions; form inputs accessible. | Mobile navigation collapse works via backdrop toggle; typography scales gracefully across viewports. | **PASS** |

---

### Automated Test Execution Evidence

Run the full automated verification suite anytime from the project root:

```bash
# 1. Full Acceptance Test Suite (T01 - T15)
php tests/test_acceptance_cases.php

# 2. Dynamic Routing & Architecture Tests
php tests/test_dynamic_routing.php
php tests/test_pages.php

# 3. Model & Service Assertions
php tests/test_refactoring.php

# 4. Smoke Test Suite
php tests/verify.php

# 5. Database Restore Verification
php tests/verify_backup.php
```

#### Acceptance Suite Output (`tests/test_acceptance_cases.php`):
```text
==========================================================
  ResourceHub Acceptance Test Runner (Section 9: T01-T15)  
==========================================================

[PASS] T01: Fresh installation and reboot (Users: 6, Equipment: 20, Loans: 12)
[PASS] T02: Wrong password or inactive account denied; valid login & logout work
[PASS] T03: Staff request another user's loan by URL or POST denied without disclosure
[PASS] T04: Staff restricted from admin/coord actions; last active admin guarded
[PASS] T05: Duplicate asset tag, missing name, or inverted dates rejected with clear message
[PASS] T06: Boundary day overlap detected; second approval rejected as dates are inclusive (Conflicting Loan #3)
[PASS] T07: Two concurrent approval sessions serialized via row lock FOR UPDATE
[PASS] T08: Checkout of pending, expired, early, or damaged/maintenance asset rejected
[PASS] T09: Return checked-out item records returned_at and history; repeat return rejected
[PASS] T10: Maintenance blocked on checked-out asset; resolution restores availability
[PASS] T11: Overdue checkout loan blocks approval and checkout until returned
[PASS] T12: Missing/invalid CSRF token rejected; HTML and SQL injection neutralized
[PASS] T13: Dashboard metrics match; spreadsheet formulas neutralised for CSV export
[PASS] T14: Database schema, foreign key relations and record counts intact
[PASS] T15: Responsive layout, accessible labels, focus and navigation operable

==========================================================
  Acceptance Test Summary: 15 of 15 Tests Passed (100%)
==========================================================
ALL 15 ACCEPTANCE TESTS (T01 - T15) FULLY VERIFIED!
```

---

## 11. Extensions &amp; Known Limitations

### Implemented Extensions
- **Visual Equipment Loan Distribution Chart:** Responsive analytics chart on the **Reports** page (`public/reports.php`) illustrating loan frequency across equipment categories.
- **1-Click Demo Persona Shortcuts:** Instant authentication buttons on `public/login.php` to immediately log in as Administrator, Coordinator, or Staff for rapid testing and demonstrations.
- **Dynamic Convention-over-Configuration Router:** Located in `src/Router.php`, resolving clean semantic URLs and automated controller dispatch.

### Intentionally Deferred Scope
As agreed in Section 2 of the internship plan, the following items are intentionally excluded from the local offline core scope:
- **Email Server &amp; External SMTP Dependencies:** Excluded to ensure 100% self-contained offline operability.
- **File Uploads &amp; Image Attachments:** Excluded to prevent file system bloat; physical asset tags and manufacturer serials serve as verified identifiers.
- **Public Self-Registration:** All accounts are created and provisioned by administrators to uphold institutional access governance.
- **Procurement &amp; Payment Processing:** Asset purchasing and finance workflows remain handled by external organizational enterprise systems.

---

*ResourceHub &mdash; Reliable, Concurrency-Safe Equipment Loan &amp; Maintenance Management.*
