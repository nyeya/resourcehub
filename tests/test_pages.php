<?php
require_once __DIR__ . '/../includes/init.php';

// 1. Test Login
$ok = Auth::attempt('admin@resourcehub.local', 'Password123!');
assert($ok === true, 'Admin login failed');
assert(Auth::check() === true, 'Auth check failed');
assert(Auth::name() === 'Alice Admin', 'Auth name mismatch');
assert(Auth::role() === 'administrator', 'Auth role mismatch');
assert(Auth::isAdministrator() === true, 'Auth isAdministrator failed');
assert(Auth::isCoordinator() === true, 'Auth isCoordinator failed');
echo "Auth verification: PASS\n";

// 2. Test Equipment Class
$eq = new Equipment();
$items = $eq->getAll();
assert(count($items) === 20, 'Expected 20 items');
$first = $eq->getById(1);
assert(!empty($first), 'Item 1 not found');
echo "Equipment verification: PASS\n";

// 3. Test Loan Class
$loan = new Loan();
$allLoans = $loan->getAll();
assert(count($allLoans) === 12, 'Expected 12 loans');
$loan1 = $loan->getById(1);
assert(!empty($loan1), 'Loan 1 not found');
$hist = $loan->getHistory(1);
assert(!empty($hist), 'Loan 1 history empty');
echo "Loan verification: PASS\n";

// 4. Test Maintenance Class
$maint = new Maintenance();
$mList = $maint->getAll();
assert(!empty($mList), 'Maintenance list empty');
echo "Maintenance verification: PASS\n";

// 5. Test User Class
$usr = new User();
$uList = $usr->getAll();
assert(count($uList) === 6, 'Expected 6 users');
assert($usr->countActiveAdministrators() === 1, 'Expected 1 active admin');
echo "User verification: PASS\n";

// 6. Test Report Class
$rep = new Report();
$summary = $rep->getSummaryMetrics();
assert(isset($summary['by_category']), 'Missing by_category in metrics');
assert(isset($summary['loans_by_status']), 'Missing loans_by_status in metrics');
echo "Report verification: PASS\n";

// 7. Test URL Helper mapping (Clean path URLs without page query parameters)
assert(url('dashboard') === 'dashboard', 'url dashboard failed');
assert(url('equipment') === 'equipment', 'url equipment failed');
assert(url('equipment', ['action' => 'create']) === 'equipment/create', 'url equipment create failed');
assert(url('equipment', ['action' => 'edit', 'id' => 2]) === 'equipment/2/edit', 'url equipment edit failed');
assert(url('equipment', ['action' => 'view', 'id' => 2]) === 'equipment/2', 'url equipment view failed');
assert(url('loans', ['action' => 'request']) === 'loans/request', 'url loan request failed');
assert(url('loans', ['action' => 'view', 'id' => 5]) === 'loans/5', 'url loan view failed');
assert(url('maintenance', ['action' => 'open']) === 'maintenance/open', 'url maintenance open failed');
assert(url('users', ['action' => 'create']) === 'users/create', 'url user create failed');
assert(url('users', ['action' => 'edit', 'id' => 3]) === 'users/3/edit', 'url user edit failed');
echo "URL routing helper: PASS\n";

echo "\nALL FUNCTIONAL AND ARCHITECTURAL TESTS PASSED!\n";

