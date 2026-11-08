<?php
require_once __DIR__ . '/../includes/init.php';

echo 'Testing Equipment class... ';
$eq = new Equipment();
$cats = $eq->getCategories();
$locs = $eq->getLocations();
$items = $eq->getAll();
$single = $eq->getById(1);
assert(!empty($cats), 'Categories empty');
assert(!empty($locs), 'Locations empty');
assert(!empty($items), 'Items empty');
assert((int)$single['id'] === 1, 'Single item mismatch');
echo "OK\n";

echo 'Testing Loan class... ';
$ls = new Loan();
$loans = $ls->getAll();
$loansLimited = $ls->getAll([], null, 3);
assert(count($loansLimited) === 3, 'Limit failed on loans');
$singleLoan = $ls->getById(1);
assert((int)$singleLoan['id'] === 1, 'Single loan mismatch');
$hist = $ls->getHistory(1);
assert(is_array($hist), 'History failed');
echo "OK\n";

echo 'Testing Maintenance class... ';
$ms = new Maintenance();
$mList = $ms->getAll();
$mOpen = $ms->getAll('open');
assert(is_array($mList), 'Maintenance list failed');
assert(count($mOpen) >= 1, 'Maintenance open list failed');
echo "OK\n";

echo 'Testing Report class... ';
$rs = new Report();
$metrics = $rs->getSummaryMetrics();
assert(isset($metrics['by_category']), 'Metrics missing category');
assert(isset($metrics['loan_overview']), 'Metrics missing loan_overview');
echo "OK\n";

echo 'Testing User class... ';
$us = new User();
$users = $us->getAll();
assert(count($users) >= 3, 'Users count failed');
$adminCount = $us->countActiveAdministrators();
assert($adminCount >= 1, 'Admin count failed');
echo "OK\n";

echo 'Testing Helpers... ';
assert(format_date('2026-11-05') === '05 Nov 2026', 'format_date failed');
assert(format_datetime('2026-11-05 14:30:00') === '05 Nov 2026, 14:30', 'format_datetime failed');
echo "OK\n";

echo "ALL COMPREHENSIVE CHECKS PASSED!\n";
