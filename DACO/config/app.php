<?php
/**
 * DCO — App Config
 * Session cookie lifetime = 0 means the cookie is a "session cookie":
 * it lives while the browser is open, and is deleted when the browser closes.
 * The cart uses sessionStorage (JS) which resets on page refresh.
 */

// Must be called BEFORE session_start()
ini_set('session.cookie_lifetime', 0);        // cookie deleted when browser closes
ini_set('session.gc_maxlifetime',  7200);     // server-side session data lives 2 hrs

session_start();

// ── Database connection ────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'dco');
define('DB_USER', 'root');       // change to your DB username
define('DB_PASS', '');           // change to your DB password

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log this and show a friendly error page
    die('Database connection failed. Please try again later.');
}