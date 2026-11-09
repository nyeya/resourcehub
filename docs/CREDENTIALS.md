# ResourceHub &mdash; Demo Accounts &amp; Credentials

This document details the demonstration personas, credentials, and testing responsibilities pre-seeded into ResourceHub, satisfying the **Section 9** repeatable demonstration dataset requirement.

---

## 1. Master Demonstration Credentials

All fictional test accounts share a single standardized password:  
**`Password123!`**

Password hashes are generated using native PHP Bcrypt (`password_hash($password, PASSWORD_BCRYPT)`). No plaintext passwords or sensitive customer credentials are stored in code or committed to version control.

---

## 2. Seeded Account Matrix

| Name | Email Address | Role | Account Status | Key Testing Scenarios &amp; Seeded Data |
| :--- | :--- | :--- | :---: | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | **Active** | **User Administration:** Provision new accounts, assign roles, reset passwords, deactivate accounts. Verifies that administrators cannot deactivate themselves or remove the last active administrator. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | **Active** | **Inventory &amp; Operations:** Equipment catalogue CRUD, loan approval/rejection with mandatory notes, physical checkout handover, physical return condition logging, and maintenance ticket lifecycle. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | **Active** | **Borrower / Overdue Testing:** Catalogue searching, date-bounded loan requests, tracking personal loans. Holds an active overdue loan on Dell Latitude (`EQ-1001`), demonstrating overdue badge alerts and checkout blocking. |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | **Active** | **Borrower / Conflict Testing:** Holds an active checkout (`EQ-1002`) and a conflicting pending reservation request on `EQ-1007` to test boundary-day overlap detection. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | **Active** | **Borrower / Damage History:** Holds an approved future reservation (`EQ-1003`) and past loan history for a returned damaged Sony Alpha camera (`EQ-1004`). |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | **Inactive** | **Authentication Denial:** Verification of account deactivation. The system refuses authentication upon sign-in attempt and displays an access alert. |

---

## 3. One-Click Quick-Fill Authentication Feature

For fast grading, rehearsals, and demonstrations without manual typing:
1. Open the application sign-in page: `http://localhost/login.php` (or `http://127.0.0.1:8000/login.php`).
2. Under the login form, click any of the **Demo Account Tiles**:
   - **Administrator:** Automatically fills `admin@resourcehub.local` and `Password123!`.
   - **Coordinator:** Automatically fills `coordinator@resourcehub.local` and `Password123!`.
   - **Staff:** Automatically fills `staff1@resourcehub.local` and `Password123!`.
3. Click **Sign In** to immediately access the respective dashboard.

---

## 4. Resetting the Demonstration Dataset

To restore all demonstration accounts, passwords, and loan states to their initial known baseline at any time:

```bash
php database/reset.php
```

All dates are generated dynamically relative to execution time so that demonstration scenarios, overdue checks, and active reservations never expire.
