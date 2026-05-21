<?php
require_once __DIR__ . '/security.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        if (isset($_COOKIE['app_logged_in'])) {
            setcookie('app_logged_in', '', time() - 3600, '/');
        }

        if (file_exists('login.php')) {
            header("Location: login.php");
        } else {
            header("Location: ../login.php");
        }
        exit();
    } else {
        if (!isset($_COOKIE['app_logged_in'])) {
            setcookie('app_logged_in', '1', 0, '/');
        }
    }
}
?>
