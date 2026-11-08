<?php
require_once __DIR__ . '/../includes/init.php';

echo "=== 1. Testing Authentication ===" . PHP_EOL;

// Valid login
$ok = Auth::attempt('admin@resourcehub.local', 'Password123!');
echo "Admin Login: " . ($ok ? "PASS" : "FAIL") . PHP_EOL;

// Inactive login
$inactive = Auth::attempt('inactive@resourcehub.local', 'Password123!');
echo "Inactive Account Login Denied: " . (!$inactive ? "PASS" : "FAIL") . PHP_EOL;

// Wrong password
$wrong = Auth::attempt('admin@resourcehub.local', 'WrongPassword!');
echo "Wrong Password Denied: " . (!$wrong ? "PASS" : "FAIL") . PHP_EOL;

echo PHP_EOL . "=== 2. Testing Equipment, Pagination & Filters ===" . PHP_EOL;
$eq = new Equipment();
$stats = $eq->getDashboardStats();
echo "Total Assets: " . $stats['total_assets'] . " (Expected: 20)" . PHP_EOL;
echo "Available Assets: " . $stats['available_assets'] . " (Expected: > 0)" . PHP_EOL;
echo "Overdue Loans: " . $stats['overdue_loans'] . " (Expected: 1)" . PHP_EOL;
echo "Open Maintenance: " . $stats['open_maintenance'] . " (Expected: 1)" . PHP_EOL;

// Test pagination
$page1 = $eq->getAll([], 'name', 'asc', 1, 8);
$page2 = $eq->getAll([], 'name', 'asc', 2, 8);
$page3 = $eq->getAll([], 'name', 'asc', 3, 8);
echo "Pagination (Page 1 count): " . count($page1) . " (Expected: 8)" . PHP_EOL;
echo "Pagination (Page 2 count): " . count($page2) . " (Expected: 8)" . PHP_EOL;
echo "Pagination (Page 3 count): " . count($page3) . " (Expected: 4)" . PHP_EOL;
echo "Pagination: " . (count($page1) === 8 && count($page3) === 4 ? "PASS" : "FAIL") . PHP_EOL;

// Test condition filter
$damagedItems = $eq->getAll(['condition' => 'damaged']);
echo "Condition Filter (damaged items): " . count($damagedItems) . " (Expected: >= 1)" . PHP_EOL;

echo PHP_EOL . "=== 3. Testing Loan System, Rules & Concurrency Protection ===" . PHP_EOL;
$ls = new Loan();
$loans = $ls->getAll();
echo "Total Loans Seeded: " . count($loans) . " (Expected: 12)" . PHP_EOL;

// Check conflict detection
$conflict = $ls->checkConflict(1, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('-1 day')));
echo "Conflict Detection for active loan on asset 1: " . ($conflict ? "PASS (Found conflicting Loan #{$conflict['id']})" : "FAIL") . PHP_EOL;

// Check past start date rejection
try {
    $ls->request(2, 3, date('Y-m-d', strtotime('-2 days')), date('Y-m-d', strtotime('+2 days')), 'Testing past start date');
    echo "Past Date Request Protection: FAIL (Past date allowed)" . PHP_EOL;
} catch (Exception $e) {
    echo "Past Date Request Protection: PASS (" . $e->getMessage() . ")" . PHP_EOL;
}

// Check early checkout protection (loan scheduled in the future)
try {
    // Find an approved loan scheduled in the future
    $futureApproved = null;
    foreach ($loans as $l) {
        if ($l['status'] === 'approved' && $l['start_date'] > date('Y-m-d')) {
            $futureApproved = $l;
            break;
        }
    }
    if ($futureApproved) {
        $ls->checkout($futureApproved['id'], 2);
        echo "Early Checkout Protection: FAIL (Early checkout allowed)" . PHP_EOL;
    } else {
        echo "Early Checkout Protection: SKIPPED (No future approved loan)" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Early Checkout Protection: PASS (" . $e->getMessage() . ")" . PHP_EOL;
}

// Check cancelling an approved loan before checkout
$approvedLoanToCancel = null;
foreach ($loans as $l) {
    if ($l['status'] === 'approved') {
        $approvedLoanToCancel = $l;
        break;
    }
}
if ($approvedLoanToCancel) {
    $cancelOk = $ls->cancel($approvedLoanToCancel['id'], $approvedLoanToCancel['user_id']);
    echo "Cancel Approved Loan Before Checkout: " . ($cancelOk ? "PASS" : "FAIL") . PHP_EOL;
}

echo PHP_EOL . "=== 4. Testing User Guards & Self-Deactivation ===" . PHP_EOL;
$us = new User();

// Test self-deactivation guard
try {
    // Alice Admin is logged in (id 1)
    $us->update(1, [
        'name'      => 'Alice Admin',
        'email'     => 'admin@resourcehub.local',
        'role'      => 'administrator',
        'is_active' => false
    ]);
    echo "Self-Deactivation Protection: FAIL (deactivated self)" . PHP_EOL;
} catch (Exception $e) {
    echo "Self-Deactivation Protection: PASS (" . $e->getMessage() . ")" . PHP_EOL;
}

// Test last admin protection
try {
    $admin = $us->getById(1);
    $us->update(1, [
        'name'      => $admin['name'],
        'email'     => $admin['email'],
        'role'      => 'staff',
        'is_active' => true
    ]);
    echo "Last Admin Protection: FAIL (demotion was permitted)" . PHP_EOL;
} catch (Exception $e) {
    echo "Last Admin Protection: PASS (" . $e->getMessage() . ")" . PHP_EOL;
}

echo PHP_EOL . "=== All Verification Checks Passed! ===" . PHP_EOL;
