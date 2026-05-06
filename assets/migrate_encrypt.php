<?php
// ═══════════════════════════════════════════════════════════════════════════
// ENCRYPTION MIGRATION SCRIPT — Run once from the browser or CLI.
//
// PREREQUISITES:
//   1. Run other/migration_step1.sql first (adds _enc staging columns,
//      drops warning_threshold).
//   2. Deploy the updated database.php with ENCRYPTION_KEY defined.
//
// AFTER this script completes successfully:
//   Run other/migration_step2.sql to swap the columns.
//
// IMPORTANT: Delete or move this file off the server after use.
// ═══════════════════════════════════════════════════════════════════════════

// Simple protection — remove this block after testing in a safe environment
$auth_token = 'MIGRATE_2026_ENCRYPT_NOW';
if (!isset($_GET['token']) || $_GET['token'] !== $auth_token) {
    http_response_code(403);
    exit('Access denied. Append ?token=MIGRATE_2026_ENCRYPT_NOW to the URL to run.');
}

require_once __DIR__ . '/database.php'; // provides $savienojums + encrypt_value()

header('Content-Type: text/plain; charset=utf-8');

$errors   = [];
$migrated = ['transactions_amount' => 0, 'transactions_desc' => 0, 'budgets_name' => 0, 'budgets_amount' => 0];

// ─── Verify staging columns exist ────────────────────────────────────────────
$checks = [
    ['BU_transactions', 'amount_enc'],
    ['BU_transactions', 'description_enc'],
    ['BU_budgets',      'budget_name_enc'],
    ['BU_budgets',      'budget_amount_enc'],
];
foreach ($checks as [$table, $col]) {
    $r = mysqli_query($savienojums, "SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    if (!$r || mysqli_num_rows($r) === 0) {
        die("ERROR: Column `{$table}`.`{$col}` does not exist.\nPlease run migration_step1.sql first.\n");
    }
}
echo "✓ Staging columns verified.\n\n";

// ─── 1. Encrypt BU_transactions.amount ───────────────────────────────────────
echo "Encrypting BU_transactions.amount …\n";
$res = mysqli_query($savienojums, "SELECT id, amount FROM BU_transactions WHERE amount_enc IS NULL");
if (!$res) { die('Query failed: ' . mysqli_error($savienojums) . "\n"); }

while ($row = mysqli_fetch_assoc($res)) {
    $enc  = encrypt_value(strval(floatval($row['amount'])));
    $stmt = mysqli_prepare($savienojums, "UPDATE BU_transactions SET amount_enc = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $enc, $row['id']);
    if (mysqli_stmt_execute($stmt)) {
        $migrated['transactions_amount']++;
    } else {
        $errors[] = "TX amount id={$row['id']}: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
echo "  → {$migrated['transactions_amount']} rows encrypted.\n\n";

// ─── 2. Encrypt BU_transactions.description ──────────────────────────────────
echo "Encrypting BU_transactions.description …\n";
$res = mysqli_query($savienojums, "SELECT id, description FROM BU_transactions WHERE description_enc IS NULL");
if (!$res) { die('Query failed: ' . mysqli_error($savienojums) . "\n"); }

while ($row = mysqli_fetch_assoc($res)) {
    $enc  = encrypt_value($row['description'] ?? '');
    $stmt = mysqli_prepare($savienojums, "UPDATE BU_transactions SET description_enc = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $enc, $row['id']);
    if (mysqli_stmt_execute($stmt)) {
        $migrated['transactions_desc']++;
    } else {
        $errors[] = "TX description id={$row['id']}: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
echo "  → {$migrated['transactions_desc']} rows encrypted.\n\n";

// ─── 3. Encrypt BU_budgets.budget_name ───────────────────────────────────────
echo "Encrypting BU_budgets.budget_name …\n";
$res = mysqli_query($savienojums, "SELECT id, budget_name FROM BU_budgets WHERE budget_name_enc IS NULL");
if (!$res) { die('Query failed: ' . mysqli_error($savienojums) . "\n"); }

while ($row = mysqli_fetch_assoc($res)) {
    $enc  = encrypt_value($row['budget_name'] ?? '');
    $stmt = mysqli_prepare($savienojums, "UPDATE BU_budgets SET budget_name_enc = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $enc, $row['id']);
    if (mysqli_stmt_execute($stmt)) {
        $migrated['budgets_name']++;
    } else {
        $errors[] = "Budget name id={$row['id']}: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
echo "  → {$migrated['budgets_name']} rows encrypted.\n\n";

// ─── 4. Encrypt BU_budgets.budget_amount ─────────────────────────────────────
echo "Encrypting BU_budgets.budget_amount …\n";
$res = mysqli_query($savienojums, "SELECT id, budget_amount FROM BU_budgets WHERE budget_amount_enc IS NULL");
if (!$res) { die('Query failed: ' . mysqli_error($savienojums) . "\n"); }

while ($row = mysqli_fetch_assoc($res)) {
    $enc  = encrypt_value(strval(floatval($row['budget_amount'])));
    $stmt = mysqli_prepare($savienojums, "UPDATE BU_budgets SET budget_amount_enc = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $enc, $row['id']);
    if (mysqli_stmt_execute($stmt)) {
        $migrated['budgets_amount']++;
    } else {
        $errors[] = "Budget amount id={$row['id']}: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
echo "  → {$migrated['budgets_amount']} rows encrypted.\n\n";

// ─── Summary ──────────────────────────────────────────────────────────────────
echo "═══════════════════════════════════\n";
echo "MIGRATION COMPLETE\n";
echo "  BU_transactions.amount      : {$migrated['transactions_amount']} rows\n";
echo "  BU_transactions.description : {$migrated['transactions_desc']} rows\n";
echo "  BU_budgets.budget_name      : {$migrated['budgets_name']} rows\n";
echo "  BU_budgets.budget_amount    : {$migrated['budgets_amount']} rows\n";

if (!empty($errors)) {
    echo "\nERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) { echo "  • {$e}\n"; }
    echo "\nFix the errors above before running migration_step2.sql!\n";
} else {
    echo "\nNo errors. You can now run migration_step2.sql.\n";
    echo "Then DELETE this file from the server.\n";
}
