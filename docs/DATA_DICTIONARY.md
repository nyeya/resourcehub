# ResourceHub &mdash; Database Data Dictionary

This document details the PostgreSQL relational schema, constraints, data types, and business rules implemented in ResourceHub, satisfying the **Day 4 Data Dictionary** requirement of the internship project plan.

---

## 1. Entity-Relationship Overview

```
 [categories] 1 ────< [equipment] 1 ────< [loans] 1 ────< [loan_history]
                         │                   │
 [locations]  1 ─────────┤                   ├───> [users] (borrower)
                         │                   ├───> [users] (approver)
 [users] 1 ──────────────┼───────────────────┼───> [users] (checkout by)
 (opened/closed by)      │                   └───> [users] (return by)
                         v
                   [maintenance]
```

All foreign keys enforce `ON DELETE RESTRICT` to ensure data history integrity (assets and users are retired or deactivated rather than deleted).

---

## 2. Table Specifications

### 2.1. `users`
Stores user identities, credentials, and role-based permissions.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Auto-incrementing identifier. |
| `name` | `VARCHAR(100)` | **NO** | | User's full name. |
| `email` | `VARCHAR(150)` | **NO** | `UNIQUE` | Case-insensitive sign-in email. |
| `password_hash` | `VARCHAR(255)` | **NO** | | Secure Bcrypt hash (`PASSWORD_BCRYPT`). |
| `role` | `VARCHAR(20)` | **NO** | `CHECK (role IN ('administrator', 'coordinator', 'staff'))` | User permission tier. |
| `is_active` | `BOOLEAN` | **NO** | `DEFAULT TRUE` | Active status; `false` denies sign-in. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Account creation timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Last profile update timestamp. |

---

### 2.2. `categories`
Classification grouping for physical inventory assets.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Category identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Category title (e.g., Computing, Photography). |
| `description` | `TEXT` | **YES** | | Contextual summary of items in category. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

---

### 2.3. `locations`
Physical storage buildings and room locations.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Location identifier. |
| `name` | `VARCHAR(100)` | **NO** | `UNIQUE` | Location name (e.g., Main Store, Media Studio). |
| `building` | `VARCHAR(100)` | **YES** | | Facility or campus building. |
| `room` | `VARCHAR(50)` | **YES** | | Room or shelf reference number. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration timestamp. |

---

### 2.4. `equipment`
Catalogue of physical devices, instruments, and equipment assets.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Asset identifier. |
| `asset_tag` | `VARCHAR(50)` | **NO** | `UNIQUE` | Organization barcode/tag (e.g., `EQ-1001`). |
| `name` | `VARCHAR(150)` | **NO** | | Asset title (e.g., Sony Alpha a7 IV). |
| `category_id` | `INT` | **NO** | `REFERENCES categories(id) ON DELETE RESTRICT` | Associated category. |
| `location_id` | `INT` | **NO** | `REFERENCES locations(id) ON DELETE RESTRICT` | Home storage room. |
| `model` | `VARCHAR(100)` | **YES** | | Manufacturer model number. |
| `serial_number`| `VARCHAR(100)` | **YES** | | Hardware serial number. |
| `condition` | `VARCHAR(20)` | **NO** | `CHECK (condition IN ('new', 'good', 'fair', 'damaged'))` | Physical equipment condition. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('available', 'maintenance', 'retired'))` | Circulation lifecycle state. |
| `notes` | `TEXT` | **YES** | | Inclusions, accessories, handling notes. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Registration date. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Last updated timestamp. |

---

### 2.5. `loans`
Equipment reservation, active checkout, and return transactions.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Loan reference number. |
| `asset_id` | `INT` | **NO** | `REFERENCES equipment(id) ON DELETE RESTRICT` | Requested equipment item. |
| `user_id` | `INT` | **NO** | `REFERENCES users(id) ON DELETE RESTRICT` | Borrower (staff member). |
| `start_date` | `DATE` | **NO** | | Requested checkout start date. |
| `end_date` | `DATE` | **NO** | `CONSTRAINT check_dates CHECK (end_date >= start_date)` | Scheduled return due date. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('pending', 'approved', 'checked_out', 'returned', 'rejected', 'cancelled'))` | Lifecycle state. |
| `purpose` | `TEXT` | **NO** | | Fieldwork / research justification. |
| `rejection_reason`| `TEXT` | **YES** | | Coordinator decline reason (if rejected). |
| `approved_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Reviewing coordinator. |
| `approved_at` | `TIMESTAMPTZ` | **YES** | | Decision timestamp. |
| `checked_out_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Handover coordinator. |
| `checked_out_at` | `TIMESTAMPTZ` | **YES** | | Handover timestamp. |
| `returned_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Receiving coordinator. |
| `returned_at` | `TIMESTAMPTZ` | **YES** | | Return receipt timestamp. |
| `return_condition`| `VARCHAR(20)` | **YES** | `CHECK (return_condition IN ('new', 'good', 'fair', 'damaged'))` | Verified condition upon return. |
| `return_notes` | `TEXT` | **YES** | | Inspection notes on return. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Request submission timestamp. |
| `updated_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Status change timestamp. |

