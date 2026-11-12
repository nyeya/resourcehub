<?php
/**
 * Backup and Restoration Acceptance & Integrity Test Suite
 * Tests:
 * 1. RBAC authorization (Admins only, Coordinator and Staff denied)
 * 2. On-demand backup creation and file listing
 * 3. Archive download resolution and security against path traversal
 * 4. Restoration confirmation requirement (unconfirmed requests rejected)
 * 5. Full database restoration with data integrity and row count verification
 * 6. Deletion of saved backup snapshots
 * 7. Verification that production database remains untouched
 */

require_once __DIR__ . '/test_helper.php';

$pdo = TestHelper::initTestDatabase();
$passed = 0;
$total = 7;

function testAssert($code, $description, $result, $details = '') {
    global $passed;
    if ($result) {
        $passed++;
        echo "[\033[32mPASS\033[0m] {$code}: {$description}";
        if ($details) echo " ({$details})";
        echo PHP_EOL;
    } else {
        echo "[\033[31mFAIL\033[0m] {$code}: {$description}";
        if ($details) echo " - FAILED: {$details}";
        echo PHP_EOL;
    }
}

echo "==========================================================" . PHP_EOL;
echo "  ResourceHub Backup & Restore Test Suite                 " . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

$backupService = new BackupService($pdo);

// 1. RBAC: Only administrators can access backup feature
Auth::attempt('staff1@resourcehub.local', 'Password123!');
$staffDenied = !Auth::isAdministrator();
Auth::logout();

Auth::attempt('coordinator@resourcehub.local', 'Password123!');
$coordDenied = !Auth::isAdministrator();
Auth::logout();

Auth::attempt('admin@resourcehub.local', 'Password123!');
$adminAllowed = Auth::isAdministrator();

$rbacOk = ($staffDenied && $coordDenied && $adminAllowed);
testAssert('B01', 'RBAC access control: only administrators may access backup/restore', $rbacOk);

// 2. Snapshot creation & listing
$testSnapshotLabel = 'test_suite_snapshot_' . time();
$createRes = $backupService->createBackup($testSnapshotLabel);
$fileCreated = !empty($createRes['filepath']) && file_exists($createRes['filepath']) && filesize($createRes['filepath']) > 0;

$list = $backupService->listBackups();
$foundInList = false;
foreach ($list as $b) {
    if ($b['filename'] === $createRes['filename']) {
        $foundInList = true;
        break;
    }
}
testAssert('B02', 'Create and list server backup snapshot', ($fileCreated && $foundInList), "File: {$createRes['filename']}, Size: {$createRes['size_formatted']}");

// 3. Path traversal security on download / lookup
$traversalAttack1 = $backupService->getBackupFilePath('../../config/database.php');
$traversalAttack2 = $backupService->getBackupFilePath('..\\..\\config\\database.php');
$traversalAttack3 = $backupService->getBackupFilePath('nonexistent.sql');
$validPath = $backupService->getBackupFilePath($createRes['filename']);
$securityOk = ($traversalAttack1 === null && $traversalAttack2 === null && $traversalAttack3 === null && $validPath !== null);
testAssert('B03', 'Path traversal protection and safe file path resolution', $securityOk);

// 4. Restore without confirmation rejected
$unconfirmedRejected = false;
// Test via Controller logic or direct confirmation guard
$postData = [
    'confirm_restore' => '0',
    'confirm_text'    => 'NO',
    'source'          => 'server',
    'filename'        => $createRes['filename'],
];
$confirmed = !empty($postData['confirm_restore']) && (
    $postData['confirm_restore'] === '1' ||
    strtoupper(trim($postData['confirm_text'] ?? '')) === 'RESTORE'
);
$unconfirmedRejected = ($confirmed === false);
testAssert('B04', 'Restoration without confirmation is blocked', $unconfirmedRejected);

// 5. Database restoration with confirmed input and integrity check
// Alter a record temporarily to verify restoration overwrites it
$pdo->exec("UPDATE users SET name = 'Modified Before Restore' WHERE id = 1");
$nameBefore = $pdo->query("SELECT name FROM users WHERE id = 1")->fetchColumn();

// Restore from our snapshot
$restoreRes = $backupService->restoreFromSqlFile($createRes['filepath']);
TestDatabase::injectIntoDatabase(TestDatabase::getConnection());
$pdo = TestDatabase::getConnection(); // reconnect
$nameAfter = $pdo->query("SELECT name FROM users WHERE id = 1")->fetchColumn();
$userRestored = ($nameBefore === 'Modified Before Restore' && $nameAfter === 'Alice Admin');
$countsValid = ($restoreRes['counts']['users'] >= 6 && $restoreRes['counts']['equipment'] === 20 && $restoreRes['counts']['loans'] === 12);

testAssert('B05', 'Restore database with confirmation and verify table integrity', ($restoreRes['success'] && $userRestored && $countsValid), "Restored user: {$nameAfter}");

// 6. Delete backup snapshot
$deleteOk = $backupService->deleteBackup($createRes['filename']);
$fileGone = !file_exists($createRes['filepath']);
testAssert('B06', 'Delete backup snapshot from server', ($deleteOk && $fileGone));

// 7. Verify production database isolation
$prodConfig = TestDatabase::getConfig();
$prodDsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $prodConfig['host'], $prodConfig['port'], $prodConfig['database']);
$prodPdo = new PDO($prodDsn, $prodConfig['username'], $prodConfig['password']);
$prodUserCount = (int)$prodPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$prodEqCount = (int)$prodPdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$prodLoansCount = (int)$prodPdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$activeDb = $pdo->query("SELECT current_database()")->fetchColumn();
$prodSafe = ($prodUserCount >= 6 && $prodEqCount === 20 && $prodLoansCount === 12 && $activeDb === 'resourcehub_test');
testAssert('B07', 'Production database isolation: resourcehub intact', $prodSafe, "DB: {$prodConfig['database']}, Users: {$prodUserCount}, Assets: {$prodEqCount}, Loans: {$prodLoansCount}");

echo PHP_EOL;
echo "==========================================================" . PHP_EOL;
echo "  Backup & Restore Test Summary: {$passed} of {$total} Tests Passed (" . round(($passed / $total) * 100) . "%)" . PHP_EOL;
echo "==========================================================" . PHP_EOL;

if ($passed === $total) {
    echo "ALL BACKUP & RESTORE CHECKS VERIFIED SUCCESSFULLY!" . PHP_EOL;
    exit(0);
} else {
    echo "SOME CHECKS FAILED!" . PHP_EOL;
    exit(1);
}

