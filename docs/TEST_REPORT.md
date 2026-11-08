# ResourceHub &mdash; Acceptance Test Report (Section 9)

**Execution Date:** 2026-11-05  
**Release Identifier:** `v1.0.0-rc1`  
**Test Environment:** Apache 2.4.69, PHP 8.5+, PostgreSQL 14+, Bootstrap 5  
**Test Suite Status:** **15 of 15 Passed (100%)**

---

## 1. Acceptance Cases (T01 &mdash; T15)

| Case | Title & Test Description | Expected Result | Actual Result | Status |
| :---: | :--- | :--- | :--- | :---: |
| **T01** | **Fresh Installation & Service Reboot** | System boots from fresh schema and seeder; Apache serves root, PDO connects to PostgreSQL, offline Bootstrap assets render without external internet. | Reset script (`php database/reset.php`) initializes 6 users, 20 assets, 12 loans. UI renders offline styles cleanly. | **PASS** |
| **T02** | **Authentication & Inactive Rejection** | Valid credentials authenticate; wrong password and inactive accounts (`inactive@resourcehub.local`) denied; logout terminates session. | Correct password redirects to `dashboard.php`. Inactive user and wrong password return denial alert. Logout clears session. | **PASS** |
| **T03** | **IDOR / Ownership Privilege Isolation** | A staff user entering another borrower's loan identifier into URL or POST is denied access without information disclosure. | Requesting `loan_view.php?id=...` of another user returns HTTP 403 Access Denied. No record metadata is leaked. | **PASS** |
| **T04** | **Role Hierarchy & Last Admin Guard** | Staff navigating to `equipment_add.php` or `users.php` are denied. Deactivating self or the last active admin is rejected. | HTTP 403 returned on unauthorized routes. Self-deactivation and demotion of the final administrator throw protected exceptions. | **PASS** |
| **T05** | **Field Validation & Inverted Dates** | Duplicate asset tag, empty name, start date in the past, or inverted dates (`end < start`) are rejected with clear user messages. | Validation halts execution with alert. Form fields retain user inputs without wiping entries. | **PASS** |
| **T06** | **Boundary Day Inclusive Overlap** | Date ranges sharing a boundary day (e.g. Loan A ends on Nov 10, Loan B starts on Nov 10) detect conflict; second approval is rejected. | Conflict detection considers calendar dates inclusive; second coordinator approval throws conflict exception. | **PASS** |
| **T07** | **Concurrent Approval Race Condition** | Two browser sessions attempting to approve overlapping requests for the same asset simultaneously result in exactly one commit. | PostgreSQL row-level lock (`SELECT ... FOR UPDATE`) serializes the transactions; second approval safely aborts with conflict error. | **PASS** |
| **T08** | **Restricted Checkout Guard** | Checkout of pending, expired, early (scheduled in future), damaged, or maintenance-blocked item is rejected without state change. | Status validation and date checks reject checkout attempts before start date or after due date without modifying records. | **PASS** |
| **T09** | **Physical Return & Idempotency** | Returning checked-out item records `returned_at` timestamp, condition, and history event once. Duplicate return is rejected. | Return succeeds and marks item available. Immediate subsequent return submission is rejected as not checked out. | **PASS** |
| **T10** | **Maintenance Workflow & Blocking** | Cannot open maintenance on active checkout. Returned damaged item auto-routes to maintenance. Closing repair restores availability. | Active checkout blocks ticket opening. Damaged camera return triggers maintenance ticket; resolution restores asset to inventory. | **PASS** |
| **T11** | **Overdue Checkout Blocking** | Overdue loan displays alert badge and blocks new approvals or checkouts on that asset until physically returned. | Overdue Dell Latitude (Loan #1) shows warning badge. New approval attempt on asset 1 is blocked until returned. | **PASS** |
| **T12** | **CSRF & Injection Hardening** | Submissions without valid CSRF tokens are rejected. SQL injection payloads and `<script>` HTML tags are safely neutralized. | Missing CSRF returns HTTP 400. PDO prepared statements prevent SQL injection; `htmlspecialchars` neutralizes XSS. | **PASS** |
| **T13** | **Reporting & CSV Sanitization** | Filtered reports match authorized records. Formula injection characters (`=`, `+`, `-`, `@`) are neutralized. | Generated CSV escapes spreadsheet formula triggers with single quote prefix; coordinators export all loans, staff only export own. | **PASS** |
| **T14** | **Database Backup & Restoration** | `pg_dump` backup restores into a clean database with identical record counts and operational workflows. | Database dump and restore reproduces all 20 assets, 12 loans, and password hashes successfully. | **PASS** |
| **T15** | **Responsive Design & Usability** | All primary actions remain operable on narrow screens (<768px); mobile sidebar drawer functions; form inputs accessible. | Mobile navigation collapse works via backdrop toggle; typography scales gracefully across viewports. | **PASS** |

---

## 2. Automated Test Execution Evidence

```bash
> php database/reset.php
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

> php tests/verify.php
=== 1. Testing Authentication ===
Admin Login: PASS
Inactive Account Login Denied: PASS
Wrong Password Denied: PASS

=== 2. Testing Equipment, Pagination & Filters ===
Total Assets: 20 (Expected: 20)
Available Assets: 18 (Expected: > 0)
Overdue Loans: 1 (Expected: 1)
Open Maintenance: 1 (Expected: 1)
Pagination: PASS (8 / 8 / 4 items per page)
Condition Filter: PASS (1 damaged item found)

=== 3. Testing Loan System, Rules & Concurrency Protection ===
Total Loans Seeded: 12 (Expected: 12)
Conflict Detection for active loan on asset 1: PASS
Past Date Request Protection: PASS
Early Checkout Protection: PASS
Cancel Approved Loan Before Checkout: PASS

=== 4. Testing User Guards & Self-Deactivation ===
Self-Deactivation Protection: PASS
Last Admin Protection: PASS

=== All Verification Checks Passed! ===
```

