<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit();
}
require_once('../../assets/database.php');
require_once('../../assets/auth_check.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit();
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'no_file']);
    exit();
}

$original_name = $_FILES['csv_file']['name'] ?? '';
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'error' => 'invalid_type']);
    exit();
}

$mime = $_FILES['csv_file']['type'] ?? '';
$allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
if (!in_array($mime, $allowed_mimes, true) && strpos($mime, 'text/') !== 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_type']);
    exit();
}

$tmp = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($tmp)) {
    echo json_encode(['success' => false, 'error' => 'upload_error']);
    exit();
}

$handle = fopen($tmp, 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'error' => 'read_error']);
    exit();
}

// Strip UTF-8 BOM if present
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$header = fgetcsv($handle);
if (!$header) {
    fclose($handle);
    echo json_encode(['success' => false, 'error' => 'empty_file']);
    exit();
}

$header = array_map('trim', $header);
$required = ['budget_name', 'budget_amount', 'budget_period', 'start_date', 'end_date'];
foreach ($required as $col) {
    if (!in_array($col, $header, true)) {
        fclose($handle);
        echo json_encode(['success' => false, 'error' => 'invalid_format']);
        exit();
    }
}

$nameIdx      = array_search('budget_name',        $header, true);
$amtIdx       = array_search('budget_amount',      $header, true);
$periodIdx    = array_search('budget_period',      $header, true);
$startIdx     = array_search('start_date',         $header, true);
$endIdx       = array_search('end_date',           $header, true);
$recurIdx     = array_search('is_recurring',       $header, true);
$recurDaysIdx = array_search('recurring_days',     $header, true);
$qlabelIdx    = array_search('quarter_label',      $header, true);
$groupIdx     = array_search('recurring_group_id', $header, true);

$allowed_periods = ['daily', 'weekly', 'monthly', 'yearly', 'custom'];

// ── Read all rows first, then remap group IDs ─────────────────────────────────
$rows    = [];
$skipped = 0;

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 6) { $skipped++; continue; }

    $budget_name    = mb_substr(trim($row[$nameIdx] ?? ''), 0, 255);
    $budget_amount  = floatval($row[$amtIdx] ?? 0);
    $budget_period  = trim($row[$periodIdx] ?? '');
    $start_date     = trim($row[$startIdx] ?? '');
    $end_date       = trim($row[$endIdx] ?? '');
    $is_recurring   = ($recurIdx !== false)     ? intval($row[$recurIdx] ?? 0)       : 0;
    $recurring_days = ($recurDaysIdx !== false)  ? trim($row[$recurDaysIdx] ?? '')    : '';
    $quarter_label  = ($qlabelIdx !== false)     ? trim($row[$qlabelIdx] ?? '')       : '';
    $old_group_id   = ($groupIdx !== false)      ? trim($row[$groupIdx] ?? '')        : '';

    if ($budget_name === '')  { $skipped++; continue; }
    if ($budget_amount <= 0)  { $skipped++; continue; }

    if (!in_array($budget_period, $allowed_periods, true)) {
        $budget_period = 'custom';
    }

    foreach ([$start_date, $end_date] as $d) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { $skipped++; continue 2; }
        $p = explode('-', $d);
        if (!checkdate((int)$p[1], (int)$p[2], (int)$p[0])) { $skipped++; continue 2; }
    }

    if ($end_date < $start_date) { $skipped++; continue; }

    // Only carry a quarter_label if this row is actually recurring
    if (!$is_recurring) { $quarter_label = ''; $old_group_id = ''; }
    // Validate quarter_label value
    if (!in_array($quarter_label, ['Q1','Q2','Q3','Q4','Q5',''], true)) {
        $quarter_label = '';
    }

    $rows[] = [
        'budget_name'    => $budget_name,
        'budget_amount'  => $budget_amount,
        'budget_period'  => $budget_period,
        'start_date'     => $start_date,
        'end_date'       => $end_date,
        'is_recurring'   => $is_recurring,
        'recurring_days' => $recurring_days,
        'quarter_label'  => $quarter_label,
        'old_group_id'   => $old_group_id,
    ];
}
fclose($handle);

