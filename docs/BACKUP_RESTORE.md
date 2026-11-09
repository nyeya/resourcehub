# ResourceHub &mdash; Database Backup and Restoration Guide

This document details the standard procedures for creating PostgreSQL backups and verifying full data restoration into a clean secondary database instance, satisfying the **Day 18** and **Acceptance Case T14** requirements of the project plan.

---

## 1. Environment &amp; System Specifications

- **Reference Host:** Ubuntu 26.04 LTS (x86_64)
- **Database Engine:** PostgreSQL 16+ / 18.6 (UTF-8 encoding, port 5432)
- **PHP Runtime:** PHP 8.5.12 with `pdo_pgsql`
- **Application Database:** `resourcehub`
- **Application Role:** `resourcehub_app`

---

## 2. Creating a Database Backup (`pg_dump`)

Dump the entire `resourcehub` database schema and data into a standard SQL archive:

### On Ubuntu Linux / Debian (Reference Platform):
```bash
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub > resourcehub_backup.sql
```

### On Windows (PowerShell or Command Prompt):
```powershell
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub -F p -f resourcehub_backup.sql
```

*(Enter the application role password: `root` when prompted)*

> **Security Note:** The generated SQL dump (`resourcehub_backup.sql`) contains password hashes and transaction data; it is excluded from Git via `.gitignore`.

---

## 3. Restoring to a Separate Local Database

To prove that the database backup is fully self-contained and reproducible without verbal aid, restore the archive into a separate verification database:

### Step 1: Create a Clean Target Database
Launch `psql` as the administrative user (`postgres`):

```bash
# On Ubuntu:
sudo -u postgres psql
```

Execute the database provisioning commands:

```sql
-- Clean up any previous verification database
DROP DATABASE IF EXISTS resourcehub_restore;

-- Create target database with explicit UTF-8 encoding
CREATE DATABASE resourcehub_restore WITH ENCODING 'UTF8';

-- Grant privileges to the application user
GRANT ALL PRIVILEGES ON DATABASE resourcehub_restore TO resourcehub_app;

-- Grant schema privileges in PostgreSQL 15+ / 16+
\c resourcehub_restore
GRANT ALL ON SCHEMA public TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO resourcehub_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO resourcehub_app;

\q
```

### Step 2: Apply the Backup Archive
Restore the SQL archive directly using `psql`:

```bash
# Ubuntu Linux / Windows
psql -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub_restore -f resourcehub_backup.sql
```

---

## 4. Automated Restoration Verification

Execute the automated verification runner to confirm database structure, foreign key relations, record counts, and authentication functionality:

```bash
php tests/verify_backup.php
```

### Expected Output:
```text
=== Verifying Backup Database (resourcehub_restore) ===
Connected to restore database successfully.

Table Record Counts:
 - users: 6 (Expected: 6) -> PASS
 - categories: 4 (Expected: 4) -> PASS
 - locations: 3 (Expected: 3) -> PASS
 - equipment: 20 (Expected: 20) -> PASS
 - loans: 12 (Expected: >= 12) -> PASS
 - loan_history: PASS
 - maintenance: 1 (Expected: >= 1) -> PASS

Authentication Verification:
 - Testing login for admin@resourcehub.local... PASS (Password123!)
 - Inactive user inactive@resourcehub.local rejected: PASS

=== All Backup Verification Checks Passed! ===
```

### Key Verification Metrics:
- **`users`:** 6 rows (1 administrator, 1 coordinator, 4 staff).
- **`categories`:** 4 rows.
- **`locations`:** 3 rows.
- **`equipment`:** 20 assets with full serials and tags.
- **`loans`:** 12+ loan records covering all lifecycle states.
- **`loan_history`:** Immutable audit log intact.
- **`maintenance`:** 1 open repair ticket on damaged camera `EQ-1004`.
- **Authentication:** All native Bcrypt password hashes (`Password123!`) remain functional post-restoration.

---

## 5. Day 18 Handover Acceptance Sign-Off

- [x] Database dump completes without warnings or syntax errors.
- [x] Fresh restore reproduces identical table counts, constraints, and sequences.
- [x] Verification script passes automatically with zero manual SQL intervention.
- [x] A teammate can perform a fresh installation and restoration following this guide without verbal assistance.
