<?php
session_start();
require_once('../../assets/database.php');

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['administrator', 'moderator'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$stats = ['total_users' => 0, 'total_budget_count' => 0, 'total_transactions' => 0, 'tx_this_month' => 0];

$result = mysqli_query($savienojums, "SELECT COUNT(*) as count FROM BU_users");
if ($result) $stats['total_users'] = (int)mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($savienojums, "SELECT COUNT(*) as count FROM BU_budgets");
if ($result) $stats['total_budget_count'] = (int)mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($savienojums, "SELECT COUNT(*) as count FROM BU_transactions");
if ($result) $stats['total_transactions'] = (int)mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($savienojums, "SELECT COUNT(*) as count FROM BU_transactions WHERE date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
if ($result) $stats['tx_this_month'] = (int)mysqli_fetch_assoc($result)['count'];

$_t0 = microtime(true);
mysqli_query($savienojums, 'SELECT 1');
$db_latency_ms = round((microtime(true) - $_t0) * 1000, 2);
if ($db_latency_ms >= 100)     $latency_level = 'critical';
elseif ($db_latency_ms >= 40)  $latency_level = 'warning';
else                           $latency_level = 'online';

$recent_registered = [];
$result = mysqli_query($savienojums, "SELECT username, email, created_at FROM BU_users ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $recent_registered[] = $row;
}

$recent_logins = [];
$result = mysqli_query($savienojums, "SELECT username, email, last_login FROM BU_users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $recent_logins[] = $row;
}

echo json_encode([
    'stats'             => $stats,
    'db_latency_ms'     => $db_latency_ms,
    'latency_level'     => $latency_level,
    'recent_registered' => $recent_registered,
    'recent_logins'     => $recent_logins,
]);