// ── Remap old group IDs → fresh group IDs ─────────────────────────────────────
// Rows with the same non-empty old_group_id share one new group ID.
// Rows with no group ID but is_recurring=1 get their own new group ID
// (they were created before group IDs were exported — group by name+days).
$groupMap = []; // old_group_id => new_group_id

foreach ($rows as &$r) {
    if (!$r['is_recurring']) {
        $r['new_group_id'] = '';
        continue;
    }
    $key = $r['old_group_id'];
    if ($key === '') {
        // Fall back: group by name + recurring_days so Q1-Q5 rows still cluster
        $key = '__auto__' . $r['budget_name'] . '|' . $r['recurring_days'];
    }
    if (!isset($groupMap[$key])) {
        $groupMap[$key] = uniqid('qg_', true);
    }
    $r['new_group_id'] = $groupMap[$key];
}
unset($r);

// ── Insert rows ───────────────────────────────────────────────────────────────
$imported = 0;

foreach ($rows as $r) {
    $ql       = $r['quarter_label'] !== '' ? $r['quarter_label'] : null;
    $gid      = $r['new_group_id']  !== '' ? $r['new_group_id']  : null;
    $enc_name = encrypt_value($r['budget_name']);
    $enc_amt  = encrypt_value(strval($r['budget_amount']));

    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_budgets
            (user_id, budget_name, budget_amount, budget_period,
             start_date, end_date,
             recurring_days, is_recurring, quarter_label,
             recurring_group_id, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssssiss",
            $user_id, $enc_name, $enc_amt, $r['budget_period'],
            $r['start_date'], $r['end_date'],
            $r['recurring_days'], $r['is_recurring'], $ql, $gid);
        if (mysqli_stmt_execute($stmt)) {
            $imported++;
        } else {
            $skipped++;
        }
        mysqli_stmt_close($stmt);
    } else {
        $skipped++;
    }
}

echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
exit();


$mime = $_FILES['csv_file']['type'] ?? '';
$allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
if (!in_array($mime, $allowed_mimes, true) && strpos($mime, 'text/') !== 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_type']);
    exit();
}

$tmp = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($tmp)) {
    echo json_encode(['success' => false, 'error' => 'upload_error']);
    exit();
}

$handle = fopen($tmp, 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'error' => 'read_error']);
    exit();
}

// Strip UTF-8 BOM if present
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$header = fgetcsv($handle);
if (!$header) {
    fclose($handle);
    echo json_encode(['success' => false, 'error' => 'empty_file']);
    exit();
}

$header = array_map('trim', $header);
$required = ['budget_name', 'budget_amount', 'budget_period', 'start_date', 'end_date'];
foreach ($required as $col) {
    if (!in_array($col, $header, true)) {
        fclose($handle);
        echo json_encode(['success' => false, 'error' => 'invalid_format']);
        exit();
    }
}

$nameIdx      = array_search('budget_name',      $header, true);
$amtIdx       = array_search('budget_amount',    $header, true);
$periodIdx    = array_search('budget_period',    $header, true);
$startIdx     = array_search('start_date',       $header, true);
$endIdx       = array_search('end_date',         $header, true);
$recurIdx     = array_search('is_recurring',     $header, true);
$recurDaysIdx = array_search('recurring_days',   $header, true);

$allowed_periods = ['daily', 'weekly', 'monthly', 'yearly', 'custom'];

$imported = 0;
$skipped  = 0;

// Ensure quarterly columns exist before import
mysqli_query($savienojums, "ALTER TABLE BU_budgets ADD COLUMN IF NOT EXISTS quarter_label VARCHAR(2) NULL DEFAULT NULL");
mysqli_query($savienojums, "ALTER TABLE BU_budgets ADD COLUMN IF NOT EXISTS recurring_group_id VARCHAR(36) NULL DEFAULT NULL");

