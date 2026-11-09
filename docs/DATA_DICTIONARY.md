# ResourceHub &mdash; Database Data Dictionary

This document details the PostgreSQL relational schema, column definitions, data types, constraints, indexes, and business rules implemented in ResourceHub, satisfying the **Day 4 Data Dictionary** requirement of the internship project plan.

---

## 1. Environment &amp; Engine Specifications

- **Reference Operating System:** Ubuntu 26.04 LTS (x86_64)
- **Database Engine:** PostgreSQL 16+ / 18.6
- **Database Name:** `resourcehub`
- **Application User:** `resourcehub_app`
- **Encoding &amp; Collation:** `UTF8` / `en_US.UTF-8`
- **Timezone:** `UTC`
- **Referential Integrity Policy:** `ON DELETE RESTRICT` on core assets, categories, locations, and users to prevent accidental data destruction; `ON DELETE SET NULL` on audit actor references to preserve historical audit logs.

---

## 2. Entity-Relationship Overview

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

## 3. Table Specifications

### 3.1. `users`
Stores user identities, credentials, and role-based permissions.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Auto-incrementing unique user identifier. |
| `name` | `VARCHAR(100)` | **NO** | | User's full name. |
| `email` | `VARCHAR(150)` | **NO** | `UNIQUE` | Case-insensitive sign-in email address. |
| `password_hash` | `VARCHAR(255)` | **NO** | | Native Bcrypt password hash (`PASSWORD_BCRYPT`). |
| `role` | `VARCHAR(20)` | **NO** | `CHECK (role IN ('administrator', 'coordinator', 'staff'))` | Permission tier. |
| `is_active` | `BOOLEAN` | **NO** | `DEFAULT TRUE` | Active toggle; `false` denies authentication. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Account creation timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Last profile update timestamp. |

---

### 3.2. `categories`
Classification grouping for physical inventory assets.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Category identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Category title (e.g., Computing, Photography). |
| `description` | `TEXT` | **YES** | | Contextual summary of items in category. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

---

### 3.3. `locations`
Physical storage buildings and room locations.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Location identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Location name (e.g., Main IT Store, Media Studio). |
| `building` | `VARCHAR(100)` | **YES** | | Campus or facility building name. |
| `room` | `VARCHAR(50)` | **YES** | | Room or shelf reference code. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

---

### 3.4. `equipment`
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
| `condition` | `VARCHAR(20)` | **NO** | `CHECK (condition IN ('new', 'good', 'fair', 'damaged'))` | Physical hardware condition. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('available', 'maintenance', 'retired'))` | Operational lifecycle state. |
| `notes` | `TEXT` | **YES** | | Inclusions, accessories, handling notes. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration date. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Last updated timestamp. |

---

### 3.5. `loans`
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
| `approved_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Reviewing coordinator. |
| `approved_at` | `TIMESTAMPTZ` | **YES** | | Decision timestamp. |
| `checked_out_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Handover coordinator. |
| `checked_out_at` | `TIMESTAMPTZ` | **YES** | | Handover timestamp. |
| `returned_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Receiving coordinator. |
| `returned_at` | `TIMESTAMPTZ` | **YES** | | Return receipt timestamp. |
| `return_condition`| `VARCHAR(20)` | **YES** | `CHECK (return_condition IN ('new', 'good', 'fair', 'damaged'))` | Condition inspected upon return. |
| `return_notes` | `TEXT` | **YES** | | Inspection notes on return. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Request submission timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Status change timestamp. |

---

### 3.6. `loan_history`
Immutable event audit log tracking each transition in a loan's lifecycle.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | History event identifier. |
| `loan_id` | `INT` | **NO** | `REFERENCES loans(id) ON DELETE RESTRICT` | Parent loan record. |
| `action` | `VARCHAR(50)` | **NO** | | Action (`requested`, `approved`, `rejected`, `checked_out`, `returned`, `cancelled`). |
| `performed_by`| `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | User performing transition. |
| `notes` | `TEXT` | **YES** | | Transition comment or reason. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Immutable event timestamp. |

---

### 3.7. `maintenance`
Repair, damage report, and servicing tracker for equipment.

| Column | Data Type | Nullable | Constraints &amp; Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Maintenance ticket identifier. |
| `asset_id` | `INT` | **NO** | `REFERENCES equipment(id) ON DELETE RESTRICT` | Equipment under maintenance. |
| `opened_by` | `INT` | **NO** | `REFERENCES users(id) ON DELETE RESTRICT` | Coordinator reporting the issue. |
| `closed_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Coordinator certifying repair. |
| `issue_description` | `TEXT` | **NO** | | Defect or damage description. |
| `resolution_notes` | `TEXT` | **YES** | | Repair details and testing outcome. |
| `restored_condition` | `VARCHAR(20)`| **YES** | `CHECK (restored_condition IN ('new', 'good', 'fair'))` | Restored condition upon closure. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('open', 'resolved', 'cancelled')) DEFAULT 'open'` | Ticket state. |
| `opened_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Ticket opened timestamp. |
| `closed_at` | `TIMESTAMPTZ` | **YES** | | Ticket resolution timestamp. |

---

## 4. State Machine &amp; Business Rules

### 4.1. Loan State Machine
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
- **Terminal States:** `rejected`, `cancelled`, and `returned` are immutable end-states.
- **Cancellation Grace:** Borrowers or coordinators can cancel any loan in `pending` or `approved` state prior to physical checkout handover.

---

### 4.2. Equipment Availability Logic
Equipment availability is **derived dynamically** using five strict conditions:
1. Physical condition is functional: `condition != 'damaged'`.
2. Operational circulation state is active: `status == 'available'` (not `maintenance` or `retired`).
3. No active maintenance ticket exists: `NOT EXISTS (SELECT 1 FROM maintenance WHERE asset_id = :id AND status = 'open')`.
4. No overlapping approved or checked-out reservations: `NOT EXISTS (SELECT 1 FROM loans WHERE asset_id = :id AND status IN ('approved', 'checked_out') AND start_date <= :end_date AND end_date >= :start_date)`.
5. No overdue active loans on the item: `NOT EXISTS (SELECT 1 FROM loans WHERE asset_id = :id AND status = 'checked_out' AND end_date < CURRENT_DATE)`.

---

### 4.3. Transactional Concurrency Control
All availability-altering actions execute within PostgreSQL transactions guarded by row-level locking:
```sql
SELECT * FROM equipment WHERE id = :id FOR UPDATE;
```
This guarantees that concurrent requests or simultaneous coordinator approvals cannot produce duplicate or overlapping loans.