---

### 2.6. `loan_history`
Immutable event audit log tracking each transition in a loan's lifecycle.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | History event identifier. |
| `loan_id` | `INT` | **NO** | `REFERENCES loans(id) ON DELETE RESTRICT` | Parent loan. |
| `action` | `VARCHAR(50)` | **NO** | | Action name (`requested`, `approved`, `rejected`, `checked_out`, `returned`, `cancelled`). |
| `performed_by`| `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | User performing the transition. |
| `notes` | `TEXT` | **YES** | | Transition comment or reason. |
| `created_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Immutable event timestamp. |

---

### 2.7. `maintenance`
Repair, damage report, and servicing tracker for equipment.

| Column | Data Type | Nullable | Constraints & Defaults | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `SERIAL` | **NO** | `PRIMARY KEY` | Ticket reference number. |
| `asset_id` | `INT` | **NO** | `REFERENCES equipment(id) ON DELETE RESTRICT` | Equipment under maintenance. |
| `opened_by` | `INT` | **NO** | `REFERENCES users(id) ON DELETE RESTRICT` | Coordinator reporting the issue. |
| `closed_by` | `INT` | **YES** | `REFERENCES users(id) ON DELETE SET NULL` | Coordinator certifying completion. |
| `issue_description` | `TEXT` | **NO** | | Defect or damage description. |
| `resolution_notes` | `TEXT` | **YES** | | Repair details and testing outcome. |
| `restored_condition` | `VARCHAR(20)`| **YES** | `CHECK (restored_condition IN ('new', 'good', 'fair'))` | Condition restored upon closure. |
| `status` | `VARCHAR(20)` | **NO** | `CHECK (status IN ('open', 'resolved', 'cancelled')) DEFAULT 'open'` | Ticket state. |
| `opened_at` | `TIMESTAMPTZ` | **YES** | `DEFAULT CURRENT_TIMESTAMP` | Ticket opened timestamp. |
| `closed_at` | `TIMESTAMPTZ` | **YES** | | Ticket resolution timestamp. |

---

## 3. State Transitions & Business Rules

### 3.1. Loan State Machine
```
               [Request]
                   │
                   v
              [ PENDING ] ────────┐
                   │              │ (Reject)
         (Approve) │              v
                   v         [ REJECTED ] (Final)
              [ APPROVED ]
                   │      \
     (Checkout at  │       \ (Cancel by borrower/coord)
      start date)  │        v
                   v    [ CANCELLED ] (Final)
            [ CHECKED_OUT ]
                   │
         (Return)  │
                   v
             [ RETURNED ] (Final)
```
- **Terminal States:** `rejected`, `cancelled`, and `returned` are immutable end-states.
- **Cancellation:** The borrower or coordinator may cancel a loan in `pending` or `approved` state prior to physical checkout.

### 3.2. Equipment Availability
- Availability is **derived dynamically** from:
  1. `condition != 'damaged'`
  2. `status == 'available'` (not `maintenance` or `retired`)
  3. No open maintenance tickets (`maintenance.status = 'open'`)
  4. No overlapping `approved` or `checked_out` loans during the requested inclusive calendar dates
  5. No currently overdue loans on the item
- Overdue status is computed dynamically: `status = 'checked_out' AND end_date < CURRENT_DATE`.

### 3.3. Database Concurrency Control
All availability-altering actions (`request`, `approve`, `checkout`, `return`, `openMaintenance`, `closeMaintenance`) execute inside a transaction wrapping a PostgreSQL row-level lock:
```sql
SELECT * FROM equipment WHERE id = :id FOR UPDATE;
```
This guarantees that concurrent requests or simultaneous coordinator approvals cannot produce duplicate or overlapping loans.