// Helper: derive quarter label (Q1-Q5) from the day-of-month of start_date
function quarterLabelFromDate(string $date): string {
    $day = (int)substr($date, 8, 2);
    if ($day <= 7)  return 'Q1';
    if ($day <= 14) return 'Q2';
    if ($day <= 21) return 'Q3';
    if ($day <= 28) return 'Q4';
    return 'Q5';
}

// ── First pass: collect and validate all rows ────────────────────────────────
$valid_rows = [];
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 6) { $skipped++; continue; }

    $budget_name    = mb_substr(trim($row[$nameIdx] ?? ''), 0, 255);
    $budget_amount  = floatval($row[$amtIdx] ?? 0);
    $budget_period  = trim($row[$periodIdx] ?? '');
    $start_date     = trim($row[$startIdx] ?? '');
    $end_date       = trim($row[$endIdx] ?? '');
    $is_recurring   = ($recurIdx !== false) ? intval($row[$recurIdx] ?? 0) : 0;
    $recurring_days = ($recurDaysIdx !== false) ? trim($row[$recurDaysIdx] ?? '') : '';

    if ($budget_name === '') { $skipped++; continue; }
    if ($budget_amount <= 0) { $skipped++; continue; }

    if (!in_array($budget_period, $allowed_periods, true)) {
        $budget_period = 'custom';
    }

    $date_ok = true;
    foreach ([$start_date, $end_date] as $d) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { $date_ok = false; break; }
        $parts = explode('-', $d);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) { $date_ok = false; break; }
    }
    if (!$date_ok) { $skipped++; continue; }
    if ($end_date < $start_date) { $skipped++; continue; }

    $valid_rows[] = compact(
        'budget_name', 'budget_amount', 'budget_period',
        'start_date', 'end_date',
        'is_recurring', 'recurring_days'
    );
}
fclose($handle);

// ── Second pass: group recurring rows, insert all ───────────────────────────
// Recurring rows with the same (name + amount + recurring_days + year-month of start_date)
// belong to the same quarterly group and get a shared recurring_group_id.
$group_id_map = []; // group_key => recurring_group_id

foreach ($valid_rows as $r) {
    $quarter_label    = null;
    $recurring_group_id = null;

    if ($r['is_recurring'] && $r['recurring_days'] !== '') {
        // Build a group key: same budget across the same calendar month = one group
        $year_month  = substr($r['start_date'], 0, 7); // "YYYY-MM"
        $group_key   = $r['budget_name'] . '|' . $r['budget_amount'] . '|' . $r['recurring_days'] . '|' . $year_month;

        if (!isset($group_id_map[$group_key])) {
            $group_id_map[$group_key] = uniqid('qg_', true);
        }
        $recurring_group_id = $group_id_map[$group_key];
        $quarter_label      = quarterLabelFromDate($r['start_date']);
        $budget_period      = 'weekly';
    } else {
        $budget_period = $r['budget_period'];
    }

    $enc_name = encrypt_value($r['budget_name']);
    $enc_amt  = encrypt_value(strval($r['budget_amount']));

    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_budgets
            (user_id, budget_name, budget_amount, budget_period,
             start_date, end_date,
             recurring_days, is_recurring, quarter_label,
             recurring_group_id, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssssiss",
            $user_id, $enc_name, $enc_amt, $budget_period,
            $r['start_date'], $r['end_date'],
            $r['recurring_days'], $r['is_recurring'], $quarter_label,
            $recurring_group_id);
        if (mysqli_stmt_execute($stmt)) {
            $imported++;
        } else {
            $skipped++;
        }
        mysqli_stmt_close($stmt);
    } else {
        $skipped++;
    }
}

echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
exit();
