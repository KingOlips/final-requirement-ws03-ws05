<?php
// Session is now handled securely in security.php

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        if (file_exists('login.php')) {
            header("Location: login.php");
        } else {
            header("Location: ../login.php");
        }
        exit();
    } else {
        if (!isset($_COOKIE['app_logged_in'])) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie('app_logged_in', '1', [
                'expires' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
}
?>
