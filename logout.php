<?php
require_once 'auth/security.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

if (isset($_COOKIE['app_logged_in'])) {
    setcookie('app_logged_in', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
}

// Destroy the session
session_destroy();
session_write_close();

header("Location: login.php");
exit();
?>
