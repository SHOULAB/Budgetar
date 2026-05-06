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

// Validate extension
$original_name = $_FILES['csv_file']['name'] ?? '';
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'error' => 'invalid_type']);
    exit();
}

// Validate MIME type (basic check)
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

// Read and validate header row
$header = fgetcsv($handle);
if (!$header) {
    fclose($handle);
    echo json_encode(['success' => false, 'error' => 'empty_file']);
    exit();
}

$header = array_map('trim', $header);
$required = ['date', 'amount', 'type', 'description'];
foreach ($required as $col) {
    if (!in_array($col, $header, true)) {
        fclose($handle);
        echo json_encode(['success' => false, 'error' => 'invalid_format']);
        exit();
    }
}

$dateIdx  = array_search('date',         $header, true);
$amtIdx   = array_search('amount',       $header, true);
$typeIdx  = array_search('type',         $header, true);
$descIdx  = array_search('description',  $header, true);
$recurIdx = array_search('is_recurring', $header, true);

$imported = 0;
$skipped  = 0;

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 4) {
        $skipped++;
        continue;
    }

    $date        = trim($row[$dateIdx] ?? '');
    $amount      = floatval($row[$amtIdx] ?? 0);
    $type        = trim($row[$typeIdx] ?? '');
    $description = trim($row[$descIdx] ?? '');
    $is_recurring = ($recurIdx !== false) ? intval($row[$recurIdx] ?? 0) : 0;

    // Validate date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $skipped++;
        continue;
    }
    // Validate date is a real date
    $parts = explode('-', $date);
    if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
        $skipped++;
        continue;
    }

    if ($amount <= 0) {
        $skipped++;
        continue;
    }

    if (!in_array($type, ['income', 'expense'], true)) {
        $skipped++;
        continue;
    }

    // Truncate description to prevent oversized inserts
    $description = mb_substr($description, 0, 255);

    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_transactions (user_id, date, amount, type, description, is_recurring)
         VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isdssi",
            $user_id, $date, $amount, $type, $description, $is_recurring);
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

fclose($handle);

echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
exit();
