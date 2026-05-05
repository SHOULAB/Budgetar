<?php
// auth_check.php — Session integrity check
//
// Must be included AFTER session_start(), the session user_id check,
// and require_once('database.php') have already run.
//
// Optionally define $_auth_login_redirect before including this file.
// Defaults to 'login.php' (works for pages in user/php/).
// Admin pages should set: $_auth_login_redirect = '../../user/php/login.php';

$_auth_redirect = $_auth_login_redirect ?? 'login.php';

$_auth_stmt = mysqli_prepare($savienojums, "SELECT email FROM BU_users WHERE id = ? LIMIT 1");
if ($_auth_stmt) {
    $uid = (int) $_SESSION['user_id'];
    mysqli_stmt_bind_param($_auth_stmt, "i", $uid);
    mysqli_stmt_execute($_auth_stmt);
    mysqli_stmt_bind_result($_auth_stmt, $_auth_email);
    $_auth_found = mysqli_stmt_fetch($_auth_stmt);
    mysqli_stmt_close($_auth_stmt);

    if (!$_auth_found || empty($_auth_email)) {
        session_unset();
        session_destroy();
        header('Location: ' . $_auth_redirect);
        exit();
    }
} else {
    session_unset();
    session_destroy();
    header('Location: ' . $_auth_redirect);
    exit();
}

unset($_auth_redirect, $_auth_stmt, $uid, $_auth_email, $_auth_found, $_auth_login_redirect);
