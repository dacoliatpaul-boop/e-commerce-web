<?php
// config/app.php — PDO connection, included by login.php and register.php

// ── Session cookie: tied to the browser process, not a fixed expiry ────────
// lifetime = 0 means the cookie has NO expiry date set, so the browser
// deletes it the moment the browser itself is fully closed (not on a
// page refresh/reload — only when the process actually quits). Once that
// cookie is gone, $_SESSION['user_id'] is gone too, so the user appears
// logged out the next time they visit.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

// ── Auto-logout: destroy session if the login timeout has passed ────────────
if (!empty($_SESSION['user_id']) && !empty($_SESSION['login_expiry'])) {
    if (time() > $_SESSION['login_expiry']) {
        // Timeout reached — wipe session and cookies
        $_SESSION = [];
        session_destroy();
        setcookie('user_id',  '', time() - 3600, '/', '', false, true);
        setcookie('username', '', time() - 3600, '/', '', false, true);
        header('Location: login.php');
        exit;
    }
}

// ── Database Configuration ──────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'dcoweb');       // ← new database name
define('DB_USER',     'root');
define('DB_PASSWORD', '');             // change if your MySQL root has a password
define('DB_CHARSET',  'utf8mb4');

// ── PDO Connection ──────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    die("<h2 style='color:red;font-family:sans-serif;padding:20px;'>
         Database connection failed: " . htmlspecialchars($e->getMessage()) . "
         </h2>");
}