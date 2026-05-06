<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['administrator', 'moderator'])) {
    header('Location: ../../user/php/login.php');
    exit();
}
require_once('../../assets/database.php');

$_auth_login_redirect = '../../user/php/login.php';
require_once('../../assets/auth_check.php');

$user_id = $_SESSION['user_id'];
$type    = $_GET['type'] ?? 'transactions';

if (!in_array($type, ['transactions', 'budgets'], true)) {
    $type = 'transactions';
}

$filename = 'budgetar_' . $type . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fwrite($out, "\xEF\xBB\xBF");

if ($type === 'budgets') {
    fputcsv($out, ['budget_name', 'budget_amount', 'budget_period', 'start_date', 'end_date', 'warning_threshold', 'is_recurring', 'recurring_days', 'quarter_label', 'recurring_group_id']);

    $stmt = mysqli_prepare($savienojums,
        "SELECT budget_name, budget_amount, budget_period, start_date, end_date,
                warning_threshold, is_recurring, COALESCE(recurring_days, '') AS recurring_days,
                COALESCE(quarter_label, '') AS quarter_label,
                COALESCE(recurring_group_id, '') AS recurring_group_id
         FROM BU_budgets
         WHERE user_id = ?
         ORDER BY recurring_group_id, quarter_label, start_date DESC";
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($out, array_values($row));
        }
        mysqli_stmt_close($stmt);
    }
} else {
    fputcsv($out, ['date', 'amount', 'type', 'description', 'is_recurring']);

    $stmt = mysqli_prepare($savienojums,
        "SELECT date, amount, type,
                COALESCE(description, '') AS description,
                is_recurring
         FROM BU_transactions
         WHERE user_id = ?
         ORDER BY date DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($out, array_values($row));
        }
        mysqli_stmt_close($stmt);
    }
}

fclose($out);
exit();
