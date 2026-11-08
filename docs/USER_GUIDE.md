# ResourceHub &mdash; User Guide &amp; Technical Notes

**Application:** ResourceHub Equipment Loan &amp; Maintenance Management  
**Release:** `v1.0.0-rc1`  
**Target Roles:** Staff (`staff`), Coordinator (`coordinator`), Administrator (`administrator`)

---

## 1. Quick-Start &amp; Demo Logins

All test accounts share the common password: **`Password123!`**

| User Name | Email Address | Role | Core Testing Responsibility |
| :--- | :--- | :--- | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | User creation, role assignments, active toggle, admin protection. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | Equipment CRUD, loan approvals, checkouts, returns, maintenance. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | Item requests, viewing own loans, overdue loan demonstration. |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | Conflicting loan request testing. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | Conflicting loan request testing, returned damaged camera history. |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | Authentication denial verification (inactive account). |

---

## 2. Role Workflows

### 2.1. Staff Journey
1. **Browse Catalogue (`equipment.php`):** Search for items by name, model, or tag. Filter by category, room location, physical condition, and availability.
2. **Submit Request (`loan_request.php`):** Select available equipment, pick start and return dates (must be today or later; end date must be on or after start date), and enter justification purpose.
3. **Track Status (`loans.php`):** View submitted requests under **My Loans**. Status updates live from `pending` &rarr; `approved` &rarr; `checked_out` &rarr; `returned`.
4. **Cancel Request (`loan_view.php`):** If plans change prior to physical checkout, click **Cancel Request** or **Cancel Reservation**.

---

### 2.2. Coordinator Journey
1. **Review Loan Requests (`loans.php`):** View all organization requests under the **Pending** tab.
2. **Approve or Decline (`loan_view.php`):**
   - **Approve:** Automatically checks for inclusive date overlaps and overdue checkouts on the asset before approving.
   - **Decline:** Provide a mandatory explanation note.
3. **Physical Checkout:** When the borrower collects the item on or after the scheduled start date, click **Confirm Checkout**.
4. **Physical Return:** When the item is returned:
   - Select returned condition (`good`, `fair`, or `damaged`).
   - If marked `damaged`, the system automatically changes equipment status to `maintenance` and opens an urgent maintenance ticket.
5. **Maintenance Management (`maintenance.php`):**
   - Open repair tickets for defective equipment.
   - Resolve repair tickets with repair notes and restore condition (`new`, `good`, `fair`) to re-enable equipment in circulation.

---

### 2.3. Administrator Journey
1. **User Administration (`users.php`):**
   - Create new user accounts with roles: `staff`, `coordinator`, or `administrator`.
   - Edit user profiles and reset passwords.
   - Deactivate user accounts.
2. **Safety Guards:**
   - Administrators cannot deactivate their own active account.
   - The last remaining active administrator cannot be demoted or deactivated.

---

## 3. Technical Notes &amp; Known Limitations

### 3.1. Architectural Assumptions
- **Inclusive Dates:** Both start and due dates are inclusive. If Loan A ends on Nov 10, another loan cannot begin until Nov 11.
- **Single Asset per Request:** Each loan covers one physical inventory item to simplify tracking.
- **Zero External Dependencies:** Bootstrap 5, Bootstrap Icons, and fonts run completely offline from `assets/`.
- **Database Concurrency:** All status changes execute within PostgreSQL transactions guarded by row-level locking (`SELECT ... FOR UPDATE`).

### 3.2. Intentionally Deferred Features
As specified in Section 2 of the Project Plan, the following features are outside core scope:
- Email notifications / external mail server dependencies
- File attachments / photo uploads
- Self-registration / public sign-up
- Third-party procurement approvals or payment processing

