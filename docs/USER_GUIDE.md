# ResourceHub &mdash; User Guide &amp; Technical Notes

**Application:** ResourceHub Equipment Loan &amp; Maintenance Management  
**Release Identifier:** `v1.0.0-rc1`  
**Reference Environment:** Ubuntu 26.04 LTS, PHP 8.5.12, PostgreSQL 16+ / 18.6, Apache 2.4.69+, Bootstrap 5.3.3  
**Target Roles:** Staff (`staff`), Coordinator (`coordinator`), Administrator (`administrator`)  
**Repository:** [https://github.com/nyeya/resourcehub.git](https://github.com/nyeya/resourcehub.git)

---

## 1. Quick-Start &amp; Demonstration Logins

All test accounts share the common password: **`Password123!`**

| User Name | Email Address | Role | Core Testing Responsibility |
| :--- | :--- | :--- | :--- |
| **Alice Admin** | `admin@resourcehub.local` | `administrator` | User provisioning, role assignments, active toggle, last admin protection. |
| **Charlie Coordinator** | `coordinator@resourcehub.local` | `coordinator` | Equipment inventory CRUD, loan approvals, checkouts, returns, maintenance tickets. |
| **Sarah Staff** | `staff1@resourcehub.local` | `staff` | Catalogue browsing, item requests, viewing own loans, overdue loan demonstration (`EQ-1001`). |
| **Sam Fieldwork** | `staff2@resourcehub.local` | `staff` | Active checked-out item (`EQ-1002`), conflicting loan request on `EQ-1007`. |
| **Sophia Research** | `staff3@resourcehub.local` | `staff` | Approved upcoming loan (`EQ-1003`), returned damaged camera history (`EQ-1004`). |
| **Ian Inactive** | `inactive@resourcehub.local` | `staff` | Account deactivation verification (sign-in rejected). |

> **Quick-Fill Feature:** On `public/login.php`, click any of the **Demo Account** tiles to instantly populate credentials.

---

## 2. Role-Based User Workflows

### 2.1. Staff Workflow (Borrower Journey)
1. **Browse Equipment (`equipment.php`):**
   - Search by asset tag, item name, model, or description.
   - Filter by Category, Room Location, Condition (`new`, `good`, `fair`), and real-time Availability.
2. **Submit a Loan Request (`loan_request.php`):**
   - Select an available asset.
   - Select start date (must be today or later) and return due date (must be on or after start date).
   - Enter a justifiable loan purpose (e.g., "Field trip environmental data logging").
   - System registers the request in `pending` status.
3. **Track Status (`loans.php`):**
   - View submitted requests under **My Loans**. Status badges update dynamically: `pending` &rarr; `approved` &rarr; `checked_out` &rarr; `returned`.
4. **Cancel Reservation:**
   - If plans change prior to physical checkout handover, click **Cancel Request** or **Cancel Reservation** from `loan_view.php`.
5. **Export Personal History:**
   - Staff can download a CSV record of their own loan requests via the Export button.

---

### 2.2. Coordinator Workflow (Inventory &amp; Operations Journey)
1. **Review Pending Loan Requests (`loans.php`):**
   - Access all organisation requests under the **Pending** tab.
2. **Approve or Reject Requests (`loan_view.php`):**
   - **Approve:** Evaluates date overlap and overdue status on the asset before approving. Locks the record in a PostgreSQL transaction (`SELECT ... FOR UPDATE`).
   - **Reject:** Enforces a mandatory explanation note and transitions status to `rejected`.
3. **Physical Checkout Handover:**
   - When the borrower arrives to collect the equipment on or after the scheduled start date, click **Confirm Checkout**. Status advances to `checked_out`.
4. **Physical Return Receipt &amp; Damage Triage:**
   - Upon receiving the item back, inspect condition (`good`, `fair`, or `damaged`) and record inspection notes.
   - If marked `damaged`, the system automatically changes equipment status to `maintenance` and immediately generates an urgent maintenance ticket.
   - If functional, equipment status is restored to `available`.
5. **Maintenance Management (`maintenance.php`):**
   - Log repair tickets for defective equipment.
   - Resolve open repair tickets, document technician findings, select restored condition (`new`, `good`, `fair`), and re-enable equipment for circulation.
6. **Equipment Inventory CRUD (`equipment.php`):**
   - Add new equipment assets, edit specifications, change storage locations, or retire obsolete gear.

---

### 2.3. Administrator Workflow (Governance Journey)
1. **User Administration (`users.php`):**
   - View, search, and filter all registered users.
   - Create new user accounts with designated roles (`staff`, `coordinator`, `administrator`).
   - Edit user profiles, assign roles, and reset user passwords.
   - Deactivate user accounts (`is_active = false`) to revoke system access immediately.
2. **Built-in Safety Guards:**
   - **Self-Deactivation Prevention:** Administrators cannot deactivate their own active session.
   - **Last Active Admin Guard:** The system rejects any attempt to deactivate, delete, or demote the last remaining active administrator.

---

## 3. Core Business Rules &amp; Concurrency Control

1. **Inclusive Calendar Boundaries:**
   - Both start and return dates are inclusive. If Loan A runs from Nov 1 to Nov 10, Loan B cannot start until Nov 11 (`l.start_date <= :end_date AND l.end_date >= :start_date`).
2. **Transactional Concurrency Locking:**
   - Availability-critical operations lock the equipment record exclusively:
     ```sql
     SELECT * FROM equipment WHERE id = :id FOR UPDATE;
     ```
   - Concurrent approval attempts on overlapping requests are serialized; exactly one commits while the second is rejected with a conflict error.
3. **Overdue Checkout Blocking:**
   - Any asset currently checked out with `end_date < CURRENT_DATE` is flagged overdue. The system blocks new approvals and checkouts on that asset until physically returned.
4. **Maintenance Blocking:**
   - Defective, retired, or maintenance-ticketed assets cannot be requested or approved.
   - Maintenance tickets cannot be opened on items currently checked out; physical return must be recorded first.
5. **CSV Formula Injection Sanitization:**
   - Dynamic exports prepend a single quote (`'`) to any cell beginning with `=`, `+`, `-`, or `@` to neutralize spreadsheet formula execution.

---

## 4. End-to-End Success Scenario Walkthrough

Follow this step-by-step path to reproduce the core project plan workflow:

1. **Sign in as Staff:** Use quick-fill to sign in as **Sarah Staff** (`staff1@resourcehub.local`).
2. **Find &amp; Request Equipment:** Navigate to **Equipment Catalogue**, find a camera (e.g. `EQ-1005`), and submit a loan request for upcoming fieldwork.
3. **Sign in as Coordinator:** Switch accounts using quick-fill to **Charlie Coordinator** (`coordinator@resourcehub.local`).
4. **Approve Loan:** Open **Loans**, review the request in **Pending**, and click **Approve Request**.
5. **Execute Physical Checkout:** When collection date arrives, click **Confirm Checkout**. The loan moves to `checked_out`.
6. **Record Damaged Return:** Click **Record Return**, select condition **Damaged**, and enter inspection notes: *"Dropped in field, lens cracked."*
7. **Verify Automated Maintenance Triage:**
   - The loan completes as `returned`.
   - The camera status immediately switches to `maintenance`.
   - An open maintenance ticket is automatically generated on `maintenance.php`.
   - Any new loan request for this camera is blocked.
8. **Resolve Maintenance:** On `maintenance.php`, click **Resolve Ticket**, enter repair details (*"Replaced front lens element"*), select restored condition **Good**, and save. The camera returns to `available` status.
9. **Verify Audit Trail:** Open the loan details to confirm that every action (`requested`, `approved`, `checked_out`, `returned`) is permanently recorded in the audit log.

---

## 5. Technical Notes &amp; Known Limitations

### Architectural Assumptions
- **Single Asset per Request:** Each loan covers one physical inventory asset to ensure clear custody.
- **Zero External Dependencies:** Bootstrap 5.3.3 and Icons 1.11.3 are served 100% offline from `public/assets/`.
- **Relational Integrity:** Foreign keys use `ON DELETE RESTRICT` on primary assets to preserve immutable audit trails.

### Intentionally Deferred Features
As documented in Section 2 of the internship plan:
- **Email Notifications / SMTP Services:** Excluded to ensure standalone offline functionality without mail server dependencies.
- **File &amp; Image Uploads:** Excluded to prevent file system bloat; physical asset tags and manufacturer serials serve as verified identifiers.
- **Public Self-Registration:** All users are provisioned by administrators to uphold institutional access governance.
- **Procurement &amp; Payment Processing:** Asset purchasing and finance workflows remain handled by external organizational enterprise systems.
