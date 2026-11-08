# ResourceHub &mdash; Equipment Loan & Maintenance Management System

ResourceHub is a local equipment loan and maintenance management web application built for small organisations to manage shared assets (laptops, cameras, laboratory instruments, and fieldwork gear). It features strict role-based access control, PostgreSQL row-level concurrency locking, an immutable audit trail, and zero-dependency offline Bootstrap 5 styling.

---

## 1. Stack & Environment Specifications

- **Server:** Apache HTTP Server 2.4+ (DocumentRoot pointing to `public/`)
- **Backend:** PHP 8.1+ (Tested on PHP 8.5+ with `pdo`, `pdo_pgsql`, `session`, `filter`)
- **Database:** PostgreSQL 14+ (UTF-8 encoding, transactional row locking)
- **Frontend:** Bootstrap 5.3 + Bootstrap Icons 1.11 (Stored locally in `public/assets/`, 100% offline-ready)
- **Timezone:** Configured in `config/config.php` (Default: `UTC`)

---

## 2. Directory Architecture (Section 8 Standard)

| Location | Purpose (Section 8 Implementation Standard) |
| :--- | :--- |
| `public/` | Apache document root; `index.php` front controller and locally stored Bootstrap assets. |
| `src/` | Controllers, database queries, permission checks, and workflow services. |
| `templates/` | Shared layout, forms, and view templates with escaped output. |
| `config/` | Tracked example configuration; ignored local credentials outside `public/`. |
| `database/` | Ordered migrations and separate reproducible sample data script. |
| `tests/` & `docs/` | Focused checks, test evidence, installation guide, and user guide. |

```text
resource-hub/
├── public/                  # Apache DocumentRoot
│   ├── assets/              # Local CSS, JS, and font assets (100% offline)
│   │   ├── css/
│   │   └── js/
│   ├── index.php            # Front Controller & entry router
│   ├── .htaccess            # Apache configuration
│   └── *.php                # Clean entry delegates to Router
├── src/                     # Controllers, queries, permissions & services
│   ├── Controllers/         # Action controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EquipmentController.php
│   │   ├── LoanController.php
│   │   ├── MaintenanceController.php
│   │   ├── ReportController.php
│   │   └── UserController.php
│   ├── Auth.php             # Authentication, session, & RBAC permission checks
│   ├── Database.php         # PDO connection & database queries
│   ├── Equipment.php        # Equipment inventory service
│   ├── Loan.php             # Loan lifecycle, concurrency locking & conflicts
│   ├── Maintenance.php      # Maintenance tickets & repairs
│   ├── Report.php           # Analytics, distribution & safe CSV exports
│   ├── Router.php           # Front controller dispatcher (query & path routing)
│   └── User.php             # User management & last admin guards
├── templates/               # Shared layout, forms & view templates
│   ├── layout/              # Shared navigation, topbar, footer & flashes
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── _flashes.php
│   ├── auth/                # Login template
│   ├── dashboard/           # Overview dashboard template
│   ├── equipment/           # Catalogue, item view, & shared form template
│   ├── loans/               # Loan lists, request form, & detail view template
│   ├── maintenance/         # Ticket list & open maintenance template
│   ├── reports/             # Summary metrics & category chart template
│   ├── users/               # User administration & shared user form template
│   └── errors/              # 403 Access Denied & 404 Not Found templates
├── config/                  # Protected configuration
│   ├── config.php           # Application & session settings
│   ├── database.php         # Local PostgreSQL credentials
│   └── database.example.php # Versioned configuration template
├── database/                # Ordered migrations and seeders
│   ├── schema.sql           # Versioned PostgreSQL DDL & constraints
│   ├── seed.php             # Reproducible relative-date seeder
│   └── reset.php            # 1-step database migration & reset
├── docs/                    # Technical documentation
│   ├── BACKUP_RESTORE.md    # Backup and disaster recovery procedures
│   ├── CREDENTIALS.md       # Demo accounts & passwords
│   ├── DATA_DICTIONARY.md   # Day 4 relational data dictionary
│   ├── INSTALLATION.md      # Installation walkthrough
│   ├── TEST_REPORT.md       # Acceptance test evidence (T01 - T15)
│   └── USER_GUIDE.md        # User workflows by role
├── includes/                # Initialization & global helpers
│   ├── init.php             # Autoloader & bootstrap initialization
│   └── functions.php        # Escaping, CSRF, flash, & template helpers
├── logs/                    # Application error logs (outside public/)
├── tests/                   # Focused verification test suites
│   ├── test_acceptance_cases.php # Complete T01 - T15 automated test runner
│   ├── test_pages.php       # Architectural & routing tests
│   ├── test_refactoring.php # Model & service tests
│   ├── verify.php           # Smoke test runner
│   └── verify_backup.php    # Database restore verification
└── README.md                # Project documentation
```

