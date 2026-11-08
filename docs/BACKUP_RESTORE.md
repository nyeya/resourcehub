# ResourceHub &mdash; Database Backup and Restoration Guide

This document outlines the standard procedure for creating PostgreSQL backups and verifying full data restoration into a separate database instance, satisfying the **Day 18** and **Acceptance Case T14** requirements.

---

## 1. Creating a Database Backup (`pg_dump`)

To dump the entire `resourcehub` database schema and data into a compressed SQL archive:

### Windows (PowerShell or Command Prompt):
```powershell
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub -F p -f resourcehub_backup.sql
```

### Linux / Ubuntu:
```bash
pg_dump -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub > resourcehub_backup.sql
```

*(Enter the application password: `root` when prompted)*

---

## 2. Restoring to a Separate Local Database

To verify that the backup is functional and self-contained, restore it into a clean secondary database:

### Step 1: Create a Clean Target Database
```bash
psql -U postgres -c "CREATE DATABASE resourcehub_restore;"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE resourcehub_restore TO resourcehub_app;"
```

### Step 2: Apply the Backup Archive
```bash
psql -U resourcehub_app -h 127.0.0.1 -p 5432 -d resourcehub_restore -f resourcehub_backup.sql
```

---

## 3. Automated Restoration Verification

You can verify that all tables, primary keys, record counts, and relationships restored accurately by running:

```bash
php tests/verify_backup.php
```

### Expected Post-Restoration Record Counts:
- **`users`:** 6 rows (1 administrator, 1 coordinator, 4 staff)
- **`categories`:** 4 rows
- **`locations`:** 3 rows
- **`equipment`:** 20 assets
- **`loans`:** 12 loan records
- **`loan_history`:** Audit records intact
- **`maintenance`:** 1 active repair ticket

All Bcrypt password hashes (`Password123!`) remain functional post-restore.

