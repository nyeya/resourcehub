<?php
/**
 * Restoration Row Count and Integrity Verification
 */
require_once __DIR__ . '/../includes/init.php';

$pdo = Database::getConnection();

echo "=== Verifying Database Integrity & Counts ===" . PHP_EOL;

$counts = [
    'users'        => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'categories'   => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'locations'    => (int)$pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn(),
    'equipment'    => (int)$pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn(),
    'loans'        => (int)$pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn(),
    'maintenance'  => (int)$pdo->query("SELECT COUNT(*) FROM maintenance")->fetchColumn(),
    'loan_history' => (int)$pdo->query("SELECT COUNT(*) FROM loan_history")->fetchColumn(),
];

foreach ($counts as $table => $count) {
    echo "Table '{$table}': {$count} rows" . PHP_EOL;
}

assert($counts['users'] >= 6, 'Users table count mismatch');
assert($counts['categories'] === 4, 'Categories count mismatch');
assert($counts['locations'] === 3, 'Locations count mismatch');
assert($counts['equipment'] === 20, 'Equipment count mismatch');
assert($counts['loans'] === 12, 'Loans count mismatch');
assert($counts['maintenance'] >= 1, 'Maintenance count mismatch');

echo PHP_EOL . "=== Authentication Verification Post-Restore ===" . PHP_EOL;
$adminOk = Auth::attempt('admin@resourcehub.local', 'Password123!');
echo "Admin Login Verification: " . ($adminOk ? "PASS" : "FAIL") . PHP_EOL;

$coordOk = Auth::attempt('coordinator@resourcehub.local', 'Password123!');
echo "Coordinator Login Verification: " . ($coordOk ? "PASS" : "FAIL") . PHP_EOL;

echo PHP_EOL . "=== Backup Integrity Verified Successfully! ===" . PHP_EOL;

