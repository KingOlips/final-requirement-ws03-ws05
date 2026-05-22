<?php
// ──────────────────────────────────────────────
// Secure session settings MUST be set before session starts
// ──────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');   // NEW: prevent cross-site cookie leakage
ini_set('session.gc_maxlifetime', 1800);         // NEW: session expires after 30 min idle

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ──────────────────────────────────────────────
// HTTP Security Headers
// ──────────────────────────────────────────────

// Prevent browsers from caching private pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Block clickjacking: only allow framing by same origin
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME-type sniffing
header("X-Content-Type-Options: nosniff");

// Enable built-in browser XSS filtering (legacy browsers)
header("X-XSS-Protection: 1; mode=block");

// Content Security Policy – restrict scripts/styles to same origin only
// 'unsafe-inline' is kept for existing inline scripts/styles; tighten further
// once external CDN links are nonce-ified.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com https://fonts.gstatic.com; font-src 'self' https://fonts.gstatic.com https://unpkg.com data:; img-src 'self' data:; frame-ancestors 'none';");

// HSTS – force HTTPS (only effective when served over TLS; safe to include locally)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Referrer policy: don't leak URL to third parties
header("Referrer-Policy: strict-origin-when-cross-origin");

// Permissions policy: disable geolocation, camera, mic
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// ──────────────────────────────────────────────
// CSRF Token
// ──────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ──────────────────────────────────────────────
// Output escaping
// ──────────────────────────────────────────────
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ──────────────────────────────────────────────
// Password Strength Validation
// ──────────────────────────────────────────────
/**
 * Returns an array of error messages. Empty array = password is strong enough.
 */
function validate_password_strength(string $password): array {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g. !@#\$%^&*).";
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Global role state
// ──────────────────────────────────────────────
$user_role = $_SESSION['role'] ?? 'user';
?>