---

## 3. Installation & Quick Setup

### Step 1: Database Setup
Create the PostgreSQL database and user:
```sql
CREATE DATABASE resourcehub;
CREATE USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';
GRANT ALL PRIVILEGES ON DATABASE resourcehub TO resourcehub_app;
```

Update your credentials in `config/database.php` if different.

### Step 2: Initialize & Seed Database
Run the automated migration and seeding script:
```bash
php database/reset.php
```
This applies `database/schema.sql` and populates the repeatable test dataset:
- 6 users (1 administrator, 1 coordinator, 4 staff)
- 4 equipment categories
- 3 physical storage locations
- 20 physical inventory assets
- 1 open maintenance record (on damaged Sony Camera EQ-1004)
- 12 loans covering all lifecycle states (`pending`, `approved`, `checked_out`, `returned`, `rejected`, `cancelled`), including overdue and conflicting requests.

---

## 4. Demo Accounts & Credentials

All demo accounts use the standard password: **`Password123!`**

| User Name | Email | Role | Testing Scenarios |
| :--- | :--- | :--- | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | User administration, role modifications, last admin protection. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | Equipment CRUD, loan approval/rejection, physical checkout & return, maintenance tickets. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | Catalogue browsing, requesting loans, viewing own loans. Has an active overdue loan (EQ-1001). |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | Active checked-out loan (EQ-1002); conflicting pending loan request on EQ-1007. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | Approved future loan (EQ-1003); returned damaged camera history (EQ-1004). |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | Inactive account verification (sign-in must be rejected). |

> **Tip:** The login page (`public/login.php`) provides **1-click demo role tiles** to quickly test different roles without typing.

---

## 5. Core Business Rules & Concurrency Control

1. **Role-Based Access Control (RBAC):**
   - **Staff:** Browse equipment; submit requests; view and cancel own pending/approved requests; export own loan CSV.
   - **Coordinator:** All staff actions plus full equipment management, loan approval/rejection/checkout/return, and maintenance ticketing.
   - **Administrator:** All coordinator actions plus user account creation, role management, and deactivation.
2. **Inclusive Calendar Dates:**
   - Request start date must be today or later (`start_date >= CURRENT_DATE`).
   - End date must be on or after start date (`end_date >= start_date`).
   - Inclusive overlap conflict: two loans conflict if `l.start_date <= :end_date AND l.end_date >= :start_date`.
3. **Database Concurrency Protection:**
   - Availability-critical operations lock the equipment record in a transaction:
     ```sql
     SELECT * FROM equipment WHERE id = :id FOR UPDATE;
     ```
   - If two coordinators attempt to approve overlapping requests simultaneously, exactly one commits; the other is rejected with a conflict error.
4. **Maintenance & Overdue Blocking:**
   - Damaged, retired, or maintenance-ticketed assets cannot be requested, approved, or checked out.
   - Overdue loans (`checked_out` and `end_date < CURRENT_DATE`) block new approvals and checkouts on that asset until physically returned.
   - Maintenance cannot be opened on an asset currently checked out; physical return must be recorded first.
5. **History & Integrity Guards:**
   - Status changes are committed with immutable audit events in `loan_history` (aliased as `loan_events`).
   - Users and assets are deactivated or retired rather than deleted (`ON DELETE RESTRICT`).
   - Administrators cannot deactivate their own active session or remove the final active administrator.
6. **CSV Formula Injection Sanitization:**
   - Dynamic exports prepend `'` to fields beginning with `=`, `+`, `-`, or `@` to prevent spreadsheet formula execution.

---

## 6. Running Acceptance Tests

Run the full automated test suite verifying all 15 acceptance criteria (T01 - T15):

```bash
php tests/test_acceptance_cases.php
```

You can also run component verifications:
```bash
php tests/verify.php          # Smoke tests (auth, pagination, rules, guards)
php tests/test_pages.php      # Architectural & URL mapping tests
php tests/test_refactoring.php # Model & Helper assertions
php tests/verify_backup.php   # Database restoration integrity
```

---

## 7. Optional Extensions Implemented

- **Loans by Category Distribution Chart:** Accessible on the **Reports** page (`public/reports.php`), featuring a responsive distribution chart showing loan volume per equipment category.

